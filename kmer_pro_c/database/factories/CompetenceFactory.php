<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class CompetenceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nom' => fake()->unique()->jobTitle(),
            'description' => fake()->sentence(),
            'categorie' => fake()->randomElement(['technique', 'manuelle', 'artistique', 'service'])
        ];
    }
} 