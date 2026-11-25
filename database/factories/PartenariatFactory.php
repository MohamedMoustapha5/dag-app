<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Partenariat;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Partenariat>
 */
class PartenariatFactory extends Factory
{
    /**
     * A request from an entreprise, waiting for DAG.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'client_id' => Client::factory()->entreprise(),
            'origine' => 'DEMANDE',
            'statut' => 'EN_ETUDE',
            'domaines' => ['RECRUTEMENT', 'FORMATION'],
            'message' => fake()->paragraph(),
        ];
    }

    /**
     * A partnership in force for a year from today.
     */
    public function actif(): static
    {
        return $this->state([
            'statut' => 'ACTIF',
            'duree_mois' => 12,
            'debut' => now()->toDateString(),
            'fin' => now()->addYear()->subDay()->toDateString(),
        ]);
    }
}
