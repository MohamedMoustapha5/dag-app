<?php

namespace Database\Factories;

use App\Enums\UserType;
use App\Models\Apprenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Apprenant>
 */
class ApprenantFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->state(['type' => UserType::Apprenant]),
            'ville' => 'Douala',
            'telephone' => '+237 6'.fake()->numerify('########'),
            'consentement_cv' => true,
        ];
    }
}
