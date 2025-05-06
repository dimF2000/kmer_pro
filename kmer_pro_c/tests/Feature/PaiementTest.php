<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Service;
use App\Models\Demande;
use App\Models\Paiement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

class PaiementTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private $client;
    private $professionnel;
    private $service;
    private $demande;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = User::factory()->create(['type' => 'client']);
        $this->professionnel = User::factory()->create(['type' => 'professionnel']);
        $this->service = Service::factory()->create(['user_id' => $this->professionnel->id]);
        $this->demande = Demande::factory()->create([
            'client_id' => $this->client->id,
            'service_id' => $this->service->id,
            'statut' => 'acceptee'
        ]);
    }

    public function test_paiement_creation()
    {
        $paiementData = [
            'demande_id' => $this->demande->id,
            'montant' => 50000,
            'methode' => 'mobile_money',
            'reference' => 'PAY-' . uniqid()
        ];

        $response = $this->actingAs($this->client)
            ->postJson('/api/paiements', $paiementData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'id',
                'demande_id',
                'client_id',
                'professionnel_id',
                'montant',
                'methode',
                'reference',
                'statut',
                'created_at',
                'updated_at'
            ]);

        $this->assertDatabaseHas('paiements', [
            'demande_id' => $this->demande->id,
            'montant' => 50000,
            'methode' => 'mobile_money'
        ]);
    }

    public function test_paiement_confirmation()
    {
        $paiement = Paiement::factory()->create([
            'demande_id' => $this->demande->id,
            'client_id' => $this->client->id,
            'professionnel_id' => $this->professionnel->id,
            'statut' => 'en_attente'
        ]);

        $response = $this->actingAs($this->professionnel)
            ->putJson("/api/paiements/{$paiement->id}/confirmer");

        $response->assertStatus(200);
        $this->assertEquals('confirme', $paiement->fresh()->statut);
    }

    public function test_paiement_annulation()
    {
        $paiement = Paiement::factory()->create([
            'demande_id' => $this->demande->id,
            'client_id' => $this->client->id,
            'professionnel_id' => $this->professionnel->id,
            'statut' => 'en_attente'
        ]);

        $response = $this->actingAs($this->client)
            ->putJson("/api/paiements/{$paiement->id}/annuler", [
                'raison' => 'Changement de plan'
            ]);

        $response->assertStatus(200);
        $this->assertEquals('annule', $paiement->fresh()->statut);
    }

    public function test_paiement_listing()
    {
        Paiement::factory()->count(3)->create([
            'client_id' => $this->client->id,
            'professionnel_id' => $this->professionnel->id
        ]);

        $response = $this->actingAs($this->client)
            ->getJson('/api/paiements');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'demande_id',
                        'montant',
                        'methode',
                        'statut',
                        'created_at'
                    ]
                ]
            ]);
    }

    public function test_paiement_details()
    {
        $paiement = Paiement::factory()->create([
            'demande_id' => $this->demande->id,
            'client_id' => $this->client->id,
            'professionnel_id' => $this->professionnel->id
        ]);

        $response = $this->actingAs($this->client)
            ->getJson("/api/paiements/{$paiement->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'id',
                'demande_id',
                'client_id',
                'professionnel_id',
                'montant',
                'methode',
                'reference',
                'statut',
                'date_paiement',
                'date_confirmation'
            ]);
    }

    public function test_paiement_validation()
    {
        $paiementData = [
            'demande_id' => $this->demande->id,
            'montant' => -1000, // Montant invalide
            'methode' => 'methode_invalide'
        ];

        $response = $this->actingAs($this->client)
            ->postJson('/api/paiements', $paiementData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['montant', 'methode']);
    }

    public function test_client_peut_initier_paiement()
    {
        $response = $this->actingAs($this->client)
            ->postJson("/api/demandes/{$this->demande->id}/paiement", [
                'methode_paiement' => 'mobile_money',
                'montant' => 50000,
                'devise' => 'XAF'
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'demande_id',
                    'client_id',
                    'professionnel_id',
                    'montant',
                    'devise',
                    'statut',
                    'methode_paiement',
                    'reference_paiement'
                ]
            ]);

        $this->assertDatabaseHas('paiements', [
            'demande_id' => $this->demande->id,
            'client_id' => $this->client->id,
            'professionnel_id' => $this->professionnel->id,
            'montant' => 50000,
            'devise' => 'XAF',
            'methode_paiement' => 'mobile_money'
        ]);
    }

    public function test_professionnel_peut_confirmer_paiement()
    {
        $paiement = Paiement::factory()->create([
            'demande_id' => $this->demande->id,
            'client_id' => $this->client->id,
            'professionnel_id' => $this->professionnel->id,
            'statut' => 'en_attente'
        ]);

        $response = $this->actingAs($this->professionnel)
            ->postJson("/api/paiements/{$paiement->id}/confirmer", [
                'confirmation' => true,
                'commentaire' => 'Paiement reçu'
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'statut',
                    'date_confirmation'
                ]
            ]);

        $this->assertDatabaseHas('paiements', [
            'id' => $paiement->id,
            'statut' => 'complete'
        ]);

        $this->assertDatabaseHas('demandes', [
            'id' => $this->demande->id,
            'statut' => 'en_cours'
        ]);
    }

    public function test_client_peut_voir_historique_paiements()
    {
        Paiement::factory()->count(3)->create([
            'client_id' => $this->client->id,
            'professionnel_id' => $this->professionnel->id
        ]);

        $response = $this->actingAs($this->client)
            ->getJson('/api/paiements');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'demande_id',
                        'client_id',
                        'professionnel_id',
                        'montant',
                        'devise',
                        'statut'
                    ]
                ]
            ]);
    }

    public function test_client_peut_voir_details_paiement()
    {
        $paiement = Paiement::factory()->create([
            'client_id' => $this->client->id,
            'professionnel_id' => $this->professionnel->id
        ]);

        $response = $this->actingAs($this->client)
            ->getJson("/api/paiements/{$paiement->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'id',
                'demande_id',
                'client_id',
                'professionnel_id',
                'montant',
                'devise',
                'statut',
                'methode_paiement',
                'reference_paiement'
            ]);
    }

    public function test_client_peut_annuler_paiement()
    {
        $paiement = Paiement::factory()->create([
            'client_id' => $this->client->id,
            'professionnel_id' => $this->professionnel->id,
            'statut' => 'en_attente'
        ]);

        $response = $this->actingAs($this->client)
            ->deleteJson("/api/paiements/{$paiement->id}");

        $response->assertStatus(200);

        $this->assertDatabaseHas('paiements', [
            'id' => $paiement->id,
            'statut' => 'annule'
        ]);
    }

    public function test_validation_initiation_paiement()
    {
        $response = $this->actingAs($this->client)
            ->postJson("/api/demandes/{$this->demande->id}/paiement", [
                'methode_paiement' => 'invalid_method',
                'montant' => -100,
                'devise' => 'INVALID'
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['methode_paiement', 'montant', 'devise']);
    }

    public function test_non_autorise_voir_paiement_autre_utilisateur()
    {
        $autreClient = User::factory()->create(['type' => 'client']);
        $paiement = Paiement::factory()->create([
            'client_id' => $this->client->id,
            'professionnel_id' => $this->professionnel->id
        ]);

        $response = $this->actingAs($autreClient)
            ->getJson("/api/paiements/{$paiement->id}");

        $response->assertStatus(403);
    }

    public function test_non_autorise_annuler_paiement_autre_utilisateur()
    {
        $autreClient = User::factory()->create(['type' => 'client']);
        $paiement = Paiement::factory()->create([
            'client_id' => $this->client->id,
            'professionnel_id' => $this->professionnel->id,
            'statut' => 'en_attente'
        ]);

        $response = $this->actingAs($autreClient)
            ->deleteJson("/api/paiements/{$paiement->id}");

        $response->assertStatus(403);
    }

    public function test_non_autorise_confirmer_paiement_autre_professionnel()
    {
        $autreProfessionnel = User::factory()->create(['type' => 'professionnel']);
        $paiement = Paiement::factory()->create([
            'client_id' => $this->client->id,
            'professionnel_id' => $this->professionnel->id,
            'statut' => 'en_attente'
        ]);

        $response = $this->actingAs($autreProfessionnel)
            ->postJson("/api/paiements/{$paiement->id}/confirmer", [
                'confirmation' => true
            ]);

        $response->assertStatus(403);
    }
} 