<?php

namespace Database\Seeders;

use App\Models\EntreprisePartenaire;
use App\Models\OffreEmploi;
use Illuminate\Database\Seeder;

class EntreprisesPartenairesSeeder extends Seeder
{
    public function run(): void
    {
        $kiro = EntreprisePartenaire::create([
            'nom' => 'Kiro Digital Solutions',
            'secteur' => 'Agence digitale',
            'adresse' => 'Akwa, Douala, Cameroun',
            'rccm' => 'RC/DLA/2023/B/4521',
            'email' => 'contact@kirodigital.cm',
            'telephone' => '+237 6 90 11 22 33',
            'statut' => 'ACTIF',
        ]);

        $atlas = EntreprisePartenaire::create([
            'nom' => 'Atlas Corp',
            'secteur' => 'Services aux entreprises',
            'adresse' => 'Bonanjo, Douala, Cameroun',
            'email' => 'rh@atlascorp.cm',
            'statut' => 'ACTIF',
        ]);

        $rosineShop = EntreprisePartenaire::create([
            'nom' => 'Rosine Shop',
            'statut' => 'INACTIF',
        ]);

        OffreEmploi::create([
            'entreprise_id' => $kiro->id,
            'titre' => 'Community Manager junior',
            'description' => 'Au sein de l\'équipe marketing, vous participerez à l\'animation quotidienne des réseaux sociaux : planification éditoriale, création de visuels simples, modération des commentaires et reporting hebdomadaire.',
            'type_contrat' => 'STAGE',
            'duree' => '3 mois',
            'competences' => 'Community management, Création de contenu, Canva / outils simples',
            'ville' => 'Douala',
            'date_limite' => '2026-11-30',
            'statut' => 'PUBLIEE',
        ]);

        OffreEmploi::create([
            'entreprise_id' => $atlas->id,
            'titre' => 'Chargé(e) de création de contenu',
            'description' => 'Production de visuels et de vidéos courtes pour les réseaux sociaux de l\'entreprise.',
            'type_contrat' => 'CDD',
            'ville' => 'Douala',
            'date_limite' => '2026-10-12',
            'statut' => 'PUBLIEE',
        ]);

        OffreEmploi::create([
            'entreprise_id' => $rosineShop->id,
            'titre' => 'Assistant(e) social media',
            'description' => 'Appui à la gestion des réseaux sociaux de la boutique.',
            'type_contrat' => 'STAGE',
            'duree' => '2 mois',
            'ville' => 'Yaoundé',
            'statut' => 'CLOTUREE',
        ]);
    }
}
