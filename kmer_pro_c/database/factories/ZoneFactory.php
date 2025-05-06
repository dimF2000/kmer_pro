<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ZoneFactory extends Factory
{
    public function definition(): array
    {
        $villes = [
            'Yaoundé',
            'Douala',
            'Bafoussam',
            'Bamenda',
            'Garoua',
            'Maroua',
            'Bertoua',
            'Ngaoundéré',
            'Buea',
            'Ebolowa'
        ];

        $regions = [
            'Centre',
            'Littoral',
            'Ouest',
            'Nord-Ouest',
            'Nord',
            'Extrême-Nord',
            'Est',
            'Adamaoua',
            'Sud-Ouest',
            'Sud'
        ];

        $ville = fake()->randomElement($villes);
        $region = fake()->randomElement($regions);

        return [
            'nom' => "Zone {$ville}",
            'description' => fake()->paragraph(),
            'ville' => $ville,
            'region' => $region,
            'pays' => 'Cameroun'
        ];
    }
} 