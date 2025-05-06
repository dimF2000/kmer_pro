<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DocumentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'type' => fake()->randomElement(['cni', 'diplome', 'certificat', 'attestation']),
            'numero' => fake()->unique()->numerify('DOC-####'),
            'chemin' => 'documents/' . fake()->uuid() . '.pdf',
            'statut' => fake()->randomElement(['en_attente', 'valide', 'rejete']),
            'commentaire' => fake()->optional()->sentence()
        ];
    }
} 