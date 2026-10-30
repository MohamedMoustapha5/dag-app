<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Facture;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Facture>
 */
class FactureFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => 'SERVICE',
            'client_id' => Client::factory(),
            'montant' => fake()->randomFloat(2, 50000, 500000),
            'montant_paye' => 0,
            'statut' => 'EN_ATTENTE',
        ];
    }

    public function payee(): static
    {
        return $this->state(fn (array $attributes) => [
            'statut' => 'PAYEE',
            'montant_paye' => $attributes['montant'],
            'moyen_paiement' => 'MTN_MOMO',
        ]);
    }
}
