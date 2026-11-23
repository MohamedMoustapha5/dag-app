<?php

namespace Database\Factories;

use App\Models\AccesRecrutement;
use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AccesRecrutement>
 */
class AccesRecrutementFactory extends Factory
{
    /**
     * A period in progress, paid for one month.
     */
    public function definition(): array
    {
        return [
            'client_id' => Client::factory()->entreprise(),
            'libelle' => '1 mois',
            'duree_mois' => 1,
            'montant' => 10000,
            'origine' => 'PAIEMENT',
            'statut' => 'ACTIF',
            'debut' => now()->toDateString(),
            'fin' => now()->addMonth()->subDay()->toDateString(),
        ];
    }

    public function expire(): static
    {
        return $this->state([
            'debut' => now()->subMonths(2)->toDateString(),
            'fin' => now()->subMonth()->toDateString(),
        ]);
    }
}
