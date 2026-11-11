<?php

namespace Database\Factories;

use App\Models\Decaissement;
use App\Models\MembreEquipe;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Decaissement>
 */
class DecaissementFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'montant' => fake()->numberBetween(5, 500) * 1000,
            'moyen' => fake()->randomElement(['OM', 'MTN_MOMO']),
            'telephone' => '6'.fake()->numerify('########'),
            'beneficiaire' => 'DAG',
            'motif' => 'Retrait du solde des paiements en ligne',
            'statut' => 'EN_ATTENTE',
            'reference_transaction' => fake()->unique()->bothify('po_##########'),
            'demande_par_id' => MembreEquipe::factory(),
        ];
    }

    public function confirme(): static
    {
        return $this->state(fn () => ['statut' => 'CONFIRME', 'date_execution' => now()]);
    }
}
