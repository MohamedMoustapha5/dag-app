<?php

namespace Database\Factories;

use App\Enums\UserType;
use App\Models\Client;
use App\Models\ClientCollaborateur;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClientCollaborateur>
 */
class ClientCollaborateurFactory extends Factory
{
    /**
     * An active collaborator of an Entreprise client.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'client_id' => Client::factory()->state(['type' => 'ENTREPRISE']),
            'user_id' => User::factory()->state(['type' => UserType::Client]),
            'accepte_le' => now(),
        ];
    }

    /** Invited, has not chosen a password yet. */
    public function enAttente(): static
    {
        return $this->state(['accepte_le' => null]);
    }
}
