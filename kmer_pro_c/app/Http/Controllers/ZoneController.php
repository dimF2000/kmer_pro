<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ZoneController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function updateZones(Request $request, Service $service)
    {
        if ($service->user_id !== Auth::id()) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $validator = Validator::make($request->all(), [
            'zones' => 'required|array',
            'zones.*' => 'required|string|max:100'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $service->zones_couvertes = $request->zones;
        $service->save();

        return response()->json([
            'message' => 'Zones d\'intervention mises à jour avec succès',
            'service' => $service
        ]);
    }

    public function addZone(Request $request, Service $service)
    {
        if ($service->user_id !== Auth::id()) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $validator = Validator::make($request->all(), [
            'zone' => 'required|string|max:100'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $zones = $service->zones_couvertes;
        if (!in_array($request->zone, $zones)) {
            $zones[] = $request->zone;
            $service->zones_couvertes = $zones;
            $service->save();
        }

        return response()->json([
            'message' => 'Zone ajoutée avec succès',
            'service' => $service
        ]);
    }

    public function removeZone(Request $request, Service $service)
    {
        if ($service->user_id !== Auth::id()) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $validator = Validator::make($request->all(), [
            'zone' => 'required|string|max:100'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $zones = $service->zones_couvertes;
        $zones = array_diff($zones, [$request->zone]);
        $service->zones_couvertes = array_values($zones);
        $service->save();

        return response()->json([
            'message' => 'Zone retirée avec succès',
            'service' => $service
        ]);
    }
} 