<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Service;
use App\Models\Disponibilite;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

class DisponibiliteTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->professionnel = User::factory()->create(['type' => 'professionnel']);
        $this->service = Service::factory()->create(['user_id' => $this->professionnel->id]);
    }

    public function test_peut_definir_disponibilites()
    {
        $disponibilites = [
            [
                'jour' => 'lundi',
                'debut' => '09:00',
                'fin' => '17:00'
            ],
            [
                'jour' => 'mardi',
                'debut' => '09:00',
                'fin' => '17:00'
            ]
        ];

        $response = $this->actingAs($this->professionnel)
            ->postJson("/api/services/{$this->service->id}/disponibilites", [
                'disponibilites' => $disponibilites
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'disponibilites' => [
                    '*' => [
                        'id',
                        'jour',
                        'debut',
                        'fin'
                    ]
                ]
            ]);

        $this->assertDatabaseCount('disponibilites', 2);
    }

    public function test_peut_verifier_disponibilite_creneau()
    {
        $date = Carbon::now()->next('monday');
        $disponibilite = Disponibilite::factory()->create([
            'service_id' => $this->service->id,
            'jour' => 'lundi',
            'debut' => '09:00',
            'fin' => '17:00'
        ]);

        $response = $this->getJson("/api/services/{$this->service->id}/disponibilite", [
            'date' => $date->format('Y-m-d'),
            'heure' => '10:00'
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'disponible' => true
            ]);
    }

    public function test_peut_reserver_creneau()
    {
        $date = Carbon::now()->next('monday');
        $disponibilite = Disponibilite::factory()->create([
            'service_id' => $this->service->id,
            'jour' => 'lundi',
            'debut' => '09:00',
            'fin' => '17:00'
        ]);

        $response = $this->actingAs($this->professionnel)
            ->postJson("/api/services/{$this->service->id}/reservations", [
                'date' => $date->format('Y-m-d'),
                'heure' => '10:00',
                'duree' => 60
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'reservation' => [
                    'id',
                    'date',
                    'heure',
                    'duree'
                ]
            ]);
    }

    public function test_ne_peut_pas_reserver_creneau_indisponible()
    {
        $date = Carbon::now()->next('monday');
        $disponibilite = Disponibilite::factory()->create([
            'service_id' => $this->service->id,
            'jour' => 'lundi',
            'debut' => '09:00',
            'fin' => '17:00'
        ]);

        // Créer une réservation existante
        $this->actingAs($this->professionnel)
            ->postJson("/api/services/{$this->service->id}/reservations", [
                'date' => $date->format('Y-m-d'),
                'heure' => '10:00',
                'duree' => 60
            ]);

        // Essayer de réserver le même créneau
        $response = $this->actingAs($this->professionnel)
            ->postJson("/api/services/{$this->service->id}/reservations", [
                'date' => $date->format('Y-m-d'),
                'heure' => '10:00',
                'duree' => 60
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['heure']);
    }

    public function test_peut_voir_creneaux_disponibles()
    {
        $date = Carbon::now()->next('monday');
        $disponibilite = Disponibilite::factory()->create([
            'service_id' => $this->service->id,
            'jour' => 'lundi',
            'debut' => '09:00',
            'fin' => '17:00'
        ]);

        $response = $this->getJson("/api/services/{$this->service->id}/creneaux", [
            'date' => $date->format('Y-m-d')
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'creneaux' => [
                    '*' => [
                        'heure',
                        'disponible'
                    ]
                ]
            ]);
    }

    public function test_peut_annuler_reservation()
    {
        $date = Carbon::now()->next('monday');
        $disponibilite = Disponibilite::factory()->create([
            'service_id' => $this->service->id,
            'jour' => 'lundi',
            'debut' => '09:00',
            'fin' => '17:00'
        ]);

        // Créer une réservation
        $reservation = $this->actingAs($this->professionnel)
            ->postJson("/api/services/{$this->service->id}/reservations", [
                'date' => $date->format('Y-m-d'),
                'heure' => '10:00',
                'duree' => 60
            ])->json('reservation');

        // Annuler la réservation
        $response = $this->actingAs($this->professionnel)
            ->deleteJson("/api/reservations/{$reservation['id']}");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Réservation annulée avec succès'
            ]);
    }

    public function test_validation_creneaux_chevauchement()
    {
        $disponibilites = [
            [
                'jour' => 'lundi',
                'debut' => '09:00',
                'fin' => '17:00'
            ],
            [
                'jour' => 'lundi',
                'debut' => '16:00',
                'fin' => '18:00'
            ]
        ];

        $response = $this->actingAs($this->professionnel)
            ->postJson("/api/services/{$this->service->id}/disponibilites", [
                'disponibilites' => $disponibilites
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['disponibilites']);
    }
} 