<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nom' => fake()->name(),
            'prenom' => fake()->firstName(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'telephone' => fake()->phoneNumber(),
            'type' => fake()->randomElement(['client', 'professionnel', 'admin']),
            'adresse' => fake()->address(),
            'ville' => fake()->city(),
            'pays' => fake()->country(),
            'description' => fake()->paragraph(),
            'competences' => fake()->words(5),
            'experience' => fake()->paragraph(),
            'diplomes' => fake()->words(3),
            'photo' => null,
            'statut' => 'actif',
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     *
     * @return $this
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
