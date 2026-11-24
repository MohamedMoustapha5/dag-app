<?php

namespace Database\Seeders;

use App\Models\Clause;
use App\Models\ModeleContrat;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Installs the DAG contracts written by the juriste, each article followed
 * by its legal basis (« Base légale – … »).
 *
 * The prestation contract is the modèle's preamble followed by the clause
 * library, which the team can edit article by article. The subscription
 * contract adapts the same text to a subscription (price on devis, period
 * up to the due date, renewal through a new devis) and carries its own
 * articles, so the clause library is not appended to it.
 *
 * The identity of the client (ID card, phone, city, place of service) is
 * given when signing and printed in the "Parties" block of the contract.
 *
 * Safe to run again: it replaces the clause library and re-activates these
 * modèles. Contracts already drafted keep the text they were drafted with.
 */
class ContratDagSeeder extends Seeder
{
    public const NOM_MODELE_PRESTATION = 'Contrat DAG — Prestation de service';

    public const NOM_MODELE_ABONNEMENT = 'Contrat DAG — Abonnement';

    /** Titles of the clause library installed before the legal bases were supplied. */
    public const TITRES_SANS_BASE_LEGALE = [
        'Objet de la demande', 'Coût du service', 'Délai de prestation', 'Rectification du contrat',
        'Annulation', 'Confidentialité', 'Réservation',
    ];

    private const BASE_OBJET = "Base légale – Objet / nature du contrat : contrat de louage d'ouvrage (prestation de services contre prix) – Code civil, art. 1101 (définition du contrat), 1108 (conditions de validité : consentement, capacité, objet certain, cause licite), 1129 (objet déterminé ou déterminable), 1710 et 1779 (louage d'ouvrage et d'industrie). Loi n° 2011/012 du 6 mai 2011 portant protection du consommateur, art. 13 (information juste, suffisante, claire et lisible avant la conclusion du contrat).";

    private const BASE_IDENTITE = 'Base légale – Identité, téléphone, CNI, résidence : capacité et consentement du client – Code civil, art. 1108, 1109 (vices du consentement) et 1123 (capacité de contracter). Les données personnelles collectées (nom, téléphone, CNI, résidence) relèvent de la Loi n° 2024/017 du 23 décembre 2024 relative à la protection des données à caractère personnel (en vigueur depuis le 23 juin 2026) : finalité déterminée, minimisation (collecter uniquement le nécessaire), consentement libre, éclairé et explicite du client, sécurité et confidentialité des données.';

    private const BASE_COUT = 'Base légale – Coût du service : prix convenu entre les parties – Code civil, art. 1710 (prix convenu), 1129 (quotité déterminable) et 1134 (force obligatoire du contrat). Loi n° 2011/012, art. 13 (le prix doit être communiqué avant la conclusion du contrat) et art. 6 al. 3 (chaque partie reçoit et conserve un exemplaire du document constatant la transaction).';

    private const BASE_DELAI = "Base légale – Délai de prestation : Code civil, art. 1134 (force obligatoire), 1142 (inexécution d'une obligation de faire : dommages-intérêts), 1146 (mise en demeure préalable), 1147 (dommages-intérêts en cas de retard ou d'inexécution), 1148 (force majeure : aucune indemnité due) et 1184 (résolution pour inexécution).";

    private const BASE_RECTIFICATION = "Base légale – Rectification du contrat : Code civil, art. 1134 al. 2 et 3 (modification par consentement mutuel ; exécution de bonne foi) et 1135 (suites que l'équité donne à l'obligation). Cette limitation s'applique sans préjudice des droits du client prévus par la Loi n° 2011/012, art. 31 (demande de révision ou d'annulation du contrat, sans préjudice des dommages-intérêts), ainsi que des art. 5 et 35 (nullité des clauses abusives ou limitant la responsabilité du prestataire).";

    private const BASE_ANNULATION = "Base légale – Annulation / remboursement : Code civil, art. 1134 (force obligatoire), 1152 (clause pénale : indemnité fixée à l'avance), 1184 (résolution) et 1794 (le maître de l'ouvrage peut résilier le marché par sa seule volonté, à charge de dédommager l'entrepreneur de ses dépenses, de ses travaux et de son gain manqué). Loi n° 2011/012, art. 7 (faculté de rétractation du consommateur, délai ne pouvant excéder quatorze (14) jours), art. 5 et 35 (clauses abusives réputées nulles) et art. 28 (charge de la preuve au prestataire).";

