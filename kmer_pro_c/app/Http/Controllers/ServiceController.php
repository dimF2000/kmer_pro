<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ServiceController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum')->except(['index', 'show', 'getByCategory']);
    }

    public function index(Request $request)
    {
        $query = Service::query();

        // Filtres
        if ($request->has('categorie')) {
            $query->byCategorie($request->categorie);
        }

        if ($request->has('zone')) {
            $query->byZone($request->zone);
        }

        if ($request->has(['prix_min', 'prix_max'])) {
            $query->byPrix($request->prix_min, $request->prix_max);
        }

        if ($request->has('disponible')) {
            $query->disponible();
        }

        // Tri
        $sort = $request->get('sort', 'created_at');
        $direction = $request->get('direction', 'desc');
        $query->orderBy($sort, $direction);

        $services = $query->with('user')->paginate(10);

        return response()->json([
            'data' => $services->items()
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'titre' => 'required|string|max:255',
            'categorie' => 'required|string|max:255',
            'description' => 'required|string',
            'prix' => 'required|numeric|min:0',
            'unite_temps' => 'required|string|in:heure,jour,semaine,mois',
            'duree_estimee' => 'required|string',
            'zones_couvertes' => 'required|array',
            'competences' => 'required|array',
            'galerie' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        if (!Auth::user()->isProfessionnel()) {
            return response()->json(['message' => 'Seuls les professionnels peuvent créer des services'], 403);
        }

        $service = Service::create([
            'user_id' => Auth::id(),
            'titre' => $request->titre,
            'categorie' => $request->categorie,
            'description' => $request->description,
            'prix' => $request->prix,
            'unite_temps' => $request->unite_temps,
            'duree_estimee' => $request->duree_estimee,
            'disponible' => true,
            'disponibilite' => true,
            'zones_couvertes' => $request->zones_couvertes,
            'competences' => $request->competences,
            'galerie' => $request->galerie ?? []
        ]);

        return response()->json([
            'message' => 'Service créé avec succès',
            'service' => $service
        ], 201);
    }

    public function show(Service $service)
    {
        return response()->json([
            'service' => $service->load(['user', 'category'])
        ]);
    }

    public function update(Request $request, Service $service)
    {
        if ($service->user_id !== Auth::id()) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $validator = Validator::make($request->all(), [
            'titre' => 'sometimes|string|max:255',
            'categorie' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'prix' => 'sometimes|numeric|min:0',
            'unite_temps' => 'sometimes|string|in:heure,jour,semaine,mois',
            'duree_estimee' => 'sometimes|string',
            'zones_couvertes' => 'sometimes|array',
            'competences' => 'sometimes|array',
            'galerie' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $service->update($request->all());
        $service->refresh();

        return response()->json([
            'message' => 'Service mis à jour avec succès',
            'service' => $service
        ]);
    }

    public function destroy(Service $service)
    {
        if ($service->user_id !== Auth::id()) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $service->delete();

        return response()->json([
            'message' => 'Service supprimé avec succès'
        ]);
    }

    public function toggleAvailability(Service $service)
    {
        if ($service->user_id !== Auth::id()) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $service->disponibilite = !$service->disponibilite;
        $service->save();

        return response()->json([
            'message' => $service->disponibilite ? 'Service marqué comme disponible' : 'Service marqué comme indisponible',
            'service' => $service
        ]);
    }

    public function getByCategory($categorie)
    {
        $services = Service::byCategorie($categorie)
            ->disponible()
            ->with('user')
            ->paginate(10);

        return response()->json([
            'data' => $services->items()
        ]);
    }

    public function getByUser(User $user)
    {
        $services = $user->services()
            ->with(['category', 'evaluations'])
            ->latest()
            ->paginate(10);

        return response()->json([
            'data' => $services->items(),
            'meta' => [
                'current_page' => $services->currentPage(),
                'last_page' => $services->lastPage(),
                'per_page' => $services->perPage(),
                'total' => $services->total()
            ]
        ]);
    }

    public function search(Request $request)
    {
        $query = Service::query()
            ->with(['user', 'categorie', 'zones', 'competences'])
            ->where('disponible', true);

        // Filtre par catégorie
        if ($request->has('categorie_id')) {
            $query->where('categorie_id', $request->categorie_id);
        }

        // Filtre par zone
        if ($request->has('zone_id')) {
            $query->whereHas('zones', function ($q) use ($request) {
                $q->where('zones.id', $request->zone_id);
            });
        }

        // Filtre par prix
        if ($request->has('prix_min')) {
            $query->where('prix', '>=', $request->prix_min);
        }
        if ($request->has('prix_max')) {
            $query->where('prix', '<=', $request->prix_max);
        }

        // Filtre par compétences
        if ($request->has('competences')) {
            $query->whereHas('competences', function ($q) use ($request) {
                $q->whereIn('competences.id', $request->competences);
            });
        }

        // Filtre par disponibilité
        if ($request->has('disponible')) {
            $query->where('disponible', $request->disponible);
        }

        // Recherche par mot-clé
        if ($request->has('q')) {
            $searchTerm = $request->q;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('titre', 'like', "%{$searchTerm}%")
                    ->orWhere('description', 'like', "%{$searchTerm}%")
                    ->orWhereHas('user', function ($q) use ($searchTerm) {
                        $q->where('nom', 'like', "%{$searchTerm}%")
                            ->orWhere('prenom', 'like', "%{$searchTerm}%");
                    });
            });
        }

        // Tri
        $sortField = $request->get('sort_by', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');
        $query->orderBy($sortField, $sortDirection);

        // Pagination
        $perPage = $request->get('per_page', 15);
        $services = $query->paginate($perPage);

        return response()->json($services);
    }
} 