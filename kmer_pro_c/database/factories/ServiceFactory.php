<?php

namespace Database\Factories;

use App\Models\Service;
use App\Models\User;
use App\Models\Categorie;
use App\Models\Zone;
use Illuminate\Database\Eloquent\Factories\Factory;

class ServiceFactory extends Factory
{
    protected $model = Service::class;

    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'categorie_id' => Categorie::factory(),
            'zone_id' => Zone::factory(),
            'titre' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(),
            'prix' => $this->faker->numberBetween(5000, 100000),
            'disponible' => true,
            'note_moyenne' => $this->faker->randomFloat(1, 1, 5),
            'nombre_avis' => $this->faker->numberBetween(0, 100)
        ];
    }
} 