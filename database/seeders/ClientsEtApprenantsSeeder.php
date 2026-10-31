<?php

namespace Database\Seeders;

use App\Enums\UserType;
use App\Models\Apprenant;
use App\Models\Client;
use App\Models\User;
use Illuminate\Database\Seeder;

class ClientsEtApprenantsSeeder extends Seeder
{
    public function run(): void
    {
        Client::create([
            'user_id' => User::factory()->create([
                'name' => 'Aïcha N.',
                'email' => 'aicha.n@exemple.com',
                'type' => UserType::Client,
                'password' => 'password',
            ])->id,
            'type' => 'PARTICULIER',
            'nom' => 'Aïcha N.',
            'ville' => 'Douala',
            'telephone' => '+237 6 55 00 11 22',
            'statut' => 'ACTIF',
        ]);

        Client::create([
            'user_id' => User::factory()->create([
                'name' => 'Fatou Boutique',
                'email' => 'contact@fatouboutique.cm',
                'type' => UserType::Client,
                'password' => 'password',
            ])->id,
            'type' => 'ENTREPRISE',
            'nom' => 'Fatou Boutique',
            'ville' => 'Douala',
            'statut' => 'EN_ATTENTE',
        ]);

        // Linked to the Kiro Digital Solutions EntreprisePartenaire in
        // DemoWorkflowSeeder, once that record exists — gives the
        // recruitment espace a validated partner account to sign into.
        Client::create([
            'user_id' => User::factory()->create([
                'name' => 'Kiro Digital Solutions',
                'email' => 'contact@kirodigital.cm',
                'type' => UserType::Client,
                'password' => 'password',
            ])->id,
            'type' => 'ENTREPRISE',
            'nom' => 'Kiro Digital Solutions',
            'ville' => 'Douala',
            'telephone' => '+237 6 90 11 22 33',
            'statut' => 'ACTIF',
        ]);

        $apprenants = [
            ['name' => 'Stéphanie A.', 'email' => 'stephanie.a@mail.com'],
            ['name' => 'Brice K.', 'email' => 'brice.k@mail.com'],
            ['name' => 'Noura E.', 'email' => 'noura.e@mail.com'],
            ['name' => 'Patrick M.', 'email' => 'patrick.m@mail.com'],
            ['name' => 'Carine F.', 'email' => 'carine.f@mail.com'],
            ['name' => 'Junior A.', 'email' => 'junior.a@mail.com'],
            ['name' => 'Larissa T.', 'email' => 'larissa.t@mail.com'],
            ['name' => 'Samuel E.', 'email' => 'samuel.e@mail.com'],
            ['name' => 'Grace N.', 'email' => 'grace.n@mail.com'],
            ['name' => 'Yannick B.', 'email' => 'yannick.b@mail.com'],
        ];

        foreach ($apprenants as $apprenant) {
            $user = User::factory()->create([
                'name' => $apprenant['name'],
                'email' => $apprenant['email'],
                'type' => UserType::Apprenant,
                'password' => 'password',
            ]);

            Apprenant::create([
                'user_id' => $user->id,
                'ville' => 'Douala, Cameroun',
                'telephone' => '+237 6 55 12 34 56',
                'consentement_cv' => true,
            ]);
        }
    }
}
