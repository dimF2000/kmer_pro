<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Service;
use App\Models\Demande;
use App\Models\Paiement;
use App\Models\Message;
use App\Models\Competence;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

class StatistiqueTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['type' => 'admin']);
        $this->professionnel = User::factory()->create(['type' => 'professionnel']);
        $this->client = User::factory()->create(['type' => 'client']);
    }

    public function test_admin_peut_voir_statistiques_globales()
    {
        // Créer des données de test
        $service = Service::factory()->create(['user_id' => $this->professionnel->id]);
        $demande = Demande::factory()->create([
            'service_id' => $service->id,
            'client_id' => $this->client->id,
            'statut' => 'acceptee'
        ]);
        $paiement = Paiement::factory()->create([
            'demande_id' => $demande->id,
            'montant' => 100000,
            'statut' => 'confirme'
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/statistiques');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'total_utilisateurs',
                'total_services',
                'total_demandes',
                'total_paiements',
                'chiffre_affaires_total',
                'statistiques_par_categorie' => [
                    '*' => [
                        'categorie',
                        'nombre_services',
                        'nombre_demandes'
                    ]
                ],
                'statistiques_par_zone' => [
                    '*' => [
                        'zone',
                        'nombre_services',
                        'nombre_demandes'
                    ]
                ]
            ]);
    }

    public function test_admin_peut_voir_statistiques_performances()
    {
        // Créer des données de test
        $service = Service::factory()->create(['user_id' => $this->professionnel->id]);
        $demande = Demande::factory()->create([
            'service_id' => $service->id,
            'client_id' => $this->client->id,
            'statut' => 'acceptee'
        ]);
        $paiement = Paiement::factory()->create([
            'demande_id' => $demande->id,
            'montant' => 100000,
            'statut' => 'confirme'
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/statistiques/performances');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'taux_acceptation',
                'taux_completion',
                'taux_satisfaction',
                'temps_moyen_reponse',
                'temps_moyen_completion'
            ]);
    }

    public function test_admin_peut_voir_statistiques_financieres()
    {
        // Créer des données de test
        $service = Service::factory()->create(['user_id' => $this->professionnel->id]);
        $demande = Demande::factory()->create([
            'service_id' => $service->id,
            'client_id' => $this->client->id,
            'statut' => 'acceptee'
        ]);
        $paiement = Paiement::factory()->create([
            'demande_id' => $demande->id,
            'montant' => 100000,
            'statut' => 'confirme'
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/statistiques/financieres');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'chiffre_affaires_total',
                'chiffre_affaires_mensuel',
                'commission_totale',
                'commission_mensuelle',
                'paiements_en_attente',
                'paiements_confirmes',
                'paiements_annules'
            ]);
    }

    public function test_professionnel_peut_voir_ses_statistiques()
    {
        // Créer des données de test
        $service = Service::factory()->create(['user_id' => $this->professionnel->id]);
        $demande = Demande::factory()->create([
            'service_id' => $service->id,
            'client_id' => $this->client->id,
            'statut' => 'acceptee'
        ]);
        $paiement = Paiement::factory()->create([
            'demande_id' => $demande->id,
            'montant' => 100000,
            'statut' => 'confirme'
        ]);

        $response = $this->actingAs($this->professionnel)
            ->getJson('/api/professionnel/statistiques');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'total_services',
                'total_demandes',
                'total_paiements',
                'chiffre_affaires_total',
                'taux_acceptation',
                'taux_satisfaction',
                'statistiques_par_service' => [
                    '*' => [
                        'service',
                        'nombre_demandes',
                        'chiffre_affaires'
                    ]
                ]
            ]);
    }

    public function test_admin_peut_voir_statistiques_utilisateurs()
    {
        // Créer des données de test
        User::factory()->count(5)->create(['type' => 'professionnel']);
        User::factory()->count(10)->create(['type' => 'client']);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/statistiques/utilisateurs');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'total_utilisateurs',
                'professionnels_actifs',
                'clients_actifs',
                'nouveaux_utilisateurs_mois',
                'utilisateurs_par_zone' => [
                    '*' => [
                        'zone',
                        'nombre_utilisateurs'
                    ]
                ]
            ]);
    }

    public function test_admin_peut_voir_statistiques_messages()
    {
        // Créer des données de test
        $service = Service::factory()->create(['user_id' => $this->professionnel->id]);
        Message::factory()->count(5)->create([
            'service_id' => $service->id,
            'expediteur_id' => $this->client->id,
            'destinataire_id' => $this->professionnel->id
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/statistiques/messages');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'total_messages',
                'messages_non_lus',
                'temps_moyen_reponse',
                'messages_par_jour' => [
                    '*' => [
                        'date',
                        'nombre_messages'
                    ]
                ]
            ]);
    }

    public function test_admin_peut_voir_statistiques_competences()
    {
        // Créer des données de test
        $competences = Competence::factory()->count(3)->create();
        $this->professionnel->competences()->attach($competences->pluck('id'));

        $response = $this->actingAs($this->admin)
            ->getJson('/api/admin/statistiques/competences');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'competences_populaires' => [
                    '*' => [
                        'competence',
                        'nombre_professionnels',
                        'nombre_services'
                    ]
                ],
                'competences_par_zone' => [
                    '*' => [
                        'zone',
                        'competences' => [
                            '*' => [
                                'competence',
                                'nombre_professionnels'
                            ]
                        ]
                    ]
                ]
            ]);
    }

    public function test_statistiques_globales()
    {
        $user = User::factory()->create(['type' => 'admin']);
        $this->actingAs($user);

        $response = $this->getJson('/api/statistics/global');
        $response->assertStatus(200)
            ->assertJsonStructure([
                'total_utilisateurs',
                'total_services',
                'total_demandes',
                'total_paiements',
                'chiffre_affaires',
                'taux_satisfaction'
            ]);
    }

    public function test_statistiques_performance()
    {
        $user = User::factory()->create(['type' => 'admin']);
        $this->actingAs($user);

        $response = $this->getJson('/api/statistics/performance');
        $response->assertStatus(200)
            ->assertJsonStructure([
                'taux_completion',
                'temps_moyen_traitement',
                'satisfaction_clients',
                'taux_acceptation'
            ]);
    }

    public function test_statistiques_financieres()
    {
        $user = User::factory()->create(['type' => 'admin']);
        $this->actingAs($user);

        $response = $this->getJson('/api/statistics/financial');
        $response->assertStatus(200)
            ->assertJsonStructure([
                'chiffre_affaires_total',
                'paiements_en_attente',
                'paiements_par_methode',
                'evolution_mensuelle'
            ]);
    }

    public function test_statistiques_professionnel()
    {
        $professionnel = User::factory()->create(['type' => 'professionnel']);
        $this->actingAs($professionnel);

        $response = $this->getJson('/api/statistics/professionnel');
        $response->assertStatus(200)
            ->assertJsonStructure([
                'total_services',
                'total_demandes',
                'note_moyenne',
                'chiffre_affaires'
            ]);
    }

    public function test_statistiques_utilisateurs()
    {
        $admin = User::factory()->create(['type' => 'admin']);
        $this->actingAs($admin);

        $response = $this->getJson('/api/admin/statistiques/utilisateurs');
        $response->assertStatus(200)
            ->assertJsonStructure([
                'total_utilisateurs',
                'professionnels_actifs',
                'clients_actifs',
                'repartition_geographique'
            ]);
    }

    public function test_statistiques_messages()
    {
        $admin = User::factory()->create(['type' => 'admin']);
        $this->actingAs($admin);

        $response = $this->getJson('/api/admin/statistiques/messages');
        $response->assertStatus(200)
            ->assertJsonStructure([
                'total_messages',
                'messages_non_lus',
                'conversations_actives',
                'temps_moyen_reponse'
            ]);
    }

    public function test_statistiques_competences()
    {
        $admin = User::factory()->create(['type' => 'admin']);
        $this->actingAs($admin);

        $response = $this->getJson('/api/admin/statistiques/competences');
        $response->assertStatus(200)
            ->assertJsonStructure([
                'competences_populaires'
            ]);
    }
} 