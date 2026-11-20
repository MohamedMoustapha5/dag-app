<?php

namespace Database\Seeders;

use App\Enums\Civilite;
use App\Enums\MembreEquipeRole;
use App\Models\MembreEquipe;
use Illuminate\Database\Seeder;

class MembresEquipeSeeder extends Seeder
{
    /**
     * The internal DAG roles, matching the default access matrix in
     * section 3.2 of the cahier des charges. The Promotrice has full
     * (« Total ») access. These accounts sign in at /espace-admin/connexion.
     */
    public function run(): void
    {
        $membres = [
            ['name' => 'Mandou', 'civilite' => Civilite::Madame, 'email' => 'afsamandou15@gmail.com', 'role' => MembreEquipeRole::Promotrice],
            ['name' => 'Pascal', 'civilite' => Civilite::Monsieur, 'email' => 'ndoumnkambenoitpascal@gmail.com', 'role' => MembreEquipeRole::Juriste],
            ['name' => 'Senghor', 'civilite' => Civilite::Monsieur, 'email' => 'jules.k@dag.cm', 'role' => MembreEquipeRole::CommunicationMarketing],
            ['name' => 'Fadimatou', 'civilite' => Civilite::Madame, 'email' => 'herve.n@dag.cm', 'role' => MembreEquipeRole::ControleGestion],
            ['name' => 'Hussein', 'civilite' => Civilite::Monsieur, 'email' => 'rosine.t@dag.cm', 'role' => MembreEquipeRole::CreationContenu],
            ['name' => 'Mustapha', 'civilite' => Civilite::Monsieur, 'email' => 'moustondegeneve1111@gmail.com', 'role' => MembreEquipeRole::Developpeur],
        ];

        foreach ($membres as $membre) {
            MembreEquipe::create([
                ...$membre,
                'password' => 'password',
                'statut' => 'ACTIF',
            ]);
        }
    }
}
