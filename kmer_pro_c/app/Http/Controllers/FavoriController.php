<?php

namespace App\Http\Controllers;

use App\Models\Favori;
use App\Models\Service;
use Illuminate\Http\Request;

class FavoriController extends Controller
{
    public function index(Request $request)
    {
        $favoris = $request->user()->favoris()->with('service')->latest()->paginate(20);
        return response()->json($favoris);
    }

    public function store(Service $service, Request $request)
    {
        $user = $request->user();
        if ($user->favoris()->where('service_id', $service->id)->exists()) {
            return response()->json(['message' => 'Ce service est déjà dans vos favoris'], 400);
        }
        $favori = $user->favoris()->create(['service_id' => $service->id]);
        return response()->json([
            'message' => 'Service ajouté aux favoris',
            'favori' => $favori
        ], 201);
    }

    public function destroy(Service $service, Request $request)
    {
        $user = $request->user();
        $favori = $user->favoris()->where('service_id', $service->id)->first();
        if (!$favori) {
            return response()->json(['message' => 'Ce service n\'est pas dans vos favoris'], 404);
        }
        $favori->delete();
        return response()->json(['message' => 'Service retiré des favoris']);
    }
} 