<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Service;
use App\Models\Demande;
use App\Models\Paiement;
use App\Models\Message;
use App\Models\Competence;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StatistiqueController extends Controller
{
    public function global()
    {
        $stats = [
            'total_utilisateurs' => User::count(),
            'total_services' => Service::count(),
            'total_demandes' => Demande::count(),
            'total_paiements' => Paiement::count(),
            'chiffre_affaires' => Paiement::where('statut', 'complete')->sum('montant'),
            'taux_satisfaction' => Demande::whereNotNull('note')->avg('note') ?? 0
        ];

        return response()->json($stats);
    }

    public function performance()
    {
        $stats = [
            'taux_completion' => $this->getTauxCompletion(),
            'temps_moyen_traitement' => $this->getTempsTraitement(),
            'satisfaction_clients' => $this->getSatisfactionClients(),
            'services_populaires' => $this->getServicesPopulaires(),
            'taux_acceptation' => $this->getTauxAcceptationGlobal()
        ];

        return response()->json($stats);
    }

    public function financial()
    {
        $stats = [
            'chiffre_affaires_total' => Paiement::where('statut', 'complete')->sum('montant'),
            'paiements_en_attente' => Paiement::where('statut', 'en_attente')->sum('montant'),
            'paiements_par_methode' => $this->getPaiementsParMethode(),
            'evolution_mensuelle' => $this->getEvolutionMensuelle()
        ];

        return response()->json($stats);
    }

    public function professionnel(Request $request)
    {
        $user = $request->user();
        $stats = [
            'total_services' => Service::where('user_id', $user->id)->count(),
            'total_demandes' => Demande::where('professionnel_id', $user->id)->count(),
            'note_moyenne' => Demande::where('professionnel_id', $user->id)->whereNotNull('note')->avg('note') ?? 0,
            'revenus_total' => Paiement::where('professionnel_id', $user->id)->where('statut', 'complete')->sum('montant'),
            'taux_acceptation' => $this->getTauxAcceptation($user->id)
        ];

        return response()->json($stats);
    }

    public function utilisateurs()
    {
        $stats = [
            'total_utilisateurs' => User::count(),
            'professionnels_actifs' => User::where('type', 'professionnel')->count(),
            'clients_actifs' => User::where('type', 'client')->count(),
            'nouveaux_utilisateurs_mois' => User::whereMonth('created_at', now()->month)->count(),
            'repartition_geographique' => $this->getRepartitionGeographique(),
            'utilisateurs_par_zone' => $this->getUtilisateursParZone()
        ];

        return response()->json($stats);
    }

    public function messages()
    {
        $stats = [
            'total_messages' => Message::count(),
            'messages_non_lus' => Message::where('lu', false)->count(),
            'conversations_actives' => $this->getConversationsActives(),
            'temps_moyen_reponse' => $this->getTempsReponse()
        ];

        return response()->json($stats);
    }

    public function competences()
    {
        $stats = [
            'competences_populaires' => $this->getCompetencesPopulaires()
        ];

        return response()->json($stats);
    }

    private function getTauxCompletion()
    {
        $total = Demande::count();
        if ($total === 0) return 0;
        
        $completees = Demande::where('statut', 'terminee')->count();
        return ($completees / $total) * 100;
    }

    private function getTempsTraitement()
    {
        return Demande::whereNotNull('date_acceptation')
            ->whereNotNull('date_fin')
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, date_acceptation, date_fin)) as temps_moyen')
            ->value('temps_moyen') ?? 0;
    }

    private function getSatisfactionClients()
    {
        return Demande::whereNotNull('note')->avg('note') ?? 0;
    }

    private function getServicesPopulaires()
    {
        return Service::withCount('demandes')
            ->orderBy('demandes_count', 'desc')
            ->limit(5)
            ->get();
    }

    private function getPaiementsParMethode()
    {
        return Paiement::select('methode', DB::raw('count(*) as total'))
            ->groupBy('methode')
            ->get();
    }

    private function getEvolutionMensuelle()
    {
        return Paiement::select(
            DB::raw('YEAR(created_at) as annee'),
            DB::raw('MONTH(created_at) as mois'),
            DB::raw('SUM(montant) as total')
        )
            ->where('statut', 'complete')
            ->groupBy('annee', 'mois')
            ->orderBy('annee', 'desc')
            ->orderBy('mois', 'desc')
            ->limit(12)
            ->get();
    }

    private function getTauxAcceptation($userId)
    {
        $total = Demande::where('professionnel_id', $userId)->count();
        if ($total === 0) return 0;
        
        $acceptees = Demande::where('professionnel_id', $userId)
            ->whereIn('statut', ['acceptee', 'en_cours', 'terminee'])
            ->count();
        
        return ($acceptees / $total) * 100;
    }

    private function getTauxAcceptationGlobal()
    {
        $total = Demande::count();
        if ($total === 0) return 0;
        
        $acceptees = Demande::whereIn('statut', ['acceptee', 'en_cours', 'terminee'])->count();
        return ($acceptees / $total) * 100;
    }

    private function getRepartitionGeographique()
    {
        return User::select('ville', DB::raw('count(*) as total'))
            ->groupBy('ville')
            ->get();
    }

    private function getUtilisateursParZone()
    {
        return User::join('services', 'users.id', '=', 'services.user_id')
            ->join('service_zone', 'services.id', '=', 'service_zone.service_id')
            ->join('zones', 'service_zone.zone_id', '=', 'zones.id')
            ->select('zones.nom', DB::raw('count(distinct users.id) as total'))
            ->groupBy('zones.id', 'zones.nom')
            ->get();
    }

    private function getConversationsActives()
    {
        return Message::select('expediteur_id', 'destinataire_id')
            ->distinct()
            ->count();
    }

    private function getTempsReponse()
    {
        return Message::whereNotNull('date_lecture')
            ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, created_at, date_lecture)) as temps_moyen')
            ->value('temps_moyen') ?? 0;
    }

    private function getCompetencesPopulaires()
    {
        return Competence::withCount('users')
            ->orderBy('users_count', 'desc')
            ->limit(10)
            ->get()
            ->map(function($competence) {
                return [
                    'competence' => $competence->nom,
                    'total' => $competence->users_count
                ];
            });
    }
} 