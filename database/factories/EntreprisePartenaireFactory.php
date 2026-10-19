<?php

namespace Database\Factories;

use App\Models\EntreprisePartenaire;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EntreprisePartenaire>
 */
class EntreprisePartenaireFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nom' => fake()->company(),
            'statut' => 'ACTIF',
        ];
    }

    public function enAttente(): static
    {
        return $this->state(['statut' => 'EN_ATTENTE']);
    }
}
