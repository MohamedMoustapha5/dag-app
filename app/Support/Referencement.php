<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * What search engines and link previews read of a page, written into the
 * HTML by the server (the pages themselves are drawn in the browser).
 *
 * Only the public pages listed here are indexable; the espaces, sign-in
 * and payment screens carry « noindex ». The titles match the <Head> of
 * each React page: both are followed by the agency name, like the browser
 * tab (resources/js/app.tsx).
 */
class Referencement
{
    /**
     * @var array<string, array{titre: string, description: string}>
     */
    private const PAGES = [
        'public/home' => [
            'titre' => 'Digital Agency Group — Agence digitale à Douala, Cameroun',
            'description' => 'Agence digitale à Douala : marketing digital, community management, création de sites internet, publicité Meta et TikTok, formations et placement en entreprise.',
        ],
        'public/catalogue' => [
            'titre' => 'Services digitaux : marketing, sites web, branding',
            'description' => 'Création de contenu, community management, campagnes Meta et TikTok Ads, sites internet, e-commerce, cybersécurité et IA : tous les services de DAG à Douala.',
        ],
        'public/formations' => [
            'titre' => 'Formations digitales et placement en entreprise',
            'description' => 'Formations en community management, création de contenu et marketing digital, au Cameroun et en ligne, avec un accompagnement vers un stage ou un emploi.',
        ],
        'public/abonnement' => [
            'titre' => "Formules d'abonnement marketing digital",
            'description' => 'Un accompagnement digital continu à prix fixe : choisissez la formule DAG adaptée à votre entreprise et suivez vos résultats depuis votre espace client.',
        ],
        'public/grille-tarifaire' => [
            'titre' => 'Grille tarifaire des services digitaux',
            'description' => 'Les tarifs de Digital Agency Group en FCFA : création de contenu, gestion des réseaux sociaux, sites internet, publicité en ligne, graphisme et plus.',
        ],
        'public/offres-emploi' => [
            'titre' => "Offres de stage et d'emploi au Cameroun",
            'description' => 'Stages et emplois proposés par les entreprises partenaires de DAG au Cameroun : consultez les offres et déposez votre candidature en ligne.',
        ],
        'public/a-propos' => [
            'titre' => 'À propos : votre agence digitale à Douala',
            'description' => 'Découvrez Digital Agency Group (DAG), agence digitale basée à Douala : notre équipe, notre mission et notre façon d’accompagner entreprises et talents.',
        ],
        'public/contact' => [
            'titre' => 'Contact : parlons de votre projet digital',
            'description' => 'Parlez-nous de votre projet digital : écrivez à DAG, appelez-nous ou demandez un audit gratuit. Agence digitale basée à Douala, Cameroun.',
        ],
        'public/mentions-legales' => [
            'titre' => 'Mentions légales',
            'description' => 'Mentions légales du site agencydag.com, édité par Digital Agency Group, agence digitale à Douala, Cameroun.',
        ],
        'public/politique-confidentialite' => [
            'titre' => 'Politique de confidentialité',
            'description' => 'Comment Digital Agency Group collecte, utilise et protège vos données personnelles, et comment exercer vos droits.',
        ],
        'public/conditions-utilisation' => [
            'titre' => "Conditions générales d'utilisation",
            'description' => 'Les règles d’utilisation du site et des espaces en ligne de Digital Agency Group.',
        ],
        'public/conditions-vente' => [
            'titre' => 'Conditions générales de vente',
            'description' => 'Les conditions de vente des services et formations de Digital Agency Group : devis, contrats, facturation et paiement.',
        ],
    ];

    public const IMAGE = '/images/brand/partage.jpg';

    /**
     * The public pages listed in the sitemap, by route name.
     *
     * @return list<string>
     */
    public static function routesPubliques(): array
    {
        return [
            'home', 'catalogue', 'formations', 'abonnement', 'grille-tarifaire', 'offres-emploi',
            'a-propos', 'contact', 'mentions-legales', 'politique-confidentialite',
            'conditions-utilisation', 'conditions-vente',
        ];
    }

    /**
     * @param  array{component: string, props?: array<string, mixed>}  $page
     * @return array{titre: string, description: ?string, indexable: bool, canonique: string, image: string, donneesStructurees: list<array<string, mixed>>}
     */
    public static function pour(array $page): array
    {
        $composant = $page['component'];
        $props = $page['props'] ?? [];

        [$titre, $description] = match (true) {
            isset(self::PAGES[$composant]) => [self::PAGES[$composant]['titre'], self::PAGES[$composant]['description']],
            $composant === 'public/formation-detail' && isset($props['formation']['nom']) => [
                'Formation '.$props['formation']['nom'],
                self::resume((string) ($props['formation']['description'] ?? ''))
                    ?: self::PAGES['public/formations']['description'],
            ],
            default => [null, null],
        };

        $donnees = match ($composant) {
            'public/home' => [self::organisation()],
            'public/formation-detail' => $titre === null ? [] : [self::formation($props['formation'], (string) $description)],
            default => [],
        };

        return [
            'titre' => self::titreComplet($titre),
            'description' => $description,
            'indexable' => $titre !== null,
            'canonique' => url()->current(),
            'image' => url(self::IMAGE),
            'donneesStructurees' => $donnees,
        ];
    }

    /** The page title followed by the agency name, as in the browser tab. */
    public static function titreComplet(?string $titre): string
    {
        $nom = config('entreprise.nom');

        if ($titre === null || $titre === '') {
            return $nom;
        }

        return str_contains($titre, $nom) ? $titre : "{$titre} - {$nom}";
    }

    private static function resume(string $texte): string
    {
        return Str::limit(trim((string) preg_replace('/\s+/', ' ', strip_tags($texte))), 155);
    }

    /**
     * The agency card read by Google (name, address, contact details).
     *
     * @return array<string, mixed>
     */
    private static function organisation(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'ProfessionalService',
            'name' => config('entreprise.nom'),
            'alternateName' => 'DAG',
            'description' => self::PAGES['public/home']['description'],
            'url' => url('/'),
            'logo' => url('/images/brand/dag-logo.jpg'),
            'image' => url(self::IMAGE),
            'email' => config('entreprise.email'),
            'telephone' => str_replace(' ', '', (string) config('entreprise.telephone')),
            'address' => [
                '@type' => 'PostalAddress',
                'addressLocality' => 'Douala',
                'addressCountry' => 'CM',
            ],
            'areaServed' => ['@type' => 'Country', 'name' => 'Cameroun'],
            // The agency's own pages elsewhere (as in the site footer).
            'sameAs' => [
                'https://www.facebook.com/share/1JeFmbk479/',
                'https://www.tiktok.com/@digitalagency61',
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $formation
     * @return array<string, mixed>
     */
    private static function formation(array $formation, string $description): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'Course',
            'name' => $formation['nom'],
            'description' => $description,
            'url' => url()->current(),
            'inLanguage' => 'fr',
            'provider' => [
                '@type' => 'Organization',
                'name' => config('entreprise.nom'),
                'sameAs' => url('/'),
            ],
        ];
    }
}
