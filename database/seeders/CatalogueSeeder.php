<?php

namespace Database\Seeders;

use App\Models\Formule;
use App\Models\Service;
use Illuminate\Database\Seeder;

class CatalogueSeeder extends Seeder
{
    /**
     * Services and formules as listed in sections 5.1, 5.2 and 6.3 of the
     * cahier des charges. Prices stay blank: they are negotiated per client.
     */
    public function run(): void
    {
        $servicesDag = [
            ['nom' => 'Création de contenu', 'description' => 'Posts, visuels, vidéos, branding.', 'categorie' => 'Création & communication'],
            ['nom' => 'Social Media Management', 'description' => 'Animation et croissance des communautés.', 'categorie' => 'Création & communication'],
            ['nom' => 'Community management', 'description' => 'Modération, interaction, planification éditoriale.', 'categorie' => 'Création & communication'],
            ['nom' => 'Gestion de projets & stratégie digitale', 'description' => 'Planification, conseil, accompagnement.', 'categorie' => 'Création & communication'],
            ['nom' => 'Campagnes publicitaires', 'description' => 'Meta Ads, TikTok Ads, ciblage précis.', 'categorie' => 'Création & communication'],
            ['nom' => 'Boost de comptes et de pages', 'description' => 'Facebook, Instagram, TikTok.', 'categorie' => 'Création & communication'],
            ['nom' => 'Monétisation TikTok & Facebook', 'description' => 'Optimisation des revenus créateurs.', 'categorie' => 'Création & communication'],
            ['nom' => 'Formation et placement en entreprise', 'description' => 'Formations en community management et création de contenu, avec accompagnement vers un stage ou un emploi.', 'categorie' => 'Création & communication'],
        ];

        $servicesNovaTech = [
            ['nom' => 'Création de sites internet', 'description' => 'Sites vitrines, e-commerce, portails web.', 'categorie' => 'Technique & digital'],
            ['nom' => 'Plateformes e-commerce', 'description' => 'Vente en ligne, paiement sécurisé, gestion de boutique.', 'categorie' => 'Technique & digital'],
            ['nom' => 'Hébergement web', 'description' => 'Sites, applications, bases de données.', 'categorie' => 'Technique & digital'],
            ['nom' => 'Digitalisation des processus', 'description' => 'Adaptation des processus à l\'ère du numérique.', 'categorie' => 'Technique & digital'],
            ['nom' => 'Sécurité informatique & cybersécurité', 'description' => 'Protection, prévention, surveillance.', 'categorie' => 'Technique & digital'],
            ['nom' => 'Numérisation', 'description' => 'Documents, archives, fichiers.', 'categorie' => 'Technique & digital'],
            ['nom' => 'Secrétariat bureautique', 'description' => 'Saisie, rédaction, mise en page, gestion de documents.', 'categorie' => 'Technique & digital'],
            ['nom' => 'Graphisme et identité de marque', 'description' => 'Logo, charte graphique, affiche, flyer, visuel digital.', 'categorie' => 'Technique & digital'],
            ['nom' => 'Prompt Engineering & solutions IA', 'description' => 'Automatisation, IA, solutions intelligentes.', 'categorie' => 'Technique & digital'],
        ];

        foreach ($servicesDag as $service) {
            Service::create($service + ['fournisseur' => 'DAG', 'statut' => 'PUBLIE']);
        }

        foreach ($servicesNovaTech as $service) {
            Service::create($service + ['fournisseur' => 'Nova-Tech', 'statut' => 'PUBLIE']);
        }

        Formule::create([
            'nom' => 'Essentiel',
            'contenu' => 'Community management de base : planification et publication sur 1 à 2 réseaux.',
            'services_inclus' => ['Community management'],
            'statut' => 'PUBLIEE',
        ]);

        Formule::create([
            'nom' => 'Croissance',
            'contenu' => 'Création de contenu + community management + campagne publicitaire ciblée.',
            'services_inclus' => ['Création de contenu', 'Community management', 'Campagnes publicitaires'],
            'populaire' => true,
            'statut' => 'PUBLIEE',
        ]);

        Formule::create([
            'nom' => 'Premium',
            'contenu' => 'Stratégie digitale complète, publicité, boost de comptes, reporting mensuel.',
            'services_inclus' => ['Gestion de projets & stratégie digitale', 'Campagnes publicitaires', 'Boost de comptes et de pages'],
            'statut' => 'PUBLIEE',
        ]);
    }
}
