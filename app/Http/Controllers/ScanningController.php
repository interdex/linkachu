<?php

namespace App\Http\Controllers;

use App\Models\PbnSite;
use App\Models\PbnSiteDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class ScanningController extends Controller
{
    public function startScanning($id)
    {

        $status = Redis::get("scanning_status_{$id}");
        Log::info("Статус сканирования для сайта с ID {$id}: {$status}");

        if ($status == 'running') {
            Log::info("Сканирование уже запущено для сайта с ID: {$id}");
            return response()->json(['status' => 'already_running'], 400);
        }

        $site = PbnSite::findOrFail($id);



        // Запуск задачи на воркере
        \App\Jobs\ScanSite::dispatch($site);

        return response()->json(['status' => 'started']);
    }




    public function scanningStatus($id)
    {
        $progress = Redis::get("scanning_progress_{$id}") ?? 0;
        $currentLink = Redis::get("scanning_current_link_{$id}") ?? null;

        return response()->json([
            'progress' => $progress,
            'currentLink' => $currentLink,
            'new_link' => Redis::lpop("scanning_new_links_{$id}")
        ]);
    }

    public function getDomains($id)
    {
        $domains = PbnSiteDetail::where('pbn_site_id', $id)->get();
        return response()->json($domains);
    }
}
