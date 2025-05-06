<?php

namespace App\Http\Controllers;

use App\Models\Paiement;
use App\Models\Demande;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\Notification;

class PaiementController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'demande_id' => 'required|exists:demandes,id',
            'montant' => 'required|numeric|min:1000',
            'methode' => 'required|string|in:mobile_money,carte,virement',
            'devise' => 'required|string|in:XAF,EUR,USD',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $demande = Demande::findOrFail($request->demande_id);
        
        // Vérifier que l'utilisateur actuel est bien le client
        if ($demande->client_id !== auth()->id()) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $paiement = Paiement::create([
            'demande_id' => $request->demande_id,
            'client_id' => auth()->id(),
            'professionnel_id' => $demande->professionnel_id,
            'montant' => $request->montant,
            'methode' => $request->methode,
            'devise' => $request->devise,
            'statut' => 'en_attente',
            'reference' => Str::uuid()->toString(),
            'commentaire' => $request->commentaire
        ]);

        return response()->json([
            'message' => 'Paiement enregistré avec succès',
            'paiement' => $paiement
        ], 201);
    }

    public function confirmer(Request $request, $id)
    {
        $paiement = Paiement::findOrFail($id);
        
        // Vérifier que l'utilisateur actuel est bien le professionnel concerné
        if ($paiement->professionnel_id !== auth()->id()) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $paiement->update([
            'statut' => 'confirme',
            'date_confirmation' => now(),
            'commentaire' => $request->commentaire
        ]);

        return response()->json([
            'message' => 'Paiement confirmé avec succès',
            'paiement' => $paiement
        ]);
    }

    public function annuler(Request $request, $id)
    {
        $paiement = Paiement::findOrFail($id);
        
        // Vérifier que l'utilisateur actuel est bien le client
        if ($paiement->client_id !== auth()->id()) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        // On ne peut annuler que les paiements en attente
        if ($paiement->statut !== 'en_attente') {
            return response()->json(['message' => 'Ce paiement ne peut plus être annulé'], 422);
        }

        $paiement->update([
            'statut' => 'annule',
            'commentaire' => $request->raison
        ]);

        return response()->json([
            'message' => 'Paiement annulé avec succès',
            'paiement' => $paiement
        ]);
    }

    public function index()
    {
        $paiements = Paiement::with(['demande'])
            ->where('client_id', auth()->id())
            ->orWhere('professionnel_id', auth()->id())
            ->latest()
            ->paginate(10);

        return response()->json($paiements);
    }

    public function show($id)
    {
        $paiement = Paiement::with(['demande'])
            ->findOrFail($id);
        
        // Vérifier que l'utilisateur actuel est concerné par ce paiement
        if ($paiement->client_id !== auth()->id() && $paiement->professionnel_id !== auth()->id()) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        return response()->json($paiement);
    }

    public function destroy($id)
    {
        $paiement = Paiement::findOrFail($id);
        
        // Vérifier que l'utilisateur actuel est bien le client
        if ($paiement->client_id !== auth()->id()) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        // On ne peut annuler que les paiements en attente
        if ($paiement->statut !== 'en_attente') {
            return response()->json(['message' => 'Ce paiement ne peut plus être annulé'], 422);
        }

        $paiement->update([
            'statut' => 'annule'
        ]);

        return response()->json([
            'message' => 'Paiement annulé avec succès'
        ]);
    }

    public function initier(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'demande_id' => 'required|exists:demandes,id',
            'methode_paiement' => 'required|string|in:mobile_money,carte,virement',
            'montant' => 'required|numeric|min:1000',
            'devise' => 'required|string|in:XAF,EUR,USD',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $demande = Demande::findOrFail($request->demande_id);
        
        // Vérifier que l'utilisateur actuel est bien le client
        if ($demande->client_id !== auth()->id()) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        // Vérifier que la demande est acceptée
        if ($demande->statut !== 'acceptee' && $demande->statut !== 'en_cours') {
            return response()->json(['message' => 'Cette demande n\'est pas dans un état permettant un paiement'], 422);
        }

        $paiement = Paiement::create([
            'demande_id' => $demande->id,
            'client_id' => auth()->id(),
            'professionnel_id' => $demande->professionnel_id,
            'montant' => $request->montant,
            'methode' => $request->methode_paiement,
            'devise' => $request->devise,
            'statut' => 'en_attente',
            'reference' => 'PAY-' . Str::random(10),
        ]);

        // Dans un cas réel, on intégrerait ici avec une API de paiement

        return response()->json([
            'message' => 'Paiement initié avec succès',
            'data' => $paiement
        ]);
    }

    public function destroyAll()
    {
        Notification::where('user_id', auth()->id())->delete();
        
        return response()->json([
            'message' => 'Toutes les notifications ont été supprimées'
        ]);
    }

    public function nonLues()
    {
        // Implementation of nonLues method
    }

    public function initierPaiementDemande(Request $request, $demandeId)
    {
        $validator = Validator::make($request->all(), [
            'methode_paiement' => 'required|string|in:mobile_money,carte,virement',
            'montant' => 'required|numeric|min:1000',
            'devise' => 'required|string|in:XAF,EUR,USD',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $demande = Demande::findOrFail($demandeId);
        
        // Vérifier que l'utilisateur actuel est bien le client
        if ($demande->client_id !== auth()->id()) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        // Vérifier que la demande est acceptée
        if ($demande->statut !== 'acceptee' && $demande->statut !== 'en_cours') {
            return response()->json(['message' => 'Cette demande n\'est pas dans un état permettant un paiement'], 422);
        }

        $paiement = Paiement::create([
            'demande_id' => $demande->id,
            'client_id' => auth()->id(),
            'professionnel_id' => $demande->professionnel_id,
            'montant' => $request->montant,
            'methode' => $request->methode_paiement,
            'devise' => $request->devise,
            'statut' => 'en_attente',
            'reference' => 'PAY-' . Str::random(10),
        ]);

        // Dans un cas réel, on intégrerait ici avec une API de paiement

        return response()->json([
            'message' => 'Paiement initié avec succès',
            'data' => $paiement
        ]);
    }
} 