<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Document;
use App\Models\Competence;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ValidationProfessionnelController extends Controller
{
    public function soumettreDocument(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'document' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'type' => 'required|in:cni,diplome,certificat,attestation',
            'numero' => 'required|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $path = $request->file('document')->store('documents', 'public');

        $document = Document::create([
            'user_id' => auth()->id(),
            'type' => $request->type,
            'numero' => $request->numero,
            'chemin' => $path,
            'statut' => 'en_attente'
        ]);

        return response()->json([
            'message' => 'Document soumis avec succès',
            'document' => $document
        ], 201);
    }

    public function soumettreCompetences(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'competences' => 'required|array',
            'competences.*' => 'exists:competences,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = auth()->user();
        $user->competences()->sync($request->competences);

        return response()->json([
            'message' => 'Compétences mises à jour avec succès',
            'competences' => $user->competences
        ]);
    }

    public function validerDocument(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'statut' => 'required|in:valide,rejete',
            'commentaire' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $document = Document::findOrFail($id);
        $document->update([
            'statut' => $request->statut,
            'commentaire' => $request->commentaire
        ]);

        return response()->json([
            'message' => 'Document ' . ($request->statut === 'valide' ? 'validé' : 'rejeté') . ' avec succès',
            'document' => $document
        ]);
    }

    public function listerDocuments()
    {
        $documents = Document::where('user_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'documents' => $documents
        ]);
    }

    public function listerCompetences()
    {
        $competences = auth()->user()->competences;

        return response()->json([
            'competences' => $competences
        ]);
    }

    public function listerBadges()
    {
        $user = auth()->user();
        
        $badges = [];
        
        // Badge d'identité vérifiée
        $cniVerifiee = Document::where('user_id', $user->id)
            ->where('type', 'cni')
            ->where('statut', 'valide')
            ->exists();
            
        $badges[] = [
            'type' => 'identite_verifiee',
            'obtenu' => $cniVerifiee,
            'description' => 'Identité vérifiée'
        ];
        
        // Badge de professionnel certifié
        $diplomeVerifie = Document::where('user_id', $user->id)
            ->where('type', 'diplome')
            ->where('statut', 'valide')
            ->exists();
            
        $badges[] = [
            'type' => 'professionnel_certifie',
            'obtenu' => $diplomeVerifie,
            'description' => 'Professionnel certifié'
        ];
        
        // Badge d'expert
        $nbCompetences = $user->competences()->count();
        $badges[] = [
            'type' => 'expert',
            'obtenu' => $nbCompetences >= 3,
            'description' => 'Expert (3+ compétences)'
        ];
        
        return response()->json([
            'badges' => $badges
        ]);
    }
} 