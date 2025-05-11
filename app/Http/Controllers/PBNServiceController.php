<?php
namespace App\Http\Controllers;

use App\Models\PbnSite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Routing\Controller as BaseController;
use App\Http\Middleware\JWTMiddleware;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="PBNService",
 *     description="Управление PBN сайтами"
 * )
 */
class PBNServiceController extends BaseController
{
    public function __construct()
    {
        $this->middleware(JWTMiddleware::class);
    }

    /**
     * @OA\Post(
     *     path="/api/pbn_sites",
     *     tags={"PBNService"},
     *     summary="Создать PBN сайт",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string", maxLength=255)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Created",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer"),
     *             @OA\Property(property="user_id", type="integer"),
     *             @OA\Property(property="name", type="string")
     *         )
     *     )
     * )
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255'
        ]);

        $user = Auth::user();

        $pbnSite = PbnSite::create([
            'user_id' => $user->id,
            'name'    => $request->name,
        ]);

        return response()->json($pbnSite, 201);
    }

    /**
     * @OA\Get(
     *     path="/api/pbn_sites",
     *     tags={"PBNService"},
     *     summary="Список PBN сайтов пользователя",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="OK",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="user_id", type="integer"),
     *                 @OA\Property(property="name", type="string")
     *             )
     *         )
     *     )
     * )
     */
    public function index()
    {
        $user = Auth::user();
        $pbnSites = PbnSite::where('user_id', $user->id)->get();

        return response()->json($pbnSites, 200);
    }

    /**
     * @OA\Get(
     *     path="/api/pbn_sites/{id}",
     *     tags={"PBNService"},
     *     summary="Получить PBN сайт по ID",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID PBN сайта",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="OK",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer"),
     *             @OA\Property(property="user_id", type="integer"),
     *             @OA\Property(property="name", type="string")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Not Found")
     * )
     */
    public function show($id)
    {
        $user = Auth::user();
        $pbnSite = PbnSite::where('user_id', $user->id)
            ->where('id', $id)
            ->first();

        if (! $pbnSite) {
            return response()->json(['error' => 'Domain not found'], 404);
        }

        return response()->json($pbnSite, 200);
    }
}