    private const BASE_CONFIDENTIALITE = "Base légale – Confidentialité : Code civil, art. 1134 (force obligatoire, bonne foi), 1135 (équité) et 1382 (réparation du dommage causé par la violation de l'engagement). Loi n° 2024/017 du 23 décembre 2024 (confidentialité et sécurité des données personnelles du client).";

    private const BASE_RESERVATION = "Base légale – Réservation / arrhes : Code civil, art. 1590 (arrhes : chacune des parties peut se départir de la promesse, celui qui les a données en les perdant – appliqué par analogie à la réservation) et 1134 (force obligatoire). Loi n° 2011/012, art. 6 al. 3 (remise d'un reçu ou document justificatif).";

    private const BASE_SIGNATURE = "Base légale – Signature, preuve et langue : Code civil, art. 1134 (force obligatoire), 1322 (acte sous seing privé reconnu : même foi que l'acte authentique), 1325 (autant d'originaux que de parties) et 1328 (date certaine par l'enregistrement). Loi n° 2011/012, art. 6 al. 1 et 3 (contrats types rédigés en français et en anglais, en caractères lisibles ; remise d'un exemplaire à chaque partie). Règlement des litiges : Loi n° 2011/012, art. 26 à 30 (action individuelle ou collective, comité de recours d'arrondissement).";

    private const PARTIE_DAG = 'Digital Agency Group, Société à Responsabilité Limitée (SARL) immatriculée sous le numéro NIU P010618877302Q, dont le siège est à Douala (Akwa), Cameroun, représentée par NGOU MANDOU AFSA, CEO, ci-après « DAG »';

    public function run(): void
    {
        DB::transaction(function (): void {
            Clause::query()->delete();

            foreach (self::articles() as $ordre => $article) {
                Clause::create([...$article, 'ordre' => $ordre]);
            }

            $this->activerModele(self::NOM_MODELE_PRESTATION, 'PRESTATION_SERVICE', self::modelePrestation());
            $this->activerModele(self::NOM_MODELE_ABONNEMENT, 'ABONNEMENT', self::modeleAbonnement());
        });
    }

    /**
     * The preamble of the prestation contract; its articles are the clause library.
     */
    public static function modelePrestation(): string
    {
        return implode("\n\n", [
            'Le présent contrat de prestation de service est conclu entre '.self::PARTIE_DAG.", d'une part, et {{client}}, ci-après « l'intéressé », dont l'identité figure en tête du contrat, d'autre part.",
            'Il se rapporte à la demande {{reference}} et a été établi le {{date}}.',
        ]);
    }

