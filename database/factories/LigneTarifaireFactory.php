<?php

namespace Database\Factories;

use App\Models\LigneTarifaire;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LigneTarifaire>
 */
class LigneTarifaireFactory extends Factory
{
    public function definition(): array
    {
        return [
            'categorie' => 'Graphisme & identité visuelle',
            'categorie_ordre' => 1,
            'prestation' => fake()->words(3, true),
            'tarif' => '25 000–60 000',
            'ordre' => fake()->numberBetween(0, 10),
        ];
    }
}
