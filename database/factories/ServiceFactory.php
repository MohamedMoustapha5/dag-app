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
            'contrat_requis' => true,
            'statut' => 'PUBLIE',
        ];
    }

    /**
     * A service an entreprise buys on the validated devis alone, without
     * signing a contract.
     */
    public function sansContrat(): static
    {
        return $this->state(['contrat_requis' => false]);
    }

    public function brouillon(): static
    {
        return $this->state(['statut' => 'BROUILLON']);
    }
}