    /**
     * The articles of the prestation contract, in order.
     *
     * @return list<array{titre: string, contenu: string}>
     */
    public static function articles(): array
    {
        return [
            [
                'titre' => 'Objet de la demande',
                'contenu' => self::paragraphes(
                    'Le présent contrat porte sur : {{service}}.',
                    "Détail de la demande de l'intéressé : {{description}}",
                    self::BASE_OBJET,
                ),
            ],
            [
                'titre' => "Identification de l'intéressé",
                'contenu' => self::paragraphes(
                    "Au moment de signer, l'intéressé renseigne ses nom et prénom, son numéro de téléphone, les informations de sa carte nationale d'identité (numéro, lieu et date de délivrance), sa ville de résidence et le quartier de la prestation. Ces informations figurent en tête du présent contrat.",
                    "Elles sont collectées uniquement pour l'exécution du présent contrat ; DAG en assure la sécurité et la confidentialité.",
                    self::BASE_IDENTITE,
                ),
            ],
            [
                'titre' => 'Coût du service',
                'contenu' => self::paragraphes(
                    "Le coût du service est fixé à {{montant}} FCFA, selon l'évaluation de la Gérante de DAG ou, pour un service à prix forfaitaire, selon le prix unitaire multiplié par la quantité souhaitée par l'intéressé. Ce prix est communiqué à l'intéressé, par devis, avant la conclusion du présent contrat.",
                    self::BASE_COUT,
                ),
            ],
            [
                'titre' => 'Délai de prestation',
                'contenu' => self::paragraphes(
                    'La prestation est réalisée dans un délai de trois (03) jours minimum et de sept (07) jours maximum à compter de la signature du présent contrat.',
                    self::BASE_DELAI,
                ),
            ],
            [
                'titre' => 'Rectification du contrat',
                'contenu' => self::paragraphes(
                    "L'intéressé dispose de deux (02) possibilités de révision du présent contrat. Au-delà de deux (02) demandes de rectification, un nouveau contrat doit être généré, puis lu et approuvé par l'Administration de DAG.",
                    self::BASE_RECTIFICATION,
                ),
            ],
            [
                'titre' => 'Annulation',
                'contenu' => self::paragraphes(
                    "En cas d'annulation de la commande par l'intéressé, le remboursement de la somme versée s'effectue comme suit :",
                    "- annulation le jour même de la commande : l'intéressé est intégralement remboursé ;",
                    "- annulation le lendemain de la commande : l'intéressé est remboursé des trois quarts (3/4) de la somme versée ;",
                    "- annulation le jour de la livraison : l'intéressé est remboursé de la moitié (1/2) de la somme versée ;",
                    "- une fois la livraison et les tâches effectuées, l'intéressé ne peut plus demander la restitution de la somme versée.",
                    self::BASE_ANNULATION,
                ),
            ],
            [
                'titre' => 'Confidentialité',
                'contenu' => self::paragraphes(
                    "Le présent contrat, signé et approuvé par les parties, reste strictement confidentiel entre l'intéressé et la société DAG. Il ne peut faire l'objet d'aucune divulgation, sauf en cas de force majeure.",
                    self::BASE_CONFIDENTIALITE,
                ),
            ],
            [
                'titre' => 'Réservation',
                'contenu' => self::paragraphes(
                    "Après présentation du contrat généré par DAG, l'intéressé peut réserver la prestation en versant des arrhes de mille (1 000) francs CFA, valables deux (02) jours. Ces arrhes restent acquises à DAG si l'intéressé ne signe pas définitivement le contrat ; un reçu lui est remis.",
                    self::BASE_RESERVATION,
                ),
            ],
            [
                'titre' => 'Signature et preuve',
                'contenu' => self::paragraphes(
                    "Le présent contrat est rédigé en français et signé électroniquement sur la plateforme Digital Agency Group : l'intéressé importe l'image de sa signature manuscrite, et la date, l'heure et l'adresse IP de la signature sont conservées comme preuve. DAG y appose son cachet et la signature de sa représentante.",
                    "Chaque partie dispose d'un exemplaire du contrat signé, que l'intéressé peut télécharger à tout moment depuis son espace.",
                    self::BASE_SIGNATURE,
                ),
            ],
        ];
    }

