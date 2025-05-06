<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Service;
use App\Models\Categorie;
use App\Models\Zone;
use App\Models\Competence;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

class ServiceSearchTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();

        // Créer des données de test
        $this->categorie = Categorie::factory()->create();
        $this->zone = Zone::factory()->create();
        $this->competence = Competence::factory()->create();
        
        $this->professionnel = User::factory()->create([
            'type' => 'professionnel',
            'nom' => 'Doe',
            'prenom' => 'John'
        ]);
        
        // Créer plusieurs services avec différentes caractéristiques
        $this->service1 = Service::factory()->create([
            'user_id' => $this->professionnel->id,
            'categorie_id' => $this->categorie->id,
            'titre' => 'Service de plomberie',
            'description' => 'Installation et réparation de plomberie',
            'prix' => 5000,
            'disponible' => true
        ]);

        $this->service2 = Service::factory()->create([
            'user_id' => $this->professionnel->id,
            'categorie_id' => $this->categorie->id,
            'titre' => 'Service d\'électricité',
            'description' => 'Installation électrique',
            'prix' => 10000,
            'disponible' => true
        ]);

        // Attacher les zones et compétences
        $this->service1->zones()->attach($this->zone->id);
        $this->service1->competences()->attach($this->competence->id);
    }

    public function test_peut_rechercher_par_mot_cle_dans_titre()
    {
        $response = $this->getJson('/api/services/search?q=plomberie');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.titre', 'Service de plomberie');
    }

    public function test_peut_rechercher_par_mot_cle_dans_description()
    {
        $response = $this->getJson('/api/services/search?q=installation');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_peut_rechercher_par_nom_professionnel()
    {
        $response = $this->getJson('/api/services/search?q=Doe');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_peut_filtrer_par_categorie()
    {
        $response = $this->getJson("/api/services/search?categorie_id={$this->categorie->id}");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_peut_filtrer_par_zone()
    {
        $response = $this->getJson("/api/services/search?zone_id={$this->zone->id}");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.titre', 'Service de plomberie');
    }

    public function test_peut_filtrer_par_prix_min()
    {
        $response = $this->getJson('/api/services/search?prix_min=6000');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.titre', 'Service d\'électricité');
    }

    public function test_peut_filtrer_par_prix_max()
    {
        $response = $this->getJson('/api/services/search?prix_max=6000');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.titre', 'Service de plomberie');
    }

    public function test_peut_filtrer_par_prix_intervalle()
    {
        $response = $this->getJson('/api/services/search?prix_min=6000&prix_max=12000');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.titre', 'Service d\'électricité');
    }

    public function test_peut_filtrer_par_competences()
    {
        $response = $this->getJson("/api/services/search?competences[]={$this->competence->id}");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.titre', 'Service de plomberie');
    }

    public function test_peut_filtrer_par_disponibilite()
    {
        $this->service1->update(['disponible' => false]);

        $response = $this->getJson('/api/services/search?disponible=true');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.titre', 'Service d\'électricité');
    }

    public function test_peut_trier_par_prix_ascendant()
    {
        $response = $this->getJson('/api/services/search?sort_by=prix&sort_direction=asc');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.titre', 'Service de plomberie')
            ->assertJsonPath('data.1.titre', 'Service d\'électricité');
    }

    public function test_peut_trier_par_prix_descendant()
    {
        $response = $this->getJson('/api/services/search?sort_by=prix&sort_direction=desc');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.titre', 'Service d\'électricité')
            ->assertJsonPath('data.1.titre', 'Service de plomberie');
    }

    public function test_peut_paginer_les_resultats()
    {
        $response = $this->getJson('/api/services/search?per_page=1');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonStructure([
                'data',
                'current_page',
                'per_page',
                'total'
            ]);
    }

    public function test_peut_combiner_plusieurs_filtres()
    {
        $response = $this->getJson("/api/services/search?categorie_id={$this->categorie->id}&prix_min=6000&zone_id={$this->zone->id}");

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }

    public function test_retourne_erreur_pour_parametres_invalides()
    {
        $response = $this->getJson('/api/services/search?prix_min=invalid');

        $response->assertStatus(422);
    }
} 