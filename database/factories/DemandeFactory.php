<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Demande;
use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Demande>
 */
class DemandeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'service_id' => Service::factory(),
            'description' => fake()->sentence(12),
            'statut' => 'EN_ETUDE',
        ];
    }
}
