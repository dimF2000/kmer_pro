<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Service;
use App\Models\Demande;
use App\Models\Paiement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StatisticsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Créer des utilisateurs de test
        $this->admin = User::factory()->create(['type' => 'admin']);
        $this->professionnel = User::factory()->create(['type' => 'professionnel']);
        $this->client = User::factory()->create(['type' => 'client']);
    }

    public function test_global_statistics()
    {
        // Créer des données de test
        $services = Service::factory()->count(5)->create(['user_id' => $this->professionnel->id]);
        $demandes = Demande::factory()->count(10)->create(['client_id' => $this->client->id]);
        $paiements = Paiement::factory()->count(8)->create();

        $response = $this->actingAs($this->admin)
            ->getJson('/api/statistics/global');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'total_services',
                'total_demandes',
                'total_paiements',
                'total_revenus',
                'utilisateurs_actifs'
            ]);
    }

    public function test_performance_metrics()
    {
        // Créer des données de test pour les métriques de performance
        $services = Service::factory()->count(3)->create(['user_id' => $this->professionnel->id]);
        $demandes = Demande::factory()->count(5)->create([
            'client_id' => $this->client->id,
            'statut' => 'complete'
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/statistics/performance');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'taux_completion',
                'temps_moyen_traitement',
                'satisfaction_clients',
                'services_populaires'
            ]);
    }

    public function test_financial_reports()
    {
        // Créer des paiements de test
        $paiements = Paiement::factory()->count(5)->create([
            'statut' => 'complete',
            'montant' => 10000
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/statistics/financial');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'revenus_totaux',
                'revenus_par_periode',
                'revenus_par_service',
                'revenus_par_professionnel'
            ]);
    }

    public function test_professionnel_statistics()
    {
        // Créer des données spécifiques au professionnel
        $services = Service::factory()->count(3)->create(['user_id' => $this->professionnel->id]);
        $demandes = Demande::factory()->count(4)->create(['professionnel_id' => $this->professionnel->id]);
        $paiements = Paiement::factory()->count(3)->create(['professionnel_id' => $this->professionnel->id]);

        $response = $this->actingAs($this->professionnel)
            ->getJson('/api/statistics/professionnel');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'services_actifs',
                'demandes_en_cours',
                'revenus_totaux',
                'evaluation_moyenne'
            ]);
    }
} 