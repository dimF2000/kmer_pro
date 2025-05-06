<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class GalerieController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function uploadPhoto(Request $request, Service $service)
    {
        if ($service->user_id !== Auth::id()) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $validator = Validator::make($request->all(), [
            'photo' => 'required|image|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        if (count($service->galerie) >= 10) {
            return response()->json([
                'message' => 'La galerie ne peut pas contenir plus de 10 photos'
            ], 422);
        }

        $path = $request->file('photo')->store('services', 'public');
        $galerie = $service->galerie;
        $galerie[] = $path;
        $service->galerie = $galerie;
        $service->save();

        return response()->json([
            'message' => 'Photo ajoutée avec succès',
            'service' => $service
        ]);
    }

    public function removePhoto(Request $request, Service $service)
    {
        if ($service->user_id !== Auth::id()) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $validator = Validator::make($request->all(), [
            'photo_path' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $galerie = $service->galerie;
        if (in_array($request->photo_path, $galerie)) {
            Storage::disk('public')->delete($request->photo_path);
            $galerie = array_diff($galerie, [$request->photo_path]);
            $service->galerie = array_values($galerie);
            $service->save();
        }

        return response()->json([
            'message' => 'Photo supprimée avec succès',
            'service' => $service
        ]);
    }

    public function reorderPhotos(Request $request, Service $service)
    {
        if ($service->user_id !== Auth::id()) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $validator = Validator::make($request->all(), [
            'photos' => 'required|array',
            'photos.*' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Vérifier que toutes les photos existent dans la galerie
        $currentPhotos = $service->galerie;
        foreach ($request->photos as $photo) {
            if (!in_array($photo, $currentPhotos)) {
                return response()->json([
                    'message' => 'Une ou plusieurs photos n\'existent pas dans la galerie'
                ], 422);
            }
        }

        $service->galerie = $request->photos;
        $service->save();

        return response()->json([
            'message' => 'Ordre des photos mis à jour avec succès',
            'service' => $service
        ]);
    }
} 