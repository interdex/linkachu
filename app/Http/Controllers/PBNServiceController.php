<?php

namespace App\Http\Controllers;

use App\Models\PbnSite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Middleware\JWTMiddleware;
use Illuminate\Routing\Controller as BaseController;

class PBNServiceController extends BaseController
{
    public function __construct()
    {
        $this->middleware(JWTMiddleware::class);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $user = Auth::user();

        $pbnSite = PbnSite::create([
            'user_id' => $user->id,
            'name' => $request->name,
        ]);

        return response()->json($pbnSite, 201);
    }

    public function index()
    {
        $user = Auth::user();
        $pbnSites = PbnSite::where('user_id', $user->id)->get();

        return response()->json($pbnSites, 200);
    }

    public function show($id)
    {
        $user = Auth::user();
        $pbnSite = PbnSite::where('user_id', $user->id)->where('id', $id)->first();

        if (!$pbnSite) {
            return response()->json(['error' => 'Domain not found'], 404);
        }

        return response()->json($pbnSite, 200);
    }
}
