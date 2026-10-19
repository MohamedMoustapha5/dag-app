<?php

namespace Database\Factories;

use App\Models\Formule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Formule>
 */
class FormuleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nom' => fake()->words(2, true),
            'contenu' => fake()->sentence(),
            'services_inclus' => [fake()->words(3, true), fake()->words(3, true)],
            'prix_affiche' => 'Prix sur devis, négocié après connexion',
            'populaire' => false,
            'statut' => 'PUBLIEE',
        ];
    }
}
