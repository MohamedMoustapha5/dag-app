<?php

namespace Database\Factories;

use App\Models\Formation;
use App\Models\FormationSession;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FormationSession>
 */
class FormationSessionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'formation_id' => Formation::factory(),
            'ville' => 'Douala',
            'mode' => 'PRESENTIEL',
            'places' => 15,
            'statut' => 'OUVERTE',
        ];
    }
}
