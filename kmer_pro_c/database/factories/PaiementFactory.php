<?php

namespace Database\Factories;

use App\Models\Demande;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PaiementFactory extends Factory
{
    public function definition(): array
    {
        $demande = Demande::factory()->create();
        
        return [
            'demande_id' => $demande->id,
            'client_id' => $demande->client_id,
            'professionnel_id' => $demande->professionnel_id,
            'montant' => fake()->numberBetween(5000, 100000),
            'devise' => fake()->randomElement(['XAF', 'EUR', 'USD']),
            'statut' => fake()->randomElement(['en_attente', 'confirme', 'annule', 'complete']),
            'methode' => fake()->randomElement(['mobile_money', 'carte', 'virement']),
            'reference' => 'PAY-' . Str::random(10),
            'date_confirmation' => function (array $attributes) {
                return in_array($attributes['statut'], ['confirme', 'complete']) ? now() : null;
            },
            'date_annulation' => function (array $attributes) {
                return $attributes['statut'] === 'annule' ? now() : null;
            },
            'commentaire' => fake()->optional()->sentence(),
            'details' => fake()->optional()->passthrough([
                'transaction_id' => fake()->uuid(),
                'date_transaction' => fake()->dateTime(),
                'provider' => fake()->randomElement(['Orange Money', 'MTN Mobile Money', 'VISA', 'MasterCard'])
            ])
        ];
    }

    public function enAttente()
    {
        return $this->state(function () {
            return [
                'statut' => 'en_attente',
                'date_confirmation' => null,
                'date_annulation' => null
            ];
        });
    }

    public function confirme()
    {
        return $this->state(function () {
            return [
                'statut' => 'confirme',
                'date_confirmation' => now(),
                'date_annulation' => null
            ];
        });
    }

    public function annule()
    {
        return $this->state(function () {
            return [
                'statut' => 'annule',
                'date_confirmation' => null,
                'date_annulation' => now()
            ];
        });
    }

    public function complete()
    {
        return $this->state(function () {
            return [
                'statut' => 'complete',
                'date_confirmation' => now()->subDays(1),
                'date_annulation' => null
            ];
        });
    }
} 