    /**
     * The subscription contract: the DAG contract adapted to a formule paid
     * for a period, with its own articles.
     */
    public static function modeleAbonnement(): string
    {
        $articles = [
            "Objet de l'abonnement" => self::paragraphes(
                "Le présent contrat a pour objet la fourniture par DAG, de manière continue pendant la période d'abonnement, des prestations comprises dans la formule {{formule}}.",
                "Détail de la demande de l'abonné : {{description}}",
                self::BASE_OBJET,
            ),
            "Identification de l'abonné" => self::paragraphes(
                "Au moment de signer, l'abonné renseigne ses nom et prénom, son numéro de téléphone, les informations de sa carte nationale d'identité (numéro, lieu et date de délivrance), sa ville de résidence et le lieu de la prestation. Ces informations figurent en tête du présent contrat.",
                "Elles sont collectées uniquement pour l'exécution du présent contrat ; DAG en assure la sécurité et la confidentialité.",
                self::BASE_IDENTITE,
            ),
            "Prix de l'abonnement" => self::paragraphes(
                "Le prix de l'abonnement est fixé à {{montant}} FCFA pour la période d'abonnement, selon le devis accepté par l'abonné avant la conclusion du présent contrat. Il est payable en ligne, intégralement, avant le démarrage des prestations.",
                self::BASE_COUT,
            ),
            'Durée et renouvellement' => self::paragraphes(
                "L'abonnement prend effet à la confirmation du paiement et court jusqu'à l'échéance indiquée dans l'espace de l'abonné.",
                "À l'échéance, l'abonné peut demander le renouvellement de son abonnement depuis son espace ; chaque renouvellement fait l'objet d'un nouveau devis et d'un nouveau contrat. À défaut de renouvellement, l'abonnement prend fin à l'échéance, sans autre formalité.",
            ),
            'Démarrage et exécution des prestations' => self::paragraphes(
                "DAG démarre les prestations dans un délai de trois (03) jours minimum et de sept (07) jours maximum à compter de la signature du présent contrat et de la confirmation du paiement, puis les assure de manière continue jusqu'à l'échéance.",
                self::BASE_DELAI,
            ),
            'Rectification du contrat' => self::paragraphes(
                "L'abonné dispose de deux (02) possibilités de révision du présent contrat. Au-delà de deux (02) demandes de rectification, un nouveau contrat doit être généré, puis lu et approuvé par l'Administration de DAG.",
                self::BASE_RECTIFICATION,
            ),
            'Annulation et résiliation' => self::paragraphes(
                "En cas d'annulation de l'abonnement par l'abonné, le remboursement de la somme versée s'effectue comme suit :",
                "- annulation le jour même de la souscription : l'abonné est intégralement remboursé ;",
                "- annulation le lendemain de la souscription : l'abonné est remboursé des trois quarts (3/4) de la somme versée ;",
                "- annulation ultérieure, avant le démarrage des prestations : l'abonné est remboursé de la moitié (1/2) de la somme versée ;",
                "- une fois les prestations de la période démarrées, la période en cours reste due et n'est pas remboursée.",
                "L'abonné peut en outre mettre fin à son abonnement à tout moment en ne le renouvelant pas : il prend alors fin à l'échéance de la période en cours, sans frais supplémentaires.",
                self::BASE_ANNULATION,
            ),
            'Confidentialité' => self::paragraphes(
                "Le présent contrat, signé et approuvé par les parties, reste strictement confidentiel entre l'abonné et la société DAG. Il ne peut faire l'objet d'aucune divulgation, sauf en cas de force majeure.",
                self::BASE_CONFIDENTIALITE,
            ),
            'Réservation' => self::paragraphes(
                "Après présentation du contrat généré par DAG, l'abonné peut réserver son abonnement en versant des arrhes de mille (1 000) francs CFA, valables deux (02) jours. Ces arrhes restent acquises à DAG si l'abonné ne signe pas définitivement le contrat ; un reçu lui est remis.",
                self::BASE_RESERVATION,
            ),
            'Signature et preuve' => self::paragraphes(
                "Le présent contrat est rédigé en français et signé électroniquement sur la plateforme Digital Agency Group : l'abonné importe l'image de sa signature manuscrite, et la date, l'heure et l'adresse IP de la signature sont conservées comme preuve. DAG y appose son cachet et la signature de sa représentante.",
                "Chaque partie dispose d'un exemplaire du contrat signé, que l'abonné peut télécharger à tout moment depuis son espace.",
                self::BASE_SIGNATURE,
            ),
        ];

        $numero = 0;

        return implode("\n\n", [
            "Le présent contrat d'abonnement à la formule {{formule}} est conclu entre ".self::PARTIE_DAG.", d'une part, et {{client}}, ci-après « l'abonné », dont l'identité figure en tête du contrat, d'autre part.",
            'Il se rapporte à la demande {{reference}} et a été établi le {{date}}.',
            ...array_map(
                function (string $titre, string $texte) use (&$numero): string {
                    $numero++;

                    return "{$numero}. {$titre}\n{$texte}";
                },
                array_keys($articles),
                $articles,
            ),
        ]);
    }

    private static function paragraphes(string ...$paragraphes): string
    {
        return implode("\n\n", $paragraphes);
    }

    /**
     * Creates (or updates) the modèle and makes it the active one of its
     * type; the other modèles of that type are kept, as drafts.
     */
    private function activerModele(string $nom, string $type, string $contenu): void
    {
        ModeleContrat::query()
            ->where('type', $type)
            ->where('nom', '!=', $nom)
            ->update(['statut' => 'BROUILLON']);

        ModeleContrat::updateOrCreate(
            ['nom' => $nom],
            ['type' => $type, 'contenu' => $contenu, 'statut' => 'ACTIF'],
        );
    }
}
