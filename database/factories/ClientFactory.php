<?php

namespace Database\Factories;

use App\Enums\UserType;
use App\Models\Client;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Client>
 */
class ClientFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->state(['type' => UserType::Client]),
            'type' => 'PARTICULIER',
            'nom' => fake()->name(),
            'ville' => 'Douala',
            'telephone' => '+237 6'.fake()->numerify('########'),
            'statut' => 'ACTIF',
        ];
    }

    public function enAttente(): static
    {
        return $this->state(['statut' => 'EN_ATTENTE']);
    }
}
