<?php

namespace Database\Seeders;

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
            ['name' => 'Afsa Maira', 'email' => 'afsamandou15@gmail.com', 'role' => MembreEquipeRole::Promotrice],
            ['name' => 'Pascal', 'email' => 'fatou.m@dag.cm', 'role' => MembreEquipeRole::Juriste],
            ['name' => 'Senghor', 'email' => 'jules.k@dag.cm', 'role' => MembreEquipeRole::CommunicationMarketing],
            ['name' => 'Layana D.', 'email' => 'herve.n@dag.cm', 'role' => MembreEquipeRole::ControleGestion],
            ['name' => 'Hussein', 'email' => 'rosine.t@dag.cm', 'role' => MembreEquipeRole::CreationContenu],
            ['name' => 'Mustapha', 'email' => 'dev@dag.cm', 'role' => MembreEquipeRole::Developpeur],
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
