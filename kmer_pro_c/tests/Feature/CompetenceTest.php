<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompetenceTest extends TestCase
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
            'competences' => [
                [
                    'nom' => 'Plomberie',
                    'niveau' => 'expert',
                    'annees_experience' => 5
                ]
            ]
        ]);
    }

    public function test_professionnel_can_update_competences()
    {
        $newCompetences = [
            [
                'nom' => 'Électricité',
                'niveau' => 'intermédiaire',
                'annees_experience' => 3
            ],
            [
                'nom' => 'Maçonnerie',
                'niveau' => 'débutant',
                'annees_experience' => 1
            ]
        ];

        $response = $this->actingAs($this->professionnel)
            ->putJson("/api/services/{$this->service->id}/competences", [
                'competences' => $newCompetences
            ]);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'message' => 'Compétences mises à jour avec succès'
            ]);

        $this->assertEquals($newCompetences, $this->service->fresh()->competences);
    }

    public function test_professionnel_can_add_competence()
    {
        $newCompetence = [
            'nom' => 'Électricité',
            'niveau' => 'intermédiaire',
            'annees_experience' => 3
        ];

        $response = $this->actingAs($this->professionnel)
            ->postJson("/api/services/{$this->service->id}/competences", [
                'competence' => $newCompetence
            ]);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'message' => 'Compétence ajoutée avec succès'
            ]);

        $competences = $this->service->fresh()->competences;
        $this->assertCount(2, $competences);
        $this->assertContains($newCompetence, $competences);
    }

    public function test_professionnel_can_remove_competence()
    {
        $response = $this->actingAs($this->professionnel)
            ->deleteJson("/api/services/{$this->service->id}/competences", [
                'nom' => 'Plomberie'
            ]);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'message' => 'Compétence retirée avec succès'
            ]);

        $this->assertEmpty($this->service->fresh()->competences);
    }

    public function test_other_users_cannot_manage_competences()
    {
        $otherUser = User::factory()->create(['type' => 'professionnel']);

        $response = $this->actingAs($otherUser)
            ->putJson("/api/services/{$this->service->id}/competences", [
                'competences' => [
                    [
                        'nom' => 'Électricité',
                        'niveau' => 'intermédiaire',
                        'annees_experience' => 3
                    ]
                ]
            ]);

        $response->assertStatus(403);
    }

    public function test_competence_validation()
    {
        $response = $this->actingAs($this->professionnel)
            ->putJson("/api/services/{$this->service->id}/competences", [
                'competences' => [
                    [
                        'nom' => '',
                        'niveau' => 'invalid',
                        'annees_experience' => -1
                    ]
                ]
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'competences.0.nom',
                'competences.0.niveau',
                'competences.0.annees_experience'
            ]);
    }

    public function test_competence_max_length()
    {
        $longNom = str_repeat('a', 101);

        $response = $this->actingAs($this->professionnel)
            ->putJson("/api/services/{$this->service->id}/competences", [
                'competences' => [
                    [
                        'nom' => $longNom,
                        'niveau' => 'expert',
                        'annees_experience' => 5
                    ]
                ]
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['competences.0.nom']);
    }
} 