<?php

namespace Database\Factories;

use App\Models\Message;
use App\Models\User;
use App\Models\Demande;
use Illuminate\Database\Eloquent\Factories\Factory;

class MessageFactory extends Factory
{
    protected $model = Message::class;

    public function definition()
    {
        return [
            'expediteur_id' => User::factory(),
            'destinataire_id' => User::factory(),
            'demande_id' => null,
            'contenu' => $this->faker->paragraph(),
            'lu' => $this->faker->boolean(),
            'date_lecture' => $this->faker->optional()->dateTimeBetween('-1 month', 'now')
        ];
    }

    public function nonLu()
    {
        return $this->state(function (array $attributes) {
            return [
                'lu' => false,
                'date_lecture' => null
            ];
        });
    }

    public function lu()
    {
        return $this->state(function (array $attributes) {
            return [
                'lu' => true,
                'date_lecture' => now()
            ];
        });
    }

    public function avecPiecesJointes()
    {
        return $this->state(function (array $attributes) {
            return [
                'pieces_jointes' => [
                    [
                        'nom' => 'document.pdf',
                        'chemin' => 'messages/pieces_jointes/document.pdf',
                        'type' => 'application/pdf',
                        'taille' => 1024
                    ],
                    [
                        'nom' => 'image.jpg',
                        'chemin' => 'messages/pieces_jointes/image.jpg',
                        'type' => 'image/jpeg',
                        'taille' => 2048
                    ]
                ]
            ];
        });
    }
} 