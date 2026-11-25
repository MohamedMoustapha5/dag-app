<?php

namespace Database\Factories;

use App\Models\LogoPartenaire;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LogoPartenaire>
 */
class LogoPartenaireFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nom' => fake()->company(),
            'logo' => '/storage/'.LogoPartenaire::DOSSIER.'/'.fake()->uuid().'.png',
            'lien' => null,
            'fond_sombre' => false,
            'visible' => true,
            'ordre' => fake()->numberBetween(10, 99),
        ];
    }
}
