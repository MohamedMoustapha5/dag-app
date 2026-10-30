<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Contrat;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Contrat>
 */
class ContratFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => 'PRESTATION_SERVICE',
            'client_id' => Client::factory(),
            'statut' => 'BROUILLON',
        ];
    }

    public function signe(): static
    {
        return $this->state([
            'statut' => 'SIGNE',
            'fichier_pdf' => '/documents/contrats/exemple.pdf',
            'date_signature' => now(),
        ]);
    }
}
