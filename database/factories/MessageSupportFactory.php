<?php

namespace Database\Factories;

use App\Models\MessageSupport;
use App\Models\TicketSupport;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MessageSupport>
 */
class MessageSupportFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'ticket_support_id' => TicketSupport::factory(),
            'auteur' => 'DEMANDEUR',
            'contenu' => fake()->paragraph(),
        ];
    }
}
