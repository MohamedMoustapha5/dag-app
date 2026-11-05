<?php

namespace Database\Factories;

use App\Models\TicketSupport;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TicketSupport>
 */
class TicketSupportFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'origine' => 'ESPACE',
            'nom' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'categorie' => 'AUTRE',
            'sujet' => fake()->sentence(4),
            'statut' => 'OUVERT',
            'dernier_message_at' => now(),
        ];
    }

    /**
     * A message sent by a visitor through the public contact form.
     */
    public function public(): static
    {
        return $this->state(['user_id' => null, 'origine' => 'PUBLIC']);
    }
}
