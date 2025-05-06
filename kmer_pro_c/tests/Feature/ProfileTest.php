<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    private $professionnel;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->professionnel = User::factory()->create(['type' => 'professionnel']);
    }

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response->assertOk();
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => $user->email,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_user_can_delete_their_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->delete('/profile', [
                'password' => 'password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertNull($user->fresh());
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->delete('/profile', [
                'password' => 'wrong-password',
            ]);

        $response
            ->assertSessionHasErrorsIn('userDeletion', 'password')
            ->assertRedirect('/profile');

        $this->assertNotNull($user->fresh());
    }

    public function test_professionnel_can_view_profile()
    {
        $response = $this->actingAs($this->professionnel)
            ->getJson('/api/profile');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'user' => [
                    'id',
                    'nom',
                    'email',
                    'telephone',
                    'type',
                    'adresse',
                    'ville',
                    'pays',
                    'description',
                    'competences',
                    'experience',
                    'diplomes',
                    'photo',
                    'statut'
                ]
            ]);
    }

    public function test_professionnel_can_update_profile()
    {
        $data = [
            'nom' => 'Nouveau Nom',
            'telephone' => '+237 612345678',
            'adresse' => 'Nouvelle Adresse',
            'ville' => 'Douala',
            'pays' => 'Cameroun',
            'description' => 'Nouvelle description'
        ];

        $response = $this->actingAs($this->professionnel)
            ->putJson('/api/profile', $data);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'message' => 'Profil mis à jour avec succès'
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $this->professionnel->id,
            'nom' => 'Nouveau Nom'
        ]);
    }

    public function test_professionnel_can_upload_photo()
    {
        $file = UploadedFile::fake()->image('photo.jpg');

        $response = $this->actingAs($this->professionnel)
            ->putJson('/api/profile', [
                'photo' => $file
            ]);

        $response->assertStatus(200);
        Storage::disk('public')->assertExists('profiles/' . $file->hashName());
    }

    public function test_professionnel_can_update_documents()
    {
        $file = UploadedFile::fake()->create('diplome.pdf', 100);

        $data = [
            'diplomes' => [
                [
                    'titre' => 'Diplôme en Informatique',
                    'institution' => 'Université de Douala',
                    'date_obtention' => '2020-06-15',
                    'document' => $file
                ]
            ]
        ];

        $response = $this->actingAs($this->professionnel)
            ->postJson('/api/profile/documents', $data);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'message' => 'Documents mis à jour avec succès'
            ]);

        Storage::disk('public')->assertExists('diplomes/' . $file->hashName());
    }

    public function test_professionnel_can_update_competences()
    {
        $data = [
            'competences' => [
                [
                    'nom' => 'Développement Web',
                    'niveau' => 'expert',
                    'annees_experience' => 5
                ],
                [
                    'nom' => 'Design UI/UX',
                    'niveau' => 'intermédiaire',
                    'annees_experience' => 3
                ]
            ]
        ];

        $response = $this->actingAs($this->professionnel)
            ->postJson('/api/profile/competences', $data);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'message' => 'Compétences mises à jour avec succès'
            ]);

        $this->professionnel->refresh();
        $this->assertEquals(2, count($this->professionnel->competences));
    }

    public function test_client_cannot_update_documents()
    {
        $client = User::factory()->create(['type' => 'client']);

        $response = $this->actingAs($client)
            ->postJson('/api/profile/documents', [
                'diplomes' => []
            ]);

        $response->assertStatus(403);
    }

    public function test_client_cannot_update_competences()
    {
        $client = User::factory()->create(['type' => 'client']);

        $response = $this->actingAs($client)
            ->postJson('/api/profile/competences', [
                'competences' => []
            ]);

        $response->assertStatus(403);
    }
}
