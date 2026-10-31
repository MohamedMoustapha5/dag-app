<?php

namespace Database\Factories;

use App\Models\Apprenant;
use App\Models\Candidature;
use App\Models\OffreEmploi;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Candidature>
 */
class CandidatureFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'apprenant_id' => Apprenant::factory(),
            'offre_id' => OffreEmploi::factory(),
            'statut' => 'DEPOSEE',
        ];
    }
}
