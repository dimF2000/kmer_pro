<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

class DemandeFactory extends Factory
{
    public function definition(): array
    {
        $service = Service::factory()->create();
        $client = User::factory()->create(['type' => 'client']);
        $professionnel = User::factory()->create(['type' => 'professionnel']);

        return [
            'service_id' => $service->id,
            'client_id' => $client->id,
            'professionnel_id' => $professionnel->id,
            'statut' => fake()->randomElement(['en_attente', 'acceptee', 'refusee', 'en_cours', 'terminee', 'annulee', 'complete']),
            'description' => fake()->paragraph(),
            'date_souhaitee' => fake()->dateTimeBetween('now', '+2 months'),
            'adresse' => fake()->address(),
            'montant' => fake()->numberBetween(5000, 100000),
            'date_acceptation' => function (array $attributes) {
                return in_array($attributes['statut'], ['acceptee', 'en_cours', 'terminee', 'complete']) 
                    ? fake()->dateTimeBetween('-1 month', 'now') 
                    : null;
            },
            'date_fin' => function (array $attributes) {
                return in_array($attributes['statut'], ['terminee', 'complete']) 
                    ? fake()->dateTimeBetween('-1 month', 'now') 
                    : null;
            },
            'note' => function (array $attributes) {
                return in_array($attributes['statut'], ['terminee', 'complete']) 
                    ? fake()->numberBetween(1, 5) 
                    : null;
            },
            'commentaire' => fake()->optional()->paragraph()
        ];
    }

    public function enAttente()
    {
        return $this->state(function () {
            return [
                'statut' => 'en_attente',
                'date_acceptation' => null,
                'date_fin' => null,
                'note' => null
            ];
        });
    }

    public function acceptee()
    {
        return $this->state(function () {
            return [
                'statut' => 'acceptee',
                'date_acceptation' => now(),
                'date_fin' => null,
                'note' => null
            ];
        });
    }

    public function refusee()
    {
        return $this->state(function () {
            return [
                'statut' => 'refusee',
                'date_acceptation' => null,
                'date_fin' => null,
                'note' => null
            ];
        });
    }

    public function enCours()
    {
        return $this->state(function () {
            return [
                'statut' => 'en_cours',
                'date_acceptation' => now()->subDays(2),
                'date_fin' => null,
                'note' => null
            ];
        });
    }

    public function terminee()
    {
        return $this->state(function () {
            return [
                'statut' => 'terminee',
                'date_acceptation' => now()->subDays(5),
                'date_fin' => now(),
                'note' => fake()->numberBetween(1, 5)
            ];
        });
    }

    public function complete()
    {
        return $this->state(function () {
            return [
                'statut' => 'complete',
                'date_acceptation' => now()->subDays(5),
                'date_fin' => now(),
                'note' => fake()->numberBetween(3, 5)
            ];
        });
    }

    public function annulee()
    {
        return $this->state(function () {
            return [
                'statut' => 'annulee',
                'date_acceptation' => fake()->optional()->dateTimeBetween('-1 month', 'now'),
                'date_fin' => null,
                'note' => null
            ];
        });
    }
} 