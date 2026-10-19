<?php

namespace Database\Factories;

use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Service>
 */
class ServiceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nom' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'categorie' => 'Création & communication',
            'fournisseur' => 'DAG',
            'statut' => 'PUBLIE',
        ];
    }

    public function brouillon(): static
    {
        return $this->state(['statut' => 'BROUILLON']);
    }
}
