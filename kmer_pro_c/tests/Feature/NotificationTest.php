<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Notification;
use App\Models\Demande;
use App\Models\Paiement;
use App\Models\Message;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

class NotificationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private $user;
    private $autreUser;
    private $client;
    private $professionnel;
    private $service;
    private $demande;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->autreUser = User::factory()->create();
        $this->client = User::factory()->create(['type' => 'client']);
        $this->professionnel = User::factory()->create(['type' => 'professionnel']);
        $this->service = Service::factory()->create(['user_id' => $this->professionnel->id]);
        $this->demande = Demande::factory()->create([
            'client_id' => $this->client->id,
            'service_id' => $this->service->id
        ]);
    }

    public function test_peut_voir_notifications()
    {
        Notification::factory()->count(3)->create([
            'user_id' => $this->user->id
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/notifications');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'user_id',
                        'type',
                        'titre',
                        'message',
                        'donnees',
                        'lu'
                    ]
                ]
            ]);
    }

    public function test_peut_voir_notifications_non_lues()
    {
        Notification::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'lu' => false
        ]);

        Notification::factory()->create([
            'user_id' => $this->user->id,
            'lu' => true
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/notifications/non-lues');

        $response->assertStatus(200)
            ->assertJsonCount(2);
    }

    public function test_peut_marquer_notification_comme_lue()
    {
        $notification = Notification::factory()->create([
            'user_id' => $this->user->id,
            'lu' => false
        ]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/notifications/{$notification->id}/read");

        $response->assertStatus(200);

        $this->assertDatabaseHas('notifications', [
            'id' => $notification->id,
            'lu' => true
        ]);
    }

    public function test_peut_marquer_toutes_notifications_comme_lues()
    {
        Notification::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'lu' => false
        ]);

        $response = $this->actingAs($this->user)
            ->putJson('/api/notifications/read-all');

        $response->assertStatus(200);

        $this->assertDatabaseMissing('notifications', [
            'user_id' => $this->user->id,
            'lu' => false
        ]);
    }

    public function test_peut_supprimer_notification()
    {
        $notification = Notification::factory()->create([
            'user_id' => $this->user->id
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/notifications/{$notification->id}");

        $response->assertStatus(200);

        $this->assertDatabaseMissing('notifications', [
            'id' => $notification->id
        ]);
    }

    public function test_peut_supprimer_toutes_notifications()
    {
        Notification::factory()->count(3)->create([
            'user_id' => $this->user->id
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson('/api/notifications');

        $response->assertStatus(200);

        $this->assertDatabaseMissing('notifications', [
            'user_id' => $this->user->id
        ]);
    }

    public function test_peut_voir_statistiques_notifications()
    {
        Notification::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'type' => 'nouvelle_demande',
            'lu' => false
        ]);

        Notification::factory()->create([
            'user_id' => $this->user->id,
            'type' => 'paiement_recu',
            'lu' => true
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/notifications/statistiques');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'total',
                'non_lues',
                'par_type'
            ])
            ->assertJson([
                'total' => 3,
                'non_lues' => 2
            ]);
    }

    public function test_non_autorise_voir_notifications_autre_utilisateur()
    {
        $notification = Notification::factory()->create([
            'user_id' => $this->autreUser->id
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/notifications/{$notification->id}");

        $response->assertStatus(403);
    }

    public function test_non_autorise_marquer_notification_autre_utilisateur()
    {
        $notification = Notification::factory()->create([
            'user_id' => $this->autreUser->id
        ]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/notifications/{$notification->id}/read");

        $response->assertStatus(403);
    }

    public function test_non_autorise_supprimer_notification_autre_utilisateur()
    {
        $notification = Notification::factory()->create([
            'user_id' => $this->autreUser->id
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/notifications/{$notification->id}");

        $response->assertStatus(403);
    }

    public function test_notification_nouvelle_demande()
    {
        $notification = Notification::notifierNouvelleDemande($this->demande);

        $this->assertDatabaseHas('notifications', [
            'id' => $notification->id,
            'user_id' => $this->professionnel->id,
            'type' => 'nouvelle_demande',
            'titre' => 'Nouvelle demande de service'
        ]);
    }

    public function test_notification_paiement_recu()
    {
        $paiement = Paiement::factory()->create([
            'client_id' => $this->client->id,
            'professionnel_id' => $this->professionnel->id
        ]);

        $notification = Notification::notifierPaiementRecu($paiement);

        $this->assertDatabaseHas('notifications', [
            'id' => $notification->id,
            'user_id' => $this->professionnel->id,
            'type' => 'paiement_recu',
            'titre' => 'Paiement reçu'
        ]);
    }

    public function test_notification_nouveau_message()
    {
        $expediteur = User::factory()->create();
        $destinataire = User::factory()->create();
        $message = Message::factory()->create([
            'expediteur_id' => $expediteur->id,
            'destinataire_id' => $destinataire->id
        ]);

        $notification = Notification::notifierNouveauMessage($message);

        $this->assertDatabaseHas('notifications', [
            'id' => $notification->id,
            'user_id' => $destinataire->id,
            'type' => 'nouveau_message',
            'titre' => 'Nouveau message'
        ]);
    }

    public function test_notification_creation()
    {
        $notificationData = [
            'user_id' => $this->client->id,
            'type' => 'demande_acceptee',
            'titre' => 'Demande acceptée',
            'message' => 'Votre demande a été acceptée',
            'lien' => "/demandes/{$this->demande->id}"
        ];

        $response = $this->actingAs($this->client)
            ->postJson('/api/notifications', $notificationData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'id',
                'user_id',
                'type',
                'titre',
                'message',
                'lien',
                'lu',
                'created_at',
                'updated_at'
            ]);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->client->id,
            'type' => 'demande_acceptee',
            'titre' => 'Demande acceptée'
        ]);
    }

    public function test_notification_listing()
    {
        Notification::factory()->count(3)->create([
            'user_id' => $this->client->id
        ]);

        $response = $this->actingAs($this->client)
            ->getJson('/api/notifications');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'type',
                        'titre',
                        'message',
                        'lien',
                        'lu',
                        'created_at'
                    ]
                ]
            ]);
    }

    public function test_notification_marking_as_read()
    {
        $notification = Notification::factory()->create([
            'user_id' => $this->client->id,
            'lu' => false
        ]);

        $response = $this->actingAs($this->client)
            ->putJson("/api/notifications/{$notification->id}/lu");

        $response->assertStatus(200);
        $this->assertTrue($notification->fresh()->lu);
    }

    public function test_mark_all_notifications_as_read()
    {
        Notification::factory()->count(3)->create([
            'user_id' => $this->client->id,
            'lu' => false
        ]);

        $response = $this->actingAs($this->client)
            ->putJson('/api/notifications/marquer-tout-lu');

        $response->assertStatus(200);
        $this->assertEquals(0, Notification::where('user_id', $this->client->id)
            ->where('lu', false)
            ->count());
    }

    public function test_unread_notifications_count()
    {
        Notification::factory()->count(2)->create([
            'user_id' => $this->client->id,
            'lu' => false
        ]);

        $response = $this->actingAs($this->client)
            ->getJson('/api/notifications/non-lues/count');

        $response->assertStatus(200)
            ->assertJson([
                'count' => 2
            ]);
    }

    public function test_notification_deletion()
    {
        $notification = Notification::factory()->create([
            'user_id' => $this->client->id
        ]);

        $response = $this->actingAs($this->client)
            ->deleteJson("/api/notifications/{$notification->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('notifications', [
            'id' => $notification->id
        ]);
    }

    public function test_notification_validation()
    {
        $notificationData = [
            'user_id' => $this->client->id,
            'type' => 'type_invalide',
            'titre' => '',
            'message' => ''
        ];

        $response = $this->actingAs($this->client)
            ->postJson('/api/notifications', $notificationData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['type', 'titre', 'message']);
    }
} 