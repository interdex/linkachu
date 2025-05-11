<?php

namespace App\Http\Controllers;

use App\Models\PbnSite;
use App\Models\PbnSiteDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Routing\Controller as BaseController;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="Scanning",
 *     description="Сканирование PBN сайтов"
 * )
 */
class ScanningController extends BaseController
{
    /**
     * @OA\Post(
     *     path="/api/start-scanning/{id}",
     *     tags={"Scanning"},
     *     summary="Запустить сканирование PBN сайта",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID PBN сайта",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Started",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Already running")
     * )
     */
    public function startScanning($id)
    {
        $status = Redis::get("scanning_status_{$id}");
        Log::info("Статус сканирования для сайта с ID {$id}: {$status}");

        if ($status == 'running') {
            Log::info("Сканирование уже запущено для сайта с ID: {$id}");
            return response()->json(['status' => 'already_running'], 400);
        }

        $site = PbnSite::findOrFail($id);

        \App\Jobs\ScanSite::dispatch($site);

        return response()->json(['status' => 'started']);
    }

    /**
     * @OA\Get(
     *     path="/api/scanning-status/{id}",
     *     tags={"Scanning"},
     *     summary="Получить статус сканирования PBN сайта",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID PBN сайта",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Стatus",
     *         @OA\JsonContent(
     *             @OA\Property(property="progress", type="integer"),
     *             @OA\Property(property="currentLink", type="string", nullable=true),
     *             @OA\Property(property="new_link", type="string", nullable=true)
     *         )
     *     )
     * )
     */
    public function scanningStatus($id)
    {
        $progress = Redis::get("scanning_progress_{$id}") ?? 0;
        $currentLink = Redis::get("scanning_current_link_{$id}") ?? null;

        return response()->json([
            'progress'    => $progress,
            'currentLink' => $currentLink,
            'new_link'    => Redis::lpop("scanning_new_links_{$id}")
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/domains/{id}",
     *     tags={"Scanning"},
     *     summary="Получить домены PBN сайта",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID PBN сайта",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Список доменов",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="pbn_site_id", type="integer"),
     *                 @OA\Property(property="domain", type="string")
     *             )
     *         )
     *     )
     * )
     */
    public function getDomains($id)
    {
        $domains = PbnSiteDetail::where('pbn_site_id', $id)->get();
        return response()->json($domains);
    }
}
