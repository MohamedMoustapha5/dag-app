<?php

namespace App\Support\Partenariats;

use App\Models\Contrat;
use App\Models\ModeleContrat;
use App\Models\Partenariat;
use App\Support\ContratRedaction;

/**
 * The text of a partnership contract: the active « Contrat partenaire »
 * modèle (or the default one below, written by DAG's lawyer) with its
 * placeholders filled from the partnership. What only the partner knows
 * (legal form, NIU, representative and title) is marked in the text and
 * filled in when the partner signs.
 */
class ContratPartenariat
{
    public const MODELE_PAR_DEFAUT = <<<'TEXTE'
        Le présent contrat de partenariat (ci-après le « Contrat ») est établi et prend effet à compter des signatures des parties et de l'apposition des cachets de l'autorité compétente.

        ENTRE LES SOUSSIGNÉS :

        DIGITAL AGENCY GROUP (ci-après « Partenaire 1 »), Société à Responsabilité Limitée (SARL) constituée et régie par l'Acte Uniforme portant Droit Commercial Général du 15 décembre 2010 et la loi n°2015/018 du 21 décembre 2015 régissant l'activité commerciale au Cameroun, immatriculée sous le numéro du code NIU P010618877302Q, dont le siège social physique est en mise en place à Douala-Cameroun (Akwa), le service en ligne étant disponible.

        Représentée aux fins des présentes par NGOU MANDOU AFSA, Entrepreneure, ci-après désignée « Partenaire 1 », d'une part,

        ET :

        {{entreprise}} (ci-après « Partenaire 2 »), {{forme_juridique}} constituée et régie par l'Acte Uniforme portant Droit Commercial Général du 15 décembre 2010 et la loi n°2015/018 du 21 décembre 2015 régissant l'activité commerciale au Cameroun, immatriculée sous le numéro du code NIU {{niu}}, dont le siège social est établi {{siege}}.

        Représentée aux fins des présentes par {{representant}}, {{qualite}}, ci-après désignée « Partenaire 2 », d'autre part,

        Partenaire 1 et Partenaire 2 étant ci-après désignés individuellement une « Partie » et collectivement les « Parties ».

        PRÉAMBULE

        A. Considérant que les Parties désirent s'associer pour poursuivre des objectifs d'affaires communs dans le domaine du numérique et des technologies ;

        B. Considérant que les Parties ont étudié différentes formes de collaboration et d'exploitation commune pour leurs activités commerciales respectives ;

        C. Considérant que les Parties envisagent le présent Contrat comme étant le moyen le plus approprié pour réaliser leurs objectifs communs, dans le respect de leur autonomie juridique et financière respective ;

        D. Considérant que les Parties s'engagent par les présentes à collaborer conformément aux dispositions légales et réglementaires en vigueur ainsi qu'aux clauses ci-après énoncées, notamment sur le plan technique et technologique ;

        Considérant les engagements mutuels contenus dans le présent Contrat, les Parties conviennent et arrêtent ce qui suit :

        1. Définitions
        Aux fins du présent Contrat, les termes ci-après ont la signification suivante :

        - « Contrat » désigne le présent contrat de partenariat, y compris ses annexes et avenants éventuels ;

        - « Partenariat » désigne la collaboration économique et technique instituée entre les Parties par le présent Contrat, sans création d'une personne morale distincte, sauf stipulation contraire ;

        - « Informations Confidentielles » désigne toute information technique, commerciale, financière ou stratégique échangée entre les Parties dans le cadre de l'exécution du Contrat ;

        - « Livrables » désigne tout développement, code source, documentation technique, spécification, interface ou solution produit dans le cadre du Partenariat ;

        - « Environnement Technique » désigne l'ensemble des infrastructures, systèmes, logiciels, architectures et plateformes utilisés par les Parties pour l'exécution du Partenariat ;

        - « Date d'Effet » désigne la date de signature du présent Contrat par les deux Parties.

