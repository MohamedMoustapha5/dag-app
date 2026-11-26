<?php

namespace Database\Seeders;

use App\Enums\Civilite;
use App\Enums\MembreEquipeRole;
use App\Models\MembreEquipe;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

class MembresEquipeSeeder extends Seeder
{
    /**
     * The internal DAG roles, matching the default access matrix in
     * section 3.2 of the cahier des charges. The Promotrice has full
     * (« Total ») access. These accounts sign in at /espace-admin/connexion.
     * In production, the first seed e-mails each member a random password,
     * and the administration account its own (equipe:envoyer-acces).
     */
    public function run(): void
    {
        $membres = [
            ['name' => 'Mandou', 'civilite' => Civilite::Madame, 'email' => 'afsamandou15@gmail.com', 'role' => MembreEquipeRole::Promotrice],
            ['name' => 'Pascal', 'civilite' => Civilite::Monsieur, 'email' => 'ndoumnkambenoitpascal@gmail.com', 'role' => MembreEquipeRole::Juriste],
            ['name' => 'Senghor', 'civilite' => Civilite::Monsieur, 'email' => 'senghortams@gmail.com', 'role' => MembreEquipeRole::CommunicationMarketing],
            ['name' => 'Fadimatou', 'civilite' => Civilite::Madame, 'email' => 'herve.n@dag.cm', 'role' => MembreEquipeRole::ControleGestion],
            ['name' => 'Mustapha', 'civilite' => Civilite::Monsieur, 'email' => 'moustondegeneve1111@gmail.com', 'role' => MembreEquipeRole::Developpeur],
        ];

        $production = app()->environment('production');
        $crees = 0;

        foreach ($membres as $membre) {
            $compte = MembreEquipe::firstOrCreate(['email' => $membre['email']], [
                ...$membre,
                // On the production server no account keeps a known password:
                // each one receives its own by e-mail just below.
                'password' => $production ? Str::password(32) : 'password',
                'statut' => 'ACTIF',
            ]);

            $crees += $compte->wasRecentlyCreated ? 1 : 0;
        }

        if ($production && $crees > 0) {
            Artisan::call('equipe:envoyer-acces', ['--tous' => true], $this->command?->getOutput());
        }
    }
}
