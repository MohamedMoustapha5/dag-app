<?php

namespace Database\Factories;

use App\Enums\MembreEquipeRole;
use App\Enums\UserType;
use App\Models\MembreEquipe;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MembreEquipe>
 */
class MembreEquipeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->state(['type' => UserType::MembreEquipe]),
            'role' => fake()->randomElement(MembreEquipeRole::cases()),
            'statut' => 'ACTIF',
        ];
    }

    public function promotrice(): static
    {
        return $this->state(['role' => MembreEquipeRole::Promotrice]);
    }

    public function juriste(): static
    {
        return $this->state(['role' => MembreEquipeRole::Juriste]);
    }

    public function inactif(): static
    {
        return $this->state(['statut' => 'INACTIF']);
    }
}
