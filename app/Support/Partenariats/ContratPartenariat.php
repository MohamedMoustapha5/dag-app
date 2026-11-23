<?php

namespace App\Support\Partenariats;

use App\Models\ModeleContrat;
use App\Models\Partenariat;
use App\Support\ContratRedaction;

/**
 * The text of a partnership contract: the active « Contrat partenaire »
 * modèle (or the default one below) with its placeholders filled from the
 * partnership. It is complete on its own: the clause library is written for
 * prestations (payment, delivery) and is not appended.
 */
class ContratPartenariat
{
    public const MODELE_PAR_DEFAUT = <<<'TEXTE'
        Entre Digital Agency Group, ci-après « DAG », d'une part, et {{entreprise}} (secteur : {{secteur}} ; ville : {{ville}}), ci-après « le Partenaire », d'autre part.

        DAG accompagne les entreprises dans leur transformation digitale et forme des talents aux métiers du numérique. Le Partenaire souhaite s'associer durablement à DAG. Les parties conviennent de formaliser leur collaboration par le présent contrat-cadre.

        1. Objet
        Le présent contrat définit le cadre dans lequel DAG et le Partenaire collaborent. Il reconnaît au Partenaire la qualité de « Partenaire officiel de Digital Agency Group » pendant toute sa durée.

        2. Domaines de collaboration
        Les parties collaborent dans les domaines suivants : {{domaines}}. Chaque action précise (prestation, recrutement, formation, événement) fait, si nécessaire, l'objet d'un accord ou d'un devis distinct.

        3. Engagements de DAG
        DAG s'engage à désigner un interlocuteur dédié au Partenaire, à l'informer en priorité de ses programmes, de ses sessions de formation et des profils qu'elle forme, à le faire figurer parmi ses partenaires officiels, et à traiter ses demandes avec diligence.

        4. Engagements du Partenaire
        Le Partenaire s'engage à collaborer de bonne foi et dans le respect de l'image de DAG, à informer DAG de ses besoins dans les domaines de collaboration, et à respecter les conditions propres à chaque prestation ou service qu'il souscrit.

        5. Usage des noms et logos
        Chaque partie autorise l'autre à mentionner son nom et son logo en qualité de partenaire sur ses supports de communication, pendant la durée du contrat. Toute autre utilisation requiert un accord écrit préalable.

        6. Conditions financières
        Le présent contrat-cadre n'emporte aucune contrepartie financière. Les prestations et services souscrits par le Partenaire auprès de DAG, dont l'abonnement recrutement, restent facturés selon leurs conditions propres.

        7. Durée
        Le contrat est conclu pour une durée de {{duree}} à compter de sa validation par DAG. Il peut être renouvelé d'un commun accord.

        8. Confidentialité
        Chaque partie garde confidentielles les informations non publiques de l'autre partie dont elle a connaissance à l'occasion du partenariat, pendant la durée du contrat et deux ans après son terme.

        9. Données personnelles
        Les parties traitent les données personnelles échangées dans le respect de la réglementation applicable, et uniquement pour les besoins du partenariat.

        10. Non-exclusivité
        Le partenariat n'est pas exclusif : chaque partie reste libre de collaborer avec d'autres structures.

        11. Résiliation
        Chaque partie peut mettre fin au contrat par écrit, moyennant un préavis de trente (30) jours. En cas de manquement grave d'une partie à ses engagements, l'autre partie peut le résilier sans préavis, après une mise en demeure restée sans effet pendant quinze (15) jours.

        12. Litiges
        Les parties recherchent d'abord une solution amiable à tout différend. À défaut, le litige est porté devant les juridictions compétentes du ressort du siège de DAG.

        Contrat établi le {{date}} sous forme électronique, référence du partenariat {{reference}}.
        TEXTE;

    public static function rediger(Partenariat $partenariat): string
    {
        $partenariat->loadMissing('client');
        $client = $partenariat->client;

        $modele = ContratRedaction::modeleParDefaut('CONTRAT_PARTENAIRE');

        return trim(strtr($modele->contenu ?? self::MODELE_PAR_DEFAUT, [
            '{{entreprise}}' => $client->nom,
            '{{client}}' => $client->nom,
            '{{secteur}}' => $client->secteur_activite ?: 'non précisé',
            '{{ville}}' => $client->ville ?: 'non précisée',
            '{{domaines}}' => $partenariat->libelleDomaines(),
            '{{duree}}' => $partenariat->libelleDuree(),
            '{{date}}' => now()->format('d/m/Y'),
            '{{reference}}' => $partenariat->reference,
        ]));
    }

    /**
     * The modèle used when none is active.
     */
    public static function modele(): ?ModeleContrat
    {
        return ContratRedaction::modeleParDefaut('CONTRAT_PARTENAIRE');
    }
}
