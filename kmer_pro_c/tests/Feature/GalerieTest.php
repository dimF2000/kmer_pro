<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GalerieTest extends TestCase
{
    use RefreshDatabase;

    private $professionnel;
    private $service;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->professionnel = User::factory()->create(['type' => 'professionnel']);
        $this->service = Service::factory()->create([
            'user_id' => $this->professionnel->id,
            'galerie' => []
        ]);
    }

    public function test_professionnel_can_upload_photo()
    {
        $file = UploadedFile::fake()->image('photo.jpg');

        $response = $this->actingAs($this->professionnel)
            ->postJson("/api/services/{$this->service->id}/galerie", [
                'photo' => $file
            ]);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'message' => 'Photo ajoutée avec succès'
            ]);

        Storage::disk('public')->assertExists('services/' . $file->hashName());
        $this->assertCount(1, $this->service->fresh()->galerie);
    }

    public function test_professionnel_can_remove_photo()
    {
        // Ajouter une photo d'abord
        $file = UploadedFile::fake()->image('photo.jpg');
        $path = $file->store('services', 'public');
        $this->service->galerie = [$path];
        $this->service->save();

        $response = $this->actingAs($this->professionnel)
            ->deleteJson("/api/services/{$this->service->id}/galerie", [
                'photo_path' => $path
            ]);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'message' => 'Photo supprimée avec succès'
            ]);

        Storage::disk('public')->assertMissing($path);
        $this->assertEmpty($this->service->fresh()->galerie);
    }

    public function test_professionnel_can_reorder_photos()
    {
        // Ajouter plusieurs photos
        $paths = [];
        for ($i = 0; $i < 3; $i++) {
            $file = UploadedFile::fake()->image("photo{$i}.jpg");
            $paths[] = $file->store('services', 'public');
        }
        $this->service->galerie = $paths;
        $this->service->save();

        // Réorganiser les photos
        $reorderedPaths = array_reverse($paths);
        $response = $this->actingAs($this->professionnel)
            ->putJson("/api/services/{$this->service->id}/galerie/reorder", [
                'photos' => $reorderedPaths
            ]);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'message' => 'Ordre des photos mis à jour avec succès'
            ]);

        $this->assertEquals($reorderedPaths, $this->service->fresh()->galerie);
    }

    public function test_cannot_exceed_photo_limit()
    {
        // Ajouter 10 photos
        $paths = [];
        for ($i = 0; $i < 10; $i++) {
            $file = UploadedFile::fake()->image("photo{$i}.jpg");
            $paths[] = $file->store('services', 'public');
        }
        $this->service->galerie = $paths;
        $this->service->save();

        // Essayer d'ajouter une 11ème photo
        $file = UploadedFile::fake()->image('photo11.jpg');
        $response = $this->actingAs($this->professionnel)
            ->postJson("/api/services/{$this->service->id}/galerie", [
                'photo' => $file
            ]);

        $response->assertStatus(422)
            ->assertJsonFragment([
                'message' => 'La galerie ne peut pas contenir plus de 10 photos'
            ]);
    }

    public function test_photo_validation()
    {
        $file = UploadedFile::fake()->create('document.pdf', 100);

        $response = $this->actingAs($this->professionnel)
            ->postJson("/api/services/{$this->service->id}/galerie", [
                'photo' => $file
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['photo']);
    }

    public function test_other_users_cannot_manage_galerie()
    {
        $otherUser = User::factory()->create(['type' => 'professionnel']);

        $file = UploadedFile::fake()->image('photo.jpg');
        $response = $this->actingAs($otherUser)
            ->postJson("/api/services/{$this->service->id}/galerie", [
                'photo' => $file
            ]);

        $response->assertStatus(403);
    }
} 