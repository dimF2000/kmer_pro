<?php

namespace App\Http\Controllers;

use App\Models\Demande;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class DemandeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function index(Request $request)
    {
        $query = Demande::query();

        if ($request->has('statut')) {
            $query->where('statut', $request->statut);
        }

        $demandes = $query->with(['user', 'service'])
            ->latest()
            ->paginate(10);

        return response()->json([
            'data' => $demandes->items()
        ]);
    }

    public function store(Request $request)
    {
        if (!Auth::user()->isClient()) {
            return response()->json(['message' => 'Seuls les clients peuvent créer des demandes'], 403);
        }

        $validator = Validator::make($request->all(), [
            'service_id' => 'required|exists:services,id',
            'description' => 'required|string',
            'date_debut_souhaitee' => 'required|date|after:today',
            'date_fin_souhaitee' => 'required|date|after:date_debut_souhaitee',
            'adresse_intervention' => 'required|string',
            'budget_max' => 'required|numeric|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $service = Service::findOrFail($request->service_id);
        
        $demande = Demande::create([
            'user_id' => Auth::id(),
            'service_id' => $service->id,
            'description' => $request->description,
            'date_debut_souhaitee' => $request->date_debut_souhaitee,
            'date_fin_souhaitee' => $request->date_fin_souhaitee,
            'adresse_intervention' => $request->adresse_intervention,
            'budget_max' => $request->budget_max,
            'statut' => 'en_attente'
        ]);

        return response()->json([
            'message' => 'Demande créée avec succès',
            'demande' => $demande->load(['user', 'service'])
        ], 201);
    }

    public function show(Demande $demande)
    {
        if (!$demande->isOwner(Auth::user()) && !$demande->isProfessionnel(Auth::user())) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        return response()->json([
            'demande' => $demande->load(['user', 'service'])
        ]);
    }

    public function update(Request $request, Demande $demande)
    {
        if (!$demande->isProfessionnel(Auth::user())) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $validator = Validator::make($request->all(), [
            'statut' => 'required|in:acceptee,refusee,terminee',
            'commentaire' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $demande->update($request->only(['statut', 'commentaire']));

        return response()->json([
            'message' => 'Demande mise à jour avec succès',
            'demande' => $demande->load(['user', 'service'])
        ]);
    }

    public function getMyDemandes()
    {
        $demandes = Auth::user()
            ->demandes()
            ->with(['service', 'service.user'])
            ->latest()
            ->paginate(10);

        return response()->json([
            'data' => $demandes->items()
        ]);
    }

    public function getDemandesRecues()
    {
        if (!Auth::user()->isProfessionnel()) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $demandes = Demande::whereHas('service', function ($query) {
            $query->where('user_id', Auth::id());
        })
        ->with(['user', 'service'])
        ->latest()
        ->paginate(10);

        return response()->json([
            'data' => $demandes->items()
        ]);
    }
} 