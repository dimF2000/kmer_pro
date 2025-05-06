<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Document;
use App\Models\Competence;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

class ValidationProfessionnelTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['type' => 'professionnel']);
        Storage::fake('public');
    }

    public function test_soumission_document()
    {
        $this->actingAs($this->user);

        $file = UploadedFile::fake()->create('document.pdf', 100);

        $response = $this->postJson('/api/professionnel/documents', [
            'document' => $file,
            'type' => 'cni',
            'numero' => '123456'
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'document' => [
                    'id',
                    'user_id',
                    'type',
                    'numero',
                    'chemin',
                    'statut',
                    'created_at',
                    'updated_at'
                ]
            ]);

        Storage::disk('public')->assertExists('documents/' . $file->hashName());
    }

    public function test_soumission_competences()
    {
        $this->actingAs($this->user);

        $competences = Competence::factory()->count(5)->create();
        $competenceIds = $competences->pluck('id')->take(5)->toArray();

        $response = $this->postJson('/api/professionnel/competences', [
            'competences' => $competenceIds
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'competences'
            ]);

        $this->assertCount(5, $this->user->competences);
    }

    public function test_validation_document()
    {
        $admin = User::factory()->create(['type' => 'admin']);
        $document = Document::factory()->create([
            'user_id' => $this->user->id,
            'statut' => 'en_attente'
        ]);

        $this->actingAs($admin);

        $response = $this->putJson("/api/admin/documents/{$document->id}/valider", [
            'statut' => 'valide',
            'commentaire' => 'Document validé'
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'document'
            ]);

        $this->assertDatabaseHas('documents', [
            'id' => $document->id,
            'statut' => 'valide',
            'commentaire' => 'Document validé'
        ]);
    }

    public function test_rejet_document()
    {
        $admin = User::factory()->create(['type' => 'admin']);
        $document = Document::factory()->create([
            'user_id' => $this->user->id,
            'statut' => 'en_attente'
        ]);

        $this->actingAs($admin);

        $response = $this->putJson("/api/admin/documents/{$document->id}/valider", [
            'statut' => 'rejete',
            'commentaire' => 'Document non conforme'
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'document'
            ]);

        $this->assertDatabaseHas('documents', [
            'id' => $document->id,
            'statut' => 'rejete',
            'commentaire' => 'Document non conforme'
        ]);
    }

    public function test_liste_documents()
    {
        $this->actingAs($this->user);
        Document::factory()->count(3)->create(['user_id' => $this->user->id]);

        $response = $this->getJson('/api/professionnel/documents');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'documents' => [
                    '*' => [
                        'id',
                        'type',
                        'numero',
                        'statut',
                        'commentaire',
                        'created_at'
                    ]
                ]
            ]);

        $this->assertCount(3, $response->json('documents'));
    }

    public function test_liste_competences()
    {
        $this->actingAs($this->user);
        $competences = Competence::factory()->count(5)->create();
        $this->user->competences()->attach($competences->pluck('id'));

        $response = $this->getJson('/api/professionnel/competences');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'competences' => [
                    '*' => [
                        'id',
                        'nom',
                        'description',
                        'categorie'
                    ]
                ]
            ]);

        $this->assertCount(5, $response->json('competences'));
    }

    public function test_voir_badges()
    {
        $this->actingAs($this->user);

        Document::factory()->create([
            'user_id' => $this->user->id,
            'type' => 'cni',
            'statut' => 'valide'
        ]);

        Document::factory()->create([
            'user_id' => $this->user->id,
            'type' => 'diplome',
            'statut' => 'valide'
        ]);

        $competences = Competence::factory()->count(3)->create();
        $this->user->competences()->attach($competences->pluck('id'));

        $response = $this->getJson('/api/professionnel/badges');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'badges' => [
                    '*' => [
                        'type',
                        'obtenu',
                        'description'
                    ]
                ]
            ]);
    }

    public function test_validation_format_document()
    {
        $professionnel = User::factory()->create(['type' => 'professionnel']);
        
        $response = $this->actingAs($professionnel)
            ->postJson('/api/professionnel/documents', [
                'type' => 'cni',
                'document' => UploadedFile::fake()->create('document.txt', 100),
                'numero' => '123456789'
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['document']);
    }

    public function test_validation_taille_document()
    {
        $professionnel = User::factory()->create(['type' => 'professionnel']);
        
        $response = $this->actingAs($professionnel)
            ->postJson('/api/professionnel/documents', [
                'type' => 'cni',
                'document' => UploadedFile::fake()->image('cni.jpg')->size(10240),
                'numero' => '123456789'
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['document']);
    }
} 