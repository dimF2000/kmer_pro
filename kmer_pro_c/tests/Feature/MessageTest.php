<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Message;
use App\Models\Demande;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class MessageTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private $client;
    private $professionnel;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');

        $this->client = User::factory()->create(['type' => 'client']);
        $this->professionnel = User::factory()->create(['type' => 'professionnel']);
    }

    public function test_peut_envoyer_message()
    {
        $response = $this->actingAs($this->client)
            ->postJson('/api/messages', [
                'destinataire_id' => $this->professionnel->id,
                'contenu' => 'Bonjour, je suis intéressé par vos services.'
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'expediteur_id',
                    'destinataire_id',
                    'contenu',
                    'lu'
                ]
            ]);

        $this->assertDatabaseHas('messages', [
            'expediteur_id' => $this->client->id,
            'destinataire_id' => $this->professionnel->id,
            'contenu' => 'Bonjour, je suis intéressé par vos services.'
        ]);
    }

    public function test_peut_envoyer_message_avec_pieces_jointes()
    {
        $file = UploadedFile::fake()->image('document.jpg');

        $response = $this->actingAs($this->client)
            ->postJson('/api/messages', [
                'destinataire_id' => $this->professionnel->id,
                'contenu' => 'Voici le document demandé',
                'pieces_jointes' => [$file]
            ]);

        $response->assertStatus(200);

        Storage::disk('local')->assertExists('messages/pieces_jointes/' . $file->hashName());

        $this->assertDatabaseHas('messages', [
            'expediteur_id' => $this->client->id,
            'destinataire_id' => $this->professionnel->id,
            'contenu' => 'Voici le document demandé'
        ]);
    }

    public function test_peut_voir_conversations()
    {
        // Créer quelques messages
        Message::factory()->count(3)->create([
            'expediteur_id' => $this->client->id,
            'destinataire_id' => $this->professionnel->id
        ]);

        $response = $this->actingAs($this->client)
            ->getJson('/api/conversations');

        $response->assertStatus(200)
            ->assertJsonStructure([
                '*' => [
                    'user',
                    'last_message',
                    'unread_count'
                ]
            ]);
    }

    public function test_peut_voir_conversation_avec_utilisateur()
    {
        // Créer quelques messages
        Message::factory()->count(5)->create([
            'expediteur_id' => $this->client->id,
            'destinataire_id' => $this->professionnel->id
        ]);

        $response = $this->actingAs($this->client)
            ->getJson("/api/conversations/{$this->professionnel->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'expediteur_id',
                        'destinataire_id',
                        'contenu',
                        'lu'
                    ]
                ]
            ]);
    }

    public function test_message_marque_comme_lu_automatiquement()
    {
        $message = Message::factory()->create([
            'expediteur_id' => $this->professionnel->id,
            'destinataire_id' => $this->client->id,
            'lu' => false
        ]);

        $response = $this->actingAs($this->client)
            ->getJson("/api/messages/{$message->id}");

        $response->assertStatus(200);

        $this->assertDatabaseHas('messages', [
            'id' => $message->id,
            'lu' => true
        ]);
    }

    public function test_peut_supprimer_message()
    {
        $message = Message::factory()->create([
            'expediteur_id' => $this->client->id,
            'destinataire_id' => $this->professionnel->id
        ]);

        $response = $this->actingAs($this->client)
            ->deleteJson("/api/messages/{$message->id}");

        $response->assertStatus(200);

        $this->assertSoftDeleted('messages', [
            'id' => $message->id
        ]);
    }

    public function test_ne_peut_pas_supprimer_message_autre_utilisateur()
    {
        $message = Message::factory()->create([
            'expediteur_id' => $this->professionnel->id,
            'destinataire_id' => $this->client->id
        ]);

        $response = $this->actingAs($this->client)
            ->deleteJson("/api/messages/{$message->id}");

        $response->assertStatus(403);
    }

    public function test_peut_marquer_tous_messages_comme_lus()
    {
        // Créer quelques messages non lus
        Message::factory()->count(3)->create([
            'expediteur_id' => $this->professionnel->id,
            'destinataire_id' => $this->client->id,
            'lu' => false
        ]);

        $response = $this->actingAs($this->client)
            ->postJson("/api/messages/marquer-tous-lus/{$this->professionnel->id}");

        $response->assertStatus(200);

        $this->assertDatabaseMissing('messages', [
            'expediteur_id' => $this->professionnel->id,
            'destinataire_id' => $this->client->id,
            'lu' => false
        ]);
    }

    public function test_validation_message()
    {
        $response = $this->actingAs($this->client)
            ->postJson('/api/messages', [
                'destinataire_id' => 999, // ID inexistant
                'contenu' => ''
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['destinataire_id', 'contenu']);
    }

    public function test_validation_pieces_jointes()
    {
        $file = UploadedFile::fake()->create('document.pdf', 6000); // 6MB

        $response = $this->actingAs($this->client)
            ->postJson('/api/messages', [
                'destinataire_id' => $this->professionnel->id,
                'contenu' => 'Test',
                'pieces_jointes' => [$file]
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['pieces_jointes.0']);
    }
} 