        2. Dénomination et siège du partenariat
        Le Partenariat prend la dénomination « Accord de Partenariat sur des contrats portant sur le Service Digital entre DAG et {{entreprise}} ». Son siège opérationnel est fixé sur tout le territoire camerounais, singulièrement dans les villes de {{villes}}, sauf modification décidée d'un commun accord entre les Parties et constatée par écrit.

        3. Objet du contrat
        Le présent Contrat a pour objet de définir les modalités et conditions dans lesquelles Partenaire 1 et Partenaire 2 collaborent en vue du développement de solutions numériques, de prestations de services informatiques et de la mise en place d'outils informatiques sophistiqués dans les entreprises ou auprès des personnes qui en ont besoin.

        Les domaines de collaboration retenus par les Parties sont les suivants : {{domaines}}.

        Toute extension de l'objet du Partenariat à de nouvelles activités devra faire l'objet d'un avenant écrit signé par les deux Parties.

        4. Durée du contrat
        {{clause_duree}}

        5. Apports et contributions des parties
        Chaque Partie s'engage à apporter au Partenariat les ressources et moyens suivants :

        - Partenaire 1 : la réalisation des contrats portant sur des travaux réseau de clientèle et marketing digital, principalement sur la création de contenu, le community management, le social media management, la gestion de projet et stratégie digitale, les campagnes publicitaires, le boost de comptes, la monétisation TikTok et Facebook, la formation et le placement en entreprise, ainsi que le développement web, l'hébergement et la digitalisation des processus de cybersécurité ;

        - Partenaire 2 : l'apport des contrats portant sur des travaux réseau de clientèle et marketing digital pour la réalisation des services susmentionnés, et l'expertise sur la technique et la technologie.

        Les apports respectifs pourront être révisés d'un commun accord et consignés dans une annexe technique au présent Contrat.

        6. Gouvernance et gestion du partenariat
        Les Parties conviennent de mettre en place un comité de pilotage composé de représentants désignés par chacune d'elles, chargé du suivi de l'exécution du présent Contrat et de la coordination des actions communes, y compris le suivi de l'avancement technique des projets.

        Ce comité se réunit selon la périodicité nécessaire, en urgence en cas de litige ou à la demande de l'une des Parties. Les décisions relatives à la gestion courante du Partenariat sont prises d'un commun accord ; à défaut d'accord, la question est portée devant les représentants légaux des Parties.

        Chaque Partie conserve son entière autonomie juridique, administrative et financière ; le présent Contrat n'emporte ni création de société commune, ni lien de subordination entre les Parties, sauf stipulation contraire expresse.

        7. Répartition des résultats et des charges
        Les revenus, bénéfices et charges résultant des activités menées dans le cadre du Partenariat seront répartis entre les Parties selon les proportions suivantes : Partenaire 1 (70 %) et Partenaire 2 (30 %), et vice versa, une fois que le contrat est exécuté et que le client a effectué le versement, sauf accord contraire consigné par écrit pour un projet déterminé.

        Chaque Partie supporte ses propres charges d'exploitation, sauf disposition contraire prévue dans une annexe spécifique à un projet commun.

        8. Obligations générales des parties
        Chaque Partie s'engage à :

        - exécuter de bonne foi les obligations mises à sa charge par le présent Contrat ;

        - mettre à disposition les moyens humains, matériels et financiers nécessaires à la bonne exécution du Partenariat ;

        - informer sans délai l'autre Partie de tout événement susceptible d'affecter l'exécution du Contrat, y compris tout incident technique majeur ;

        - respecter les lois et règlements applicables à ses activités, notamment en matière fiscale, sociale et de protection des données à caractère personnel ;

        - s'abstenir de tout acte susceptible de nuire à la réputation, à l'image ou aux intérêts légitimes de l'autre Partie.

