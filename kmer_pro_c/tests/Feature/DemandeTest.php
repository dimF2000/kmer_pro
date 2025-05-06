<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Service;
use App\Models\Demande;
use App\Models\Paiement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemandeTest extends TestCase
{
    use RefreshDatabase;

    private $client;
    private $professionnel;
    private $service;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->client = User::factory()->create(['type' => 'client']);
        $this->professionnel = User::factory()->create(['type' => 'professionnel']);
        $this->service = Service::factory()->create([
            'user_id' => $this->professionnel->id
        ]);
    }

    public function test_client_can_create_demande()
    {
        $demandeData = [
            'service_id' => $this->service->id,
            'description' => 'Je souhaite réserver ce service',
            'date_debut_souhaitee' => now()->addDays(2)->format('Y-m-d'),
            'date_fin_souhaitee' => now()->addDays(4)->format('Y-m-d'),
            'adresse_intervention' => '123 Rue Test, Douala',
            'budget_max' => 50000
        ];

        $response = $this->actingAs($this->client)
            ->postJson('/api/demandes', $demandeData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'demande' => [
                    'id',
                    'user_id',
                    'service_id',
                    'description',
                    'date_debut_souhaitee',
                    'date_fin_souhaitee',
                    'adresse_intervention',
                    'budget_max',
                    'statut'
                ]
            ]);

        $this->assertDatabaseHas('demandes', [
            'user_id' => $this->client->id,
            'service_id' => $this->service->id,
            'statut' => 'en_attente'
        ]);
    }

    public function test_professionnel_cannot_create_demande()
    {
        $demandeData = [
            'service_id' => $this->service->id,
            'description' => 'Test demande',
            'date_debut_souhaitee' => now()->addDays(2)->format('Y-m-d'),
            'date_fin_souhaitee' => now()->addDays(4)->format('Y-m-d'),
            'adresse_intervention' => '123 Rue Test, Douala',
            'budget_max' => 50000
        ];

        $response = $this->actingAs($this->professionnel)
            ->postJson('/api/demandes', $demandeData);

        $response->assertStatus(403);
    }

    public function test_can_list_demandes()
    {
        Demande::factory()->count(3)->create([
            'user_id' => $this->client->id,
            'service_id' => $this->service->id
        ]);

        $response = $this->actingAs($this->client)
            ->getJson('/api/demandes');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'user_id',
                        'service_id',
                        'description',
                        'date_debut_souhaitee',
                        'date_fin_souhaitee',
                        'adresse_intervention',
                        'budget_max',
                        'statut'
                    ]
                ]
            ]);
    }

    public function test_client_can_view_own_demande()
    {
        $demande = Demande::factory()->create([
            'user_id' => $this->client->id,
            'service_id' => $this->service->id
        ]);

        $response = $this->actingAs($this->client)
            ->getJson("/api/demandes/{$demande->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'demande' => [
                    'id',
                    'user_id',
                    'service_id',
                    'description',
                    'date_debut_souhaitee',
                    'date_fin_souhaitee',
                    'adresse_intervention',
                    'budget_max',
                    'statut'
                ]
            ]);
    }

    public function test_professionnel_can_view_received_demande()
    {
        $demande = Demande::factory()->create([
            'user_id' => $this->client->id,
            'service_id' => $this->service->id
        ]);

        $response = $this->actingAs($this->professionnel)
            ->getJson("/api/demandes/{$demande->id}");

        $response->assertStatus(200);
    }

    public function test_professionnel_can_update_demande_status()
    {
        $demande = Demande::factory()->create([
            'user_id' => $this->client->id,
            'service_id' => $this->service->id,
            'statut' => 'en_attente'
        ]);

        $updateData = [
            'statut' => 'acceptee',
            'commentaire' => 'Je peux commencer dès demain'
        ];

        $response = $this->actingAs($this->professionnel)
            ->putJson("/api/demandes/{$demande->id}", $updateData);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'statut' => 'acceptee',
                'commentaire' => 'Je peux commencer dès demain'
            ]);
    }

    public function test_client_can_view_my_demandes()
    {
        Demande::factory()->count(3)->create([
            'user_id' => $this->client->id,
            'service_id' => $this->service->id
        ]);

        $response = $this->actingAs($this->client)
            ->getJson('/api/my-demandes');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'service_id',
                        'statut'
                    ]
                ]
            ]);
    }

    public function test_professionnel_can_view_received_demandes()
    {
        Demande::factory()->count(3)->create([
            'user_id' => $this->client->id,
            'service_id' => $this->service->id
        ]);

        $response = $this->actingAs($this->professionnel)
            ->getJson('/api/demandes-reçues');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'user_id',
                        'statut'
                    ]
                ]
            ]);
    }

    public function test_demande_creation()
    {
        $demandeData = [
            'service_id' => $this->service->id,
            'description' => 'Description de la demande',
            'date_souhaitee' => now()->addDays(2)->format('Y-m-d'),
            'budget' => 50000
        ];

        $response = $this->actingAs($this->client)
            ->postJson('/api/demandes', $demandeData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'id',
                'service_id',
                'client_id',
                'description',
                'date_souhaitee',
                'budget',
                'statut',
                'created_at',
                'updated_at'
            ]);

        $this->assertDatabaseHas('demandes', [
            'service_id' => $this->service->id,
            'client_id' => $this->client->id,
            'statut' => 'en_attente'
        ]);
    }

    public function test_demande_lifecycle()
    {
        // Créer une demande
        $demande = Demande::factory()->create([
            'client_id' => $this->client->id,
            'service_id' => $this->service->id,
            'statut' => 'en_attente'
        ]);

        // Accepter la demande
        $response = $this->actingAs($this->professionnel)
            ->putJson("/api/demandes/{$demande->id}/accepter");

        $response->assertStatus(200);
        $this->assertEquals('acceptee', $demande->fresh()->statut);

        // Démarrer le service
        $response = $this->actingAs($this->professionnel)
            ->putJson("/api/demandes/{$demande->id}/demarrer");

        $response->assertStatus(200);
        $this->assertEquals('en_cours', $demande->fresh()->statut);

        // Terminer le service
        $response = $this->actingAs($this->professionnel)
            ->putJson("/api/demandes/{$demande->id}/terminer");

        $response->assertStatus(200);
        $this->assertEquals('terminee', $demande->fresh()->statut);
    }

    public function test_demande_cancellation()
    {
        $demande = Demande::factory()->create([
            'client_id' => $this->client->id,
            'service_id' => $this->service->id,
            'statut' => 'en_attente'
        ]);

        $response = $this->actingAs($this->client)
            ->putJson("/api/demandes/{$demande->id}/annuler", [
                'raison' => 'Changement de plan'
            ]);

        $response->assertStatus(200);
        $this->assertEquals('annulee', $demande->fresh()->statut);
    }

    public function test_demande_payment()
    {
        $demande = Demande::factory()->create([
            'client_id' => $this->client->id,
            'service_id' => $this->service->id,
            'statut' => 'acceptee'
        ]);

        $paiementData = [
            'montant' => 50000,
            'methode' => 'mobile_money',
            'reference' => 'PAY-' . uniqid()
        ];

        $response = $this->actingAs($this->client)
            ->postJson("/api/demandes/{$demande->id}/paiement", $paiementData);

        $response->assertStatus(200);
        $this->assertDatabaseHas('paiements', [
            'demande_id' => $demande->id,
            'montant' => 50000,
            'methode' => 'mobile_money'
        ]);
    }

    public function test_demande_evaluation()
    {
        $demande = Demande::factory()->create([
            'client_id' => $this->client->id,
            'service_id' => $this->service->id,
            'statut' => 'terminee'
        ]);

        $evaluationData = [
            'note' => 5,
            'commentaire' => 'Excellent service !'
        ];

        $response = $this->actingAs($this->client)
            ->postJson("/api/demandes/{$demande->id}/evaluation", $evaluationData);

        $response->assertStatus(200);
        $this->assertDatabaseHas('evaluations', [
            'demande_id' => $demande->id,
            'note' => 5
        ]);
    }

    public function test_demande_listing()
    {
        // Créer plusieurs demandes
        Demande::factory()->count(3)->create([
            'client_id' => $this->client->id,
            'service_id' => $this->service->id
        ]);

        $response = $this->actingAs($this->client)
            ->getJson('/api/demandes');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'service_id',
                        'client_id',
                        'description',
                        'statut',
                        'created_at'
                    ]
                ]
            ]);
    }
} 