<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Service;
use App\Models\Categorie;
use App\Models\Competence;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceTest extends TestCase
{
    use RefreshDatabase;

    private $professionnel;
    private $client;
    private $service;
    private $categorie;
    private $competence;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Créer un professionnel et un client pour les tests
        $this->professionnel = User::factory()->create(['type' => 'professionnel']);
        $this->client = User::factory()->create(['type' => 'client']);
        $this->service = Service::factory()->create([
            'user_id' => $this->professionnel->id,
            'disponibilite' => true
        ]);
        $this->categorie = Categorie::factory()->create();
        $this->competence = Competence::factory()->create();
    }

    public function test_service_creation()
    {
        $serviceData = [
            'titre' => 'Service de Test',
            'description' => 'Description du service de test',
            'prix' => 50000,
            'categorie_id' => $this->categorie->id,
            'competences' => [$this->competence->id],
            'disponibilite' => 'immediate',
            'duree_estimee' => '2 jours'
        ];

        $response = $this->actingAs($this->professionnel)
            ->postJson('/api/services', $serviceData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'id',
                'titre',
                'description',
                'prix',
                'categorie_id',
                'user_id',
                'created_at',
                'updated_at'
            ]);

        $this->assertDatabaseHas('services', [
            'titre' => 'Service de Test',
            'user_id' => $this->professionnel->id
        ]);
    }

    public function test_service_update()
    {
        $service = Service::factory()->create(['user_id' => $this->professionnel->id]);

        $updateData = [
            'titre' => 'Service Modifié',
            'prix' => 75000
        ];

        $response = $this->actingAs($this->professionnel)
            ->putJson("/api/services/{$service->id}", $updateData);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'titre' => 'Service Modifié',
                'prix' => 75000
            ]);
    }

    public function test_service_search()
    {
        // Créer plusieurs services avec différentes caractéristiques
        Service::factory()->count(3)->create([
            'user_id' => $this->professionnel->id,
            'categorie_id' => $this->categorie->id
        ]);

        $response = $this->actingAs($this->client)
            ->getJson('/api/services/search?categorie=' . $this->categorie->id);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'titre',
                        'description',
                        'prix',
                        'categorie_id',
                        'user_id'
                    ]
                ]
            ]);
    }

    public function test_service_filtering()
    {
        // Créer des services avec différents prix
        Service::factory()->create([
            'user_id' => $this->professionnel->id,
            'prix' => 30000
        ]);
        Service::factory()->create([
            'user_id' => $this->professionnel->id,
            'prix' => 50000
        ]);

        $response = $this->actingAs($this->client)
            ->getJson('/api/services/search?prix_min=40000&prix_max=60000');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_service_deletion()
    {
        $service = Service::factory()->create(['user_id' => $this->professionnel->id]);

        $response = $this->actingAs($this->professionnel)
            ->deleteJson("/api/services/{$service->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('services', ['id' => $service->id]);
    }

    public function test_client_cannot_create_service()
    {
        $serviceData = [
            'titre' => 'Service de Test',
            'description' => 'Description du service',
            'prix' => 50000
        ];

        $response = $this->actingAs($this->client)
            ->postJson('/api/services', $serviceData);

        $response->assertStatus(403);
    }

    public function test_can_list_services()
    {
        // Créer quelques services
        Service::factory()->count(3)->create([
            'user_id' => $this->professionnel->id
        ]);

        $response = $this->getJson('/api/services');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'titre',
                        'categorie',
                        'description',
                        'prix',
                        'unite_temps',
                        'duree_estimee',
                        'disponible',
                        'disponibilite',
                        'zones_couvertes',
                        'competences',
                        'galerie',
                        'user_id'
                    ]
                ]
            ]);
    }

    public function test_professionnel_can_update_own_service()
    {
        $service = Service::factory()->create([
            'user_id' => $this->professionnel->id
        ]);

        $updateData = [
            'titre' => 'Service mis à jour',
            'prix' => 2000,
            'zones_couvertes' => ['Douala', 'Kribi']
        ];

        $response = $this->actingAs($this->professionnel)
            ->putJson("/api/services/{$service->id}", $updateData);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'titre' => 'Service mis à jour',
                'prix' => '2000.00',
                'zones_couvertes' => ['Douala', 'Kribi']
            ]);
    }

    public function test_professionnel_can_delete_own_service()
    {
        $service = Service::factory()->create([
            'user_id' => $this->professionnel->id
        ]);

        $response = $this->actingAs($this->professionnel)
            ->deleteJson("/api/services/{$service->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('services', ['id' => $service->id]);
    }

    public function test_professionnel_can_toggle_service_availability()
    {
        $response = $this->actingAs($this->professionnel)
            ->putJson("/api/services/{$this->service->id}/toggle-availability");

        $response->assertStatus(200)
            ->assertJsonFragment([
                'message' => 'Service marqué comme indisponible'
            ]);

        $this->assertFalse($this->service->fresh()->disponibilite);

        // Toggle back to available
        $response = $this->actingAs($this->professionnel)
            ->putJson("/api/services/{$this->service->id}/toggle-availability");

        $response->assertStatus(200)
            ->assertJsonFragment([
                'message' => 'Service marqué comme disponible'
            ]);

        $this->assertTrue($this->service->fresh()->disponibilite);
    }

    public function test_other_users_cannot_toggle_service_availability()
    {
        $otherUser = User::factory()->create(['type' => 'professionnel']);

        $response = $this->actingAs($otherUser)
            ->putJson("/api/services/{$this->service->id}/toggle-availability");

        $response->assertStatus(403);
    }

    public function test_can_get_services_by_user()
    {
        // Create additional services for the professional
        Service::factory()->count(2)->create([
            'user_id' => $this->professionnel->id
        ]);

        $response = $this->actingAs($this->professionnel)
            ->getJson("/api/users/{$this->professionnel->id}/services");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'titre',
                        'description',
                        'prix',
                        'disponibilite',
                        'category',
                        'evaluations'
                    ]
                ],
                'meta' => [
                    'current_page',
                    'last_page',
                    'per_page',
                    'total'
                ]
            ]);

        $this->assertCount(3, $response->json('data'));
    }

    public function test_services_are_sorted_by_latest()
    {
        $oldService = Service::factory()->create([
            'user_id' => $this->professionnel->id,
            'created_at' => now()->subDays(2)
        ]);

        $newService = Service::factory()->create([
            'user_id' => $this->professionnel->id,
            'created_at' => now()
        ]);

        $response = $this->actingAs($this->professionnel)
            ->getJson("/api/users/{$this->professionnel->id}/services");

        $services = $response->json('data');
        $this->assertEquals($newService->id, $services[0]['id']);
        $this->assertEquals($oldService->id, $services[2]['id']);
    }
} 