        9. Spécifications techniques et livrables
        Les caractéristiques techniques, fonctionnelles et de performance des Livrables réalisés dans le cadre du Partenariat sont décrites dans un cahier des charges technique annexé au présent Contrat (Annexe Technique), lequel précise notamment : l'architecture logicielle et matérielle retenue, les langages, frameworks et environnements de développement utilisés, les formats d'échange de données, ainsi que les jalons et délais de livraison.

        Toute modification substantielle des spécifications techniques initiales devra faire l'objet d'un accord écrit préalable entre les Parties, formalisé par un avenant à l'Annexe Technique, précisant notamment l'impact sur les délais, les coûts et la répartition des responsabilités.

        Partenaire 2 s'engage à fournir à Partenaire 1 une documentation technique complète et à jour de tout Livrable (documentation d'installation, d'exploitation, d'architecture et guide utilisateur), rédigée en langue française.

        10. Niveaux de service et performance (SLA)
        Les Parties conviennent de niveaux de service (Service Level Agreement) applicables aux prestations techniques exécutées dans le cadre du Partenariat, portant notamment sur :

        - le taux de disponibilité minimal des systèmes et plateformes concernés ;

        - les délais maximaux de prise en charge et de résolution des incidents, selon leur niveau de criticité (critique, majeur, mineur), précisés en Annexe Technique ;

        - les fenêtres de maintenance planifiée, notifiées avec un préavis minimal ;

        - les indicateurs de performance (KPI) faisant l'objet d'un reporting périodique entre les Parties selon une fréquence mensuelle.

        Le non-respect répété des niveaux de service convenus pourra donner lieu à des mesures correctives, voire à l'application de pénalités dont les modalités sont définies en Annexe Technique.

        11. Sécurité des systèmes et protection des données
        Chaque Partie s'engage à mettre en œuvre les mesures techniques et organisationnelles appropriées afin de garantir la sécurité, l'intégrité, la disponibilité et la confidentialité des systèmes, données et infrastructures utilisés dans le cadre du Partenariat, conformément aux normes et standards en vigueur (notamment en matière de protection des données à caractère personnel).

        Les Parties s'engagent réciproquement à notifier sans délai toute violation de données ou incident de sécurité susceptible d'affecter le Partenariat, et à coopérer de bonne foi pour en limiter les conséquences et y remédier.

        Les accès aux systèmes et environnements techniques de l'une des Parties par le personnel de l'autre Partie sont strictement limités aux besoins de l'exécution du présent Contrat et soumis aux politiques de sécurité de la Partie hébergeant lesdits systèmes.

        12. Interopérabilité et normes techniques
        Les Livrables et solutions développés dans le cadre du Partenariat devront respecter les normes, standards ouverts et protocoles d'interopérabilité applicables à leur domaine (notamment en matière d'échange de données, d'API et de sécurité), tels que précisés en Annexe Technique, afin d'assurer leur compatibilité avec les systèmes existants de chaque Partie.

        Toute dérogation à ces normes devra être justifiée techniquement et validée par le comité de pilotage visé à l'article 6.

        13. Tests, recette et validation technique
        Tout Livrable fait l'objet d'une phase de tests et de recette technique préalablement à sa mise en production, selon un plan de tests défini d'un commun accord et annexé au présent Contrat.

        La recette est réputée acquise à défaut de réserves formulées par écrit par la Partie destinataire à compter de la mise à disposition du Livrable. En cas de réserves, Partenaire 2 dispose d'un délai d'un (01) mois pour procéder aux corrections nécessaires, sans frais supplémentaires si les anomalies relèvent de non-conformités aux spécifications techniques initiales.

        14. Maintenance et support technique
        À compter de la mise en production de chaque Livrable, une phase de maintenance corrective et évolutive est assurée, incluant notamment un support technique selon les plages horaires à allouer.

        Les demandes d'évolution fonctionnelle ou technique non prévues au périmètre initial font l'objet d'un chiffrage et d'une planification distincts, formalisés par avenant.

        15. Propriété intellectuelle et code source
        Chaque Partie demeure titulaire exclusive des droits de propriété intellectuelle (marques, brevets, logiciels, codes sources, savoir-faire, bases de données) qu'elle détenait antérieurement à la conclusion du présent Contrat ou qu'elle développe de manière autonome pendant son exécution.

        Les droits de propriété intellectuelle sur les développements, codes sources, algorithmes ou Livrables résultant conjointement de la collaboration technique entre les Parties seront détenus en copropriété, selon des modalités à définir dans une annexe spécifique, ou reviendront à la Partie désignée d'un commun accord pour chaque projet.

        Sauf stipulation contraire, le code source spécifiquement développé pour les besoins du Partenariat sera déposé auprès d'un tiers séquestre (« dépôt de code source »), selon des modalités à convenir, afin d'en garantir la conservation et la continuité d'exploitation.

        Aucune Partie ne pourra utiliser la dénomination sociale, les marques ou les logos de l'autre Partie sans son autorisation écrite préalable.

        16. Confidentialité
        Chaque Partie s'engage à conserver strictement confidentielles toutes les Informations Confidentielles reçues de l'autre Partie à l'occasion de l'exécution du présent Contrat, y compris les informations techniques, codes sources, architectures et méthodes, à ne les divulguer à aucun tiers et à ne les utiliser qu'aux fins de l'exécution du Partenariat.

        Cette obligation de confidentialité demeure applicable pendant toute la durée du Contrat et pour une période indéterminée suivant son terme, quelle qu'en soit la cause, sauf pour les informations tombées dans le domaine public sans faute de la Partie réceptrice, ou dont la divulgation est exigée par une autorité administrative ou judiciaire compétente.

        17. Non-concurrence et non-sollicitation
        Pendant la durée du présent Contrat et pour une période indéterminée suivant sa cessation, chaque Partie s'engage à ne pas exercer, directement ou indirectement, pour son propre compte ou celui d'un tiers, une activité concurrente à celle objet du Partenariat, sans l'accord écrit préalable de l'autre Partie.

        Chaque Partie s'engage également à ne pas solliciter ou débaucher, directement ou indirectement, le personnel technique ou commercial de l'autre Partie affecté à l'exécution du présent Contrat, pendant la même période.

        18. Assurance et responsabilité
        Chaque Partie demeure responsable des dommages causés à l'autre Partie ou à des tiers du fait de sa propre faute, négligence ou inexécution de ses obligations contractuelles, notamment techniques, dans les conditions du droit commun.

        Chaque Partie s'engage à souscrire et maintenir en vigueur, pendant toute la durée du Contrat, les polices d'assurance nécessaires à la couverture des risques liés à ses activités, y compris les risques informatiques et cyber le cas échéant.

        En aucun cas la responsabilité d'une Partie ne pourra être engagée pour les dommages indirects, tels que la perte de chiffre d'affaires, de clientèle, de données ou d'image, subis par l'autre Partie.

        19. Force majeure
        Aucune des Parties ne pourra être tenue responsable de l'inexécution ou du retard dans l'exécution de l'une quelconque de ses obligations au titre du présent Contrat si cette inexécution ou ce retard résulte d'un cas de force majeure tel que défini par la jurisprudence et la loi applicable, notamment : catastrophe naturelle, guerre, émeute, grève générale, décision d'une autorité publique, panne générale des réseaux de télécommunication ou d'électricité, cyber-attaque d'ampleur exceptionnelle.

        La Partie invoquant un cas de force majeure devra en informer l'autre Partie par écrit dans un délai de sept (7) jours à compter de sa survenance. Si l'événement de force majeure perdure au-delà de sept (7) jours, chaque Partie pourra résilier le présent Contrat de plein droit, sans indemnité de part ni d'autre.

        20. Résiliation et réversibilité technique
        Le présent Contrat pourra être résilié de plein droit par l'une des Parties, sans préjudice de tous dommages et intérêts éventuels, en cas de :

        - manquement grave ou répété par l'autre Partie à l'une quelconque de ses obligations contractuelles, notamment techniques, non réparé dans un délai de quinze (15) jours suivant une mise en demeure écrite restée sans effet ;

        - cessation d'activité, dissolution, redressement ou liquidation judiciaire de l'une des Parties ;

        - survenance d'un cas de force majeure dans les conditions prévues à l'article 19 ;

        - accord mutuel des Parties, constaté par écrit.

        En cas de résiliation ou d'échéance du Contrat, les Parties conviennent d'organiser une phase de réversibilité technique, permettant la restitution des données, codes sources, accès et documentations nécessaires à la continuité d'exploitation, selon des modalités et un délai précisés en Annexe Technique.

        La résiliation du présent Contrat n'affecte pas les droits et obligations nés antérieurement à sa date d'effet, ni les clauses qui, par leur nature, ont vocation à survivre à la résiliation (notamment confidentialité, propriété intellectuelle, sécurité des données, non-concurrence et règlement des différends).

        21. Cession du contrat
        Aucune des Parties ne pourra céder ou transférer, à titre onéreux ou gratuit, tout ou partie de ses droits et obligations résultant du présent Contrat, sans l'accord écrit préalable de l'autre Partie, sauf en cas de cession au profit d'une société affiliée ou dans le cadre d'une restructuration interne, sous réserve d'en informer préalablement l'autre Partie.

        22. Indépendance des clauses
        Si l'une quelconque des clauses du présent Contrat venait à être déclarée nulle ou inapplicable par une juridiction compétente, les autres clauses demeureraient pleinement valides et applicables. Les Parties s'efforceront alors de remplacer la clause invalidée par une clause valide dont l'effet économique et juridique se rapprochera le plus possible de celui recherché par la clause initiale.

        23. Notifications
        Toute notification ou communication relative au présent Contrat devra être effectuée par écrit et adressée par lettre recommandée avec accusé de réception, par exploit d'huissier, ou par courrier électronique avec confirmation de réception, aux adresses des Parties mentionnées en tête des présentes, ou à toute autre adresse notifiée conformément au présent article.

        24. Élection de domicile
        Pour l'exécution du présent Contrat et de ses suites, les Parties font élection de domicile à leurs sièges sociaux respectifs, tels qu'indiqués en tête des présentes.

        25. Droit applicable et règlement des différends
        Le présent Contrat est régi par le droit OHADA et le droit camerounais. Tout différend relatif à la validité, l'interprétation ou l'exécution du présent Contrat, y compris les différends de nature technique, fera l'objet d'une tentative de règlement amiable entre les Parties, le cas échéant devant le comité de pilotage visé à l'article 6. À défaut d'accord amiable dans un délai de six (6) mois à compter de la notification du différend par l'une des Parties à l'autre, le litige sera soumis aux juridictions compétentes de {{tribunaux}}, selon la règle ratione materiae, en fonction du montant du litige. En cas de recours, la Cour Commune de Justice et d'Arbitrage (CCJA) de l'OHADA, située à Abidjan (Côte d'Ivoire), est compétente.

        26. Dispositions générales et finales
        Le présent Contrat constitue l'intégralité de l'accord entre les Parties relatif à son objet et annule et remplace tout accord, écrit ou verbal, antérieur ayant le même objet.

        Le fait pour l'une des Parties de ne pas se prévaloir d'un manquement de l'autre Partie à l'une quelconque des obligations visées au présent Contrat ne saurait être interprété comme une renonciation à se prévaloir dans l'avenir dudit manquement.

        Toute modification du présent Contrat, y compris de son Annexe Technique, devra faire l'objet d'un avenant écrit, daté et signé par les représentants dûment habilités des deux Parties.

        Le présent Contrat est rédigé en langue française et signé électroniquement sur la plateforme Digital Agency Group ; chaque Partie en conserve un exemplaire, téléchargeable depuis son espace.

        En foi de quoi, les Parties ont signé le présent Contrat de partenariat (référence {{reference}}, établi le {{date}}).
        TEXTE;

    /**
     * What only the partner knows, shown as such in the text until it signs.
     *
     * @var array<string, string>
     */
    public const A_COMPLETER = [
        '{{forme_juridique}}' => '[forme juridique, à compléter à la signature]',
        '{{niu}}' => '[NIU, à compléter à la signature]',
        '{{representant}}' => '[nom du représentant, à compléter à la signature]',
        '{{qualite}}' => '[qualité du représentant, à compléter à la signature]',
    ];

    public static function rediger(Partenariat $partenariat): string
    {
        $partenariat->loadMissing('client');
        $client = $partenariat->client;
        $ville = trim((string) $client->ville);
        $autreVille = $ville !== '' && ! in_array(mb_strtolower($ville), ['douala', 'yaoundé', 'yaounde'], true);

        $modele = ContratRedaction::modeleParDefaut('CONTRAT_PARTENAIRE');

        return trim(strtr($modele->contenu ?? self::MODELE_PAR_DEFAUT, [
            ...self::A_COMPLETER,
            '{{entreprise}}' => $client->nom,
            '{{client}}' => $client->nom,
            '{{secteur}}' => $client->secteur_activite ?: 'non précisé',
            '{{ville}}' => $ville !== '' ? $ville : 'non précisée',
            '{{siege}}' => $ville !== '' ? "à {$ville} (Cameroun)" : 'au Cameroun',
            '{{villes}}' => $autreVille ? "Douala, Yaoundé et {$ville}" : 'Douala et Yaoundé',
            '{{tribunaux}}' => $ville !== '' && mb_strtolower($ville) !== 'douala' ? "Douala ou de {$ville}" : 'Douala',
            '{{domaines}}' => $partenariat->libelleDomaines(),
            '{{duree}}' => $partenariat->libelleDuree(),
            '{{clause_duree}}' => self::clauseDuree($partenariat),
            '{{date}}' => now()->format('d/m/Y'),
            '{{reference}}' => $partenariat->reference,
        ]));
    }

    /**
     * Fills in what the partner gave when signing.
     *
     * @param  array{forme_juridique: string, niu: string, representant: string, qualite: string}  $informations
     */
    public static function completer(string $contenu, array $informations): string
    {
        return strtr($contenu, [
            self::A_COMPLETER['{{forme_juridique}}'] => $informations['forme_juridique'],
            self::A_COMPLETER['{{niu}}'] => $informations['niu'],
            self::A_COMPLETER['{{representant}}'] => $informations['representant'],
            self::A_COMPLETER['{{qualite}}'] => $informations['qualite'],
        ]);
    }

    /**
     * Whether the partner gives its company details when signing.
     */
    public static function exigeInformations(Contrat $contrat): bool
    {
        return $contrat->type === 'CONTRAT_PARTENAIRE';
    }

    /**
     * The duration article: open-ended and tacitly renewed as the lawyer
     * wrote it, or the fixed duration DAG granted.
     */
    private static function clauseDuree(Partenariat $partenariat): string
    {
        if ($partenariat->estDureeIndeterminee()) {
            return "Le présent Contrat est conclu pour une durée indéterminée, à compter de la Date d'Effet. Il pourra être renouvelé par tacite reconduction pour des périodes successives de même durée, sauf dénonciation par l'une des Parties notifiée par écrit à l'autre Partie, ou résilié par anticipation conformément à l'article Résiliation ci-après.";
        }

        return "Le présent Contrat est conclu pour une durée de {$partenariat->libelleDuree()}, à compter de la Date d'Effet. Il pourra être renouvelé d'un commun accord des Parties, constaté par écrit, ou résilié par anticipation conformément à l'article Résiliation ci-après.";
    }

    /**
     * The modèle used when none is active.
     */
    public static function modele(): ?ModeleContrat
    {
        return ContratRedaction::modeleParDefaut('CONTRAT_PARTENAIRE');
    }
}
