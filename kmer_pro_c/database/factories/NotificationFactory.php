<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class NotificationFactory extends Factory
{
    public function definition(): array
    {
        $types = [
            'nouvelle_demande', 
            'demande_acceptee', 
            'demande_refusee', 
            'paiement_recu', 
            'paiement_confirme', 
            'service_complete', 
            'nouveau_message', 
            'document_valide', 
            'document_rejete'
        ];

        return [
            'user_id' => User::factory(),
            'type' => fake()->randomElement($types),
            'titre' => fake()->sentence(4),
            'message' => fake()->paragraph(1),
            'lien' => fake()->optional()->url(),
            'data' => fake()->optional()->passthrough([
                'id' => fake()->uuid(),
                'date' => fake()->dateTime()
            ]),
            'lu' => fake()->boolean(20),
            'date_lecture' => function (array $attributes) {
                return $attributes['lu'] ? fake()->dateTimeBetween('-3 months', 'now') : null;
            }
        ];
    }

    public function nonLue()
    {
        return $this->state(function () {
            return [
                'lu' => false,
                'date_lecture' => null
            ];
        });
    }

    public function lue()
    {
        return $this->state(function () {
            return [
                'lu' => true,
                'date_lecture' => fake()->dateTimeBetween('-3 months', 'now')
            ];
        });
    }
} 