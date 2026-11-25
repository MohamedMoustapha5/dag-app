<?php

namespace Database\Factories;

use App\Models\ForfaitRecrutement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ForfaitRecrutement>
 */
class ForfaitRecrutementFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->bothify('FORFAIT_###'),
            'libelle' => '6 mois',
            'duree_mois' => 6,
            'prix' => 55000,
            'ordre' => 2,
        ];
    }
}
