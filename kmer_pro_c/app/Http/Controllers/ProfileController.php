<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ProfileController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function show()
    {
        $user = Auth::user()->load(['services']);
        return response()->json(['user' => $user]);
    }

    public function update(Request $request)
    {
        $user = Auth::user();
        
        $validator = Validator::make($request->all(), [
            'nom' => 'sometimes|string|max:255',
            'telephone' => 'sometimes|string|max:20',
            'adresse' => 'sometimes|string|max:255',
            'ville' => 'sometimes|string|max:100',
            'pays' => 'sometimes|string|max:100',
            'description' => 'sometimes|string',
            'competences' => 'sometimes|array',
            'experience' => 'sometimes|string',
            'diplomes' => 'sometimes|array',
            'photo' => 'sometimes|image|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        if ($request->hasFile('photo')) {
            if ($user->photo) {
                Storage::delete($user->photo);
            }
            $path = $request->file('photo')->store('profiles', 'public');
            $user->photo = $path;
        }

        $user->update($request->except('photo'));

        return response()->json([
            'message' => 'Profil mis à jour avec succès',
            'user' => $user->fresh()
        ]);
    }

    public function updateDocuments(Request $request)
    {
        $user = Auth::user();
        
        if (!$user->isProfessionnel()) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $validator = Validator::make($request->all(), [
            'diplomes' => 'required|array',
            'diplomes.*.titre' => 'required|string|max:255',
            'diplomes.*.institution' => 'required|string|max:255',
            'diplomes.*.date_obtention' => 'required|date',
            'diplomes.*.document' => 'required|file|mimes:pdf,jpg,jpeg,png|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $diplomes = [];
        foreach ($request->diplomes as $diplome) {
            $path = $diplome['document']->store('diplomes', 'public');
            $diplomes[] = [
                'titre' => $diplome['titre'],
                'institution' => $diplome['institution'],
                'date_obtention' => $diplome['date_obtention'],
                'document' => $path
            ];
        }

        $user->diplomes = $diplomes;
        $user->save();

        return response()->json([
            'message' => 'Documents mis à jour avec succès',
            'user' => $user->fresh()
        ]);
    }

    public function updateCompetences(Request $request)
    {
        $user = Auth::user();
        
        if (!$user->isProfessionnel()) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $validator = Validator::make($request->all(), [
            'competences' => 'required|array',
            'competences.*.nom' => 'required|string|max:255',
            'competences.*.niveau' => 'required|string|in:débutant,intermédiaire,expert',
            'competences.*.annees_experience' => 'required|integer|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user->competences = $request->competences;
        $user->save();

        return response()->json([
            'message' => 'Compétences mises à jour avec succès',
            'user' => $user->fresh()
        ]);
    }
}
