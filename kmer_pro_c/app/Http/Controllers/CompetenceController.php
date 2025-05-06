<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class CompetenceController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function updateCompetences(Request $request, Service $service)
    {
        if ($service->user_id !== Auth::id()) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $validator = Validator::make($request->all(), [
            'competences' => 'required|array',
            'competences.*.nom' => 'required|string|max:100',
            'competences.*.niveau' => 'required|string|in:débutant,intermédiaire,expert',
            'competences.*.annees_experience' => 'required|integer|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $service->competences = $request->competences;
        $service->save();

        return response()->json([
            'message' => 'Compétences mises à jour avec succès',
            'service' => $service
        ]);
    }

    public function addCompetence(Request $request, Service $service)
    {
        if ($service->user_id !== Auth::id()) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $validator = Validator::make($request->all(), [
            'competence' => 'required|array',
            'competence.nom' => 'required|string|max:100',
            'competence.niveau' => 'required|string|in:débutant,intermédiaire,expert',
            'competence.annees_experience' => 'required|integer|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $competences = $service->competences;
        $competences[] = $request->competence;
        $service->competences = $competences;
        $service->save();

        return response()->json([
            'message' => 'Compétence ajoutée avec succès',
            'service' => $service
        ]);
    }

    public function removeCompetence(Request $request, Service $service)
    {
        if ($service->user_id !== Auth::id()) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $validator = Validator::make($request->all(), [
            'nom' => 'required|string|max:100'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $competences = $service->competences;
        $competences = array_filter($competences, function($competence) use ($request) {
            return $competence['nom'] !== $request->nom;
        });
        $service->competences = array_values($competences);
        $service->save();

        return response()->json([
            'message' => 'Compétence retirée avec succès',
            'service' => $service
        ]);
    }
} 