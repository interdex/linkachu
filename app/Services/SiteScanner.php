<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\UriResolver;
use App\Models\PbnSite;
use App\Models\PbnSiteDetail;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;
use Exception;

class SiteScanner
{
    protected $client;

    public function __construct()
    {
        $this->client = new Client(['timeout' => 10]); // Увеличиваем тайм-аут до 10 секунд
    }

    public function scan(PbnSite $site)
    {
        Log::info("Starting scan for site: {$site->name}");

        $visited = [];
        $queue = [$this->normalizeUrl($site->name)];
        $maxPages = 100000;
        $pageCount = 0;
        $baseDomain = parse_url($this->normalizeUrl($site->name), PHP_URL_HOST);

        $startTime = microtime(true);

        try {
            Log::info("Entering try block");

            while (!empty($queue) && $pageCount < $maxPages) {
                $currentQueue = array_slice($queue, 0, 10); // Обрабатываем 10 URL за раз
                $queue = array_slice($queue, 10);

                $requests = function ($currentQueue) {
                    foreach ($currentQueue as $url) {
                        yield new Request('GET', $url);
                    }
                };

                $pool = new Pool($this->client, $requests($currentQueue), [
                    'concurrency' => 10, // Количество параллельных запросов
                    'fulfilled' => function ($response, $index) use (&$visited, &$queue, &$pageCount, $site, $currentQueue, $baseDomain, $maxPages) {
                        $url = $currentQueue[$index];
                        $url = (string)$url;
                        $visited[$url] = true;
                        $pageCount++;
                        Redis::set("scanning_progress_{$site->id}", ($pageCount / $maxPages) * 100);
                        Redis::set("scanning_current_link_{$site->id}", $url);

                        Log::info("Scanning URL: $url");

                        $links = $this->extractLinks((string) $response->getBody());
                        Log::info("Found links on $url: ", $links);

                        foreach ($links as $link) {
                            // Пропускаем некорректные ссылки
                            if ($this->isInvalidLink($link)) {
                                continue;
                            }

                            $normalizedLink = $this->normalizeUrl($link, $url);
                            $linkDomain = parse_url($normalizedLink, PHP_URL_HOST);

                            Log::info("Normalized link: $normalizedLink");

                            if ($linkDomain !== $baseDomain) {
                                // Обработка внешних ссылок
                                $this->checkExternalLink($normalizedLink, $site);
                            } else {
                                // Обработка внутренних ссылок
                                if (!isset($visited[$normalizedLink]) && !in_array($normalizedLink, $queue)) {
                                    $queue[] = $normalizedLink;
                                }
                            }
                        }
                    },
                    'rejected' => function ($reason, $index) use ($site, $currentQueue, $baseDomain) {
                        $url = $currentQueue[$index];
                        $linkDomain = parse_url($url, PHP_URL_HOST);

                        Log::error("Error scanning $url: " . $reason->getMessage());

                        // Пропускаем некорректные ссылки
                        if ($this->isInvalidLink($url)) {
                            return;
                        }

                        if ($linkDomain !== $baseDomain) {
                            // Записываем внешние ссылки с ошибками
                            PbnSiteDetail::firstOrCreate([
                                'pbn_site_id' => $site->id,
                                'url' => $url,
                            ]);
                            Redis::rpush("scanning_new_links_{$site->id}", $url);
                        }
                    },
                ]);

                $promise = $pool->promise();
                $promise->wait();
            }
        } catch (Exception $e) {
            Log::error("Exception during scan: " . $e->getMessage());
        } finally {
            Log::info("Entering finally block");

            try {
                $statusDel = Redis::del("scanning_status_{$site->id}");
                Log::info("scanning_status_{$site->id} delete status: " . ($statusDel ? 'deleted' : 'not deleted'));

                $progressDel = Redis::del("scanning_progress_{$site->id}");
                Log::info("scanning_progress_{$site->id} delete status: " . ($progressDel ? 'deleted' : 'not deleted'));

                $currentLinkDel = Redis::del("scanning_current_link_{$site->id}");
                Log::info("scanning_current_link_{$site->id} delete status: " . ($currentLinkDel ? 'deleted' : 'not deleted'));
            } catch (Exception $e) {
                Log::error("Exception in finally block: " . $e->getMessage());
            }
        }

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
        //Log::info("Finished scan for site: {$site->name}. Execution time: {$executionTime} seconds");
        Log::info("Attempting to delete Redis key: scanning_current_link_{$site->id}");
        Redis::del("scanning_current_link_{$site->id}");
        Log::info("Deleted Redis key: scanning_current_link_{$site->id}");
    }

    private function checkExternalLink($url, $site)
    {
        try {
            $response = $this->client->get($url);
            $statusCode = $response->getStatusCode();
            if ($statusCode != 200 && $statusCode != 301) {
                PbnSiteDetail::create([
                    'pbn_site_id' => $site->id,
                    'url' => $url,
                ]);
                Redis::rpush("scanning_new_links_{$site->id}", $url);
            }
        } catch (ConnectException $e) {
            PbnSiteDetail::create([
                'pbn_site_id' => $site->id,
                'url' => $url,
            ]);
            Redis::rpush("scanning_new_links_{$site->id}", $url);
        } catch (RequestException $e) {
            PbnSiteDetail::create([
                'pbn_site_id' => $site->id,
                'url' => $url,
            ]);
            Redis::rpush("scanning_new_links_{$site->id}", $url);
        }
    }

    private function normalizeUrl($url, $baseUrl = null)
    {
        if (!preg_match('~^https?://~', $url)) {
            if ($baseUrl) {
                $baseUri = new Uri($baseUrl);
                $uri = new Uri($url);
                return (string) UriResolver::resolve($baseUri, $uri);
            } else {
                $url = "https://$url";
            }
        }

        return $url;
    }

    private function isInvalidLink($url)
    {
        // Пропускаем некорректные ссылки
        return preg_match('/^(tel:|mailto:|javascript:|#|void|tg:|viber:)/i', $url);
    }

    private function extractLinks($html)
    {
        preg_match_all('~<a\s+href="([^"]+)"~i', $html, $matches);
        return $matches[1];
    }
}

