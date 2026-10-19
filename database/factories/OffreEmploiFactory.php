<?php

namespace Database\Factories;

use App\Models\EntreprisePartenaire;
use App\Models\OffreEmploi;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OffreEmploi>
 */
class OffreEmploiFactory extends Factory
{
    public function definition(): array
    {
        return [
            'entreprise_id' => EntreprisePartenaire::factory(),
            'titre' => fake()->jobTitle(),
            'description' => fake()->paragraph(),
            'type_contrat' => 'STAGE',
            'ville' => 'Douala',
            'statut' => 'PUBLIEE',
        ];
    }

    public function cloturee(): static
    {
        return $this->state(['statut' => 'CLOTUREE']);
    }
}
