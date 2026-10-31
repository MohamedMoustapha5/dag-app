<?php

namespace Database\Seeders;

use App\Enums\MembreEquipeRole;
use App\Enums\UserType;
use App\Models\MembreEquipe;
use App\Models\User;
use Illuminate\Database\Seeder;

class MembresEquipeSeeder extends Seeder
{
    /**
     * The five internal DAG roles, matching the access matrix in section 3.2
     * of the cahier des charges. The Promotrice has full (« Total ») access.
     */
    public function run(): void
    {
        $membres = [
            ['name' => 'Afsa Maira', 'email' => 'marie.d@dag.cm', 'role' => MembreEquipeRole::Promotrice],
            ['name' => 'Fatou M.', 'email' => 'fatou.m@dag.cm', 'role' => MembreEquipeRole::Juriste],
            ['name' => 'Jules K.', 'email' => 'jules.k@dag.cm', 'role' => MembreEquipeRole::CommunicationMarketing],
            ['name' => 'Layana D.', 'email' => 'herve.n@dag.cm', 'role' => MembreEquipeRole::ControleGestion],
            ['name' => 'Hussein', 'email' => 'rosine.t@dag.cm', 'role' => MembreEquipeRole::CreationContenu],
        ];

        foreach ($membres as $membre) {
            $user = User::factory()->create([
                'name' => $membre['name'],
                'email' => $membre['email'],
                'type' => UserType::MembreEquipe,
                'password' => 'password',
            ]);

            MembreEquipe::create([
                'user_id' => $user->id,
                'role' => $membre['role'],
                'statut' => 'ACTIF',
            ]);
        }
    }
}
