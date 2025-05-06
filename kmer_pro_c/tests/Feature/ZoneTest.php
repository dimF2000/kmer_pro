<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ZoneTest extends TestCase
{
    use RefreshDatabase;

    private $professionnel;
    private $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->professionnel = User::factory()->create(['type' => 'professionnel']);
        $this->service = Service::factory()->create([
            'user_id' => $this->professionnel->id,
            'zones_couvertes' => ['Yaoundé', 'Douala']
        ]);
    }

    public function test_professionnel_can_update_zones()
    {
        $newZones = ['Yaoundé', 'Douala', 'Kribi'];

        $response = $this->actingAs($this->professionnel)
            ->putJson("/api/services/{$this->service->id}/zones", [
                'zones' => $newZones
            ]);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'message' => 'Zones d\'intervention mises à jour avec succès'
            ]);

        $this->assertEquals($newZones, $this->service->fresh()->zones_couvertes);
    }

    public function test_professionnel_can_add_zone()
    {
        $response = $this->actingAs($this->professionnel)
            ->postJson("/api/services/{$this->service->id}/zones", [
                'zone' => 'Kribi'
            ]);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'message' => 'Zone ajoutée avec succès'
            ]);

        $zones = $this->service->fresh()->zones_couvertes;
        $this->assertCount(3, $zones);
        $this->assertContains('Kribi', $zones);
    }

    public function test_professionnel_can_remove_zone()
    {
        $response = $this->actingAs($this->professionnel)
            ->deleteJson("/api/services/{$this->service->id}/zones", [
                'zone' => 'Yaoundé'
            ]);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'message' => 'Zone retirée avec succès'
            ]);

        $zones = $this->service->fresh()->zones_couvertes;
        $this->assertCount(1, $zones);
        $this->assertNotContains('Yaoundé', $zones);
    }

    public function test_other_users_cannot_manage_zones()
    {
        $otherUser = User::factory()->create(['type' => 'professionnel']);

        $response = $this->actingAs($otherUser)
            ->putJson("/api/services/{$this->service->id}/zones", [
                'zones' => ['Yaoundé']
            ]);

        $response->assertStatus(403);
    }

    public function test_zone_validation()
    {
        $response = $this->actingAs($this->professionnel)
            ->putJson("/api/services/{$this->service->id}/zones", [
                'zones' => ['']
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['zones.0']);
    }

    public function test_zone_max_length()
    {
        $longZone = str_repeat('a', 101);

        $response = $this->actingAs($this->professionnel)
            ->putJson("/api/services/{$this->service->id}/zones", [
                'zones' => [$longZone]
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['zones.0']);
    }
} 