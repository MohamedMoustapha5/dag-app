<?php

namespace Database\Factories;

use App\Models\Formation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Formation>
 */
class FormationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nom' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'categorie' => 'Community management',
            'duree' => '3 semaines',
            'statut' => 'PUBLIEE',
        ];
    }
}
