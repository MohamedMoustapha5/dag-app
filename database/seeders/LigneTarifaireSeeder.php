<?php

namespace Database\Seeders;

use App\Models\LigneTarifaire;
use Illuminate\Database\Seeder;

class LigneTarifaireSeeder extends Seeder
{
    /**
     * Grille tarifaire indicative transcrite depuis Grille_tarifaire_DAG.pdf
     * fourni par DAG. Les tarifs restent du texte libre : le document mélange
     * fourchettes, abonnements mensuels et prestations « Sur devis ».
     */
    public function run(): void
    {
        $categories = [
            1 => [
                'nom' => 'Graphisme & identité visuelle',
                'lignes' => [
                    ['Logo simple', '25 000–60 000'],
                    ['Identité visuelle complète', '100 000–250 000'],
                    ['Flyer / affiche', '10 000–25 000'],
                    ['Brochure ou catalogue', '30 000–100 000'],
                    ['Carte de visite (conception)', '5 000–15 000'],
                    ['Pack de 10 visuels réseaux sociaux', '35 000–70 000'],
                    ['Présentation PowerPoint', '25 000–80 000'],
                ],
            ],
            2 => [
                'nom' => 'Réseaux sociaux & marketing digital',
                'lignes' => [
                    ['Pack de 8 publications', '50 000–80 000 / mois'],
                    ['Pack de 12 publications', '80 000–130 000 / mois'],
                    ['Pack de 16 publications avec vidéos courtes', '130 000–220 000 / mois'],
                    ['Gestion de campagnes publicitaires', '30 000–100 000 / mois'],
                    ['Audit et stratégie digitale', '50 000–150 000'],
                ],
            ],
            3 => [
                'nom' => 'Création de contenu photo & vidéo',
                'lignes' => [
                    ['Shooting photo simple', '30 000–80 000'],
                    ['Vidéo promotionnelle courte', '40 000–120 000'],
                    ['Vidéo institutionnelle', '100 000–300 000'],
                    ['Pack mensuel de contenus', '100 000–250 000 / mois'],
                ],
            ],
            4 => [
                'nom' => 'Sites web, e-commerce & maintenance',
                'lignes' => [
                    ['Site vitrine simple', '150 000–300 000'],
                    ['Site professionnel multipage', '300 000–600 000'],
                    ['Site e-commerce', '350 000–900 000'],
                    ['Application web sur mesure', 'À partir de 700 000'],
                    ['Maintenance de site web', '15 000–60 000 / mois'],
                ],
            ],
            5 => [
                'nom' => 'Numérisation & gestion documentaire',
                'lignes' => [
                    ['Numérisation de documents simples', '50–150 / page'],
                    ['Organisation et indexation d\'archives', '50 000–200 000 / projet'],
                    ['Mise en place d\'un système documentaire', '150 000–500 000'],
                    ['Archivage et assistance récurrente', 'Sur devis'],
                ],
            ],
            6 => [
                'nom' => 'Hébergement web & solutions cloud',
                'lignes' => [
                    ['Configuration d\'e-mails professionnels', '25 000–75 000'],
                    ['Mise en place d\'un espace cloud', '50 000–150 000'],
                    ['Gestion et maintenance cloud', '20 000–80 000 / mois'],
                    ['Hébergement web', 'Coût fournisseur + frais de gestion'],
                ],
            ],
            7 => [
                'nom' => 'Conseil & gestion de projets digitaux',
                'lignes' => [
                    ['Diagnostic numérique', '50 000–100 000'],
                    ['Plan de transformation digitale', '100 000–300 000'],
                    ['Accompagnement mensuel', '75 000–250 000 / mois'],
                    ['Coordination d\'un projet complexe', 'Sur devis'],
                ],
            ],
            8 => [
                'nom' => 'Sécurité informatique & cybersécurité',
                'lignes' => [
                    ['Audit de sécurité de base', '75 000–200 000'],
                    ['Sécurisation des comptes et postes', '50 000–150 000'],
                    ['Configuration de sauvegardes', '50 000–150 000'],
                    ['Assistance et suivi', '50 000–150 000 / mois'],
                ],
            ],
            9 => [
                'nom' => 'Intelligence artificielle & automatisation',
                'lignes' => [
                    ['Audit des tâches automatisables', '50 000–100 000'],
                    ['Automatisation simple (formulaires, notifications)', '75 000–200 000'],
                    ['Chatbot / assistant documentaire', '150 000–500 000'],
                    ['Automatisation métier avancée', 'Sur devis'],
                ],
            ],
            10 => [
                'nom' => 'E-commerce & accompagnement numérique',
                'lignes' => [
                    ['Accompagnement à la digitalisation d\'un petit projet', '50 000–200 000'],
                    ['Accompagnement de boutique en ligne', '30 000–100 000 / mois'],
                    ['Formation et prise en main des outils', 'Sur devis'],
                ],
            ],
            11 => [
                'nom' => 'Placement de personnel numérique',
                'lignes' => [
                    ['Recrutement / mise en relation', 'Sur devis'],
                    ['Mise à disposition ou coordination d\'un profil', 'Sur devis'],
                ],
            ],
            12 => [
                'nom' => 'Solution de gestion médicale VitalLink',
                'lignes' => [
                    ['Déploiement et configuration', 'Sur devis'],
                    ['Formation des utilisateurs', 'Sur devis'],
                    ['Maintenance, hébergement et assistance', 'Sur devis'],
                ],
            ],
        ];

        foreach ($categories as $categorieOrdre => $categorie) {
            foreach ($categorie['lignes'] as $ordre => [$prestation, $tarif]) {
                LigneTarifaire::create([
                    'categorie' => $categorie['nom'],
                    'categorie_ordre' => $categorieOrdre,
                    'prestation' => $prestation,
                    'tarif' => $tarif,
                    'ordre' => $ordre,
                ]);
            }
        }
    }
}
