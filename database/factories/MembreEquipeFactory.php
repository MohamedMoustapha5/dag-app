<?php

namespace Database\Factories;

use App\Enums\MembreEquipeRole;
use App\Models\MembreEquipe;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MembreEquipe>
 */
class MembreEquipeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => 'password',
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

    public function developpeur(): static
    {
        return $this->state(['role' => MembreEquipeRole::Developpeur]);
    }

    public function inactif(): static
    {
        return $this->state(['statut' => 'INACTIF']);
    }
}
