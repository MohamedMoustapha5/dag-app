import { Link } from '@inertiajs/react';
import PageJuridique from '@/components/public/page-juridique';
import type { SectionJuridique } from '@/components/public/page-juridique';
import { conditionsUtilisation, politiqueConfidentialite } from '@/routes';

const SECTIONS: SectionJuridique[] = [
    {
        id: 'champ',
        titre: "Champ d'application",
        contenu: (
            <p>
                Les présentes conditions générales de vente (« CGV »)
                s'appliquent à toutes les prestations vendues par DAG — Digital
                Agency Group (« DAG ») : services à la demande, formules
                d'abonnement et formations. Elles complètent le devis et le
                contrat propres à chaque commande, qui prévalent en cas de
                contradiction.
            </p>
        ),
    },
    {
        id: 'commande',
        titre: 'Devis et commande',
        contenu: (
            <>
                <p>
                    Toute prestation fait l'objet d'un devis détaillé, établi à
                    partir de votre demande. Le devis précise le contenu, les
                    délais et le prix ; il est valable pour la durée qui y est
                    indiquée.
                </p>
                <p>
                    La commande est ferme une fois le devis accepté et le
                    contrat signé par les deux parties depuis la plateforme.
                    L'inscription à une formation est confirmée après validation
                    du paiement des frais d'inscription par DAG.
                </p>
            </>
        ),
    },
    {
        id: 'prix',
        titre: 'Prix',
        contenu: (
            <p>
                Les prix sont exprimés en francs CFA (FCFA). Les tarifs indiqués
                sur la grille tarifaire et les formules sont donnés à titre
                indicatif ; seul le prix figurant sur le devis accepté, ou les
                frais affichés pour la session de formation choisie, engage DAG.
            </p>
        ),
    },
    {
        id: 'paiement',
        titre: 'Modalités de paiement',
        contenu: (
            <>
                <p>
                    Les paiements s'effectuent par les moyens proposés sur la
                    plateforme, selon l'échéancier prévu au contrat. Chaque
                    paiement validé donne lieu à une facture disponible dans
                    votre espace.
                </p>
                <p>
                    Les abonnements sont payables à chaque échéance. En cas de
                    retard, et après relance restée sans effet, DAG peut
                    suspendre la prestation jusqu'au règlement des sommes dues.
                </p>
            </>
        ),
    },
    {
        id: 'execution',
        titre: 'Exécution des prestations',
        contenu: (
            <>
                <p>
                    DAG réalise les prestations avec soin, dans les délais
                    prévus au contrat. Ces délais supposent que le client
                    fournisse à temps les informations, contenus et accès
                    nécessaires ; tout retard de sa part les décale d'autant.
                </p>
                <p>
                    L'avancement est suivi depuis l'espace client. Le client
                    dispose du délai prévu au contrat pour formuler ses demandes
                    de correction sur les livrables ; passé ce délai, ils sont
                    réputés acceptés.
                </p>
            </>
        ),
    },
    {
        id: 'abonnements',
        titre: 'Abonnements',
        contenu: (
            <p>
                Une formule d'abonnement donne droit, pour chaque période, aux
                services qu'elle inclut. Elle se poursuit d'une période à
                l'autre tant qu'elle n'a pas été résiliée. Le client peut y
                mettre fin en le signalant à DAG avant l'échéance suivante ; la
                période en cours reste due.
            </p>
        ),
    },
    {
        id: 'formations',
        titre: 'Formations',
        contenu: (
            <p>
                L'inscription à une session vaut engagement à en régler les
                frais. Une attestation est délivrée à l'apprenant à l'issue de
                la formation, selon les conditions de présence prévues. Si DAG
                reporte ou annule une session, l'apprenant peut choisir une
                autre session ou obtenir le remboursement des frais versés.
            </p>
        ),
    },
    {
        id: 'annulation',
        titre: 'Annulation et remboursement',
        contenu: (
            <p>
                Les conditions d'annulation d'une prestation sont celles du
                contrat. À défaut, les travaux déjà réalisés et les frais
                engagés restent dus. Toute demande de remboursement est à
                adresser à{' '}
                <a href="mailto:contact@agencydag.com">contact@agencydag.com</a>{' '}
                et reçoit une réponse sous quinze jours ouvrés.
            </p>
        ),
    },
    {
        id: 'propriete',
        titre: 'Propriété des livrables',
        contenu: (
            <p>
                Les droits sur les livrables sont cédés au client dans les
                conditions prévues au contrat, après paiement intégral du prix.
                Sauf refus écrit du client, DAG peut citer la réalisation dans
                ses références. Les supports de formation restent la propriété
                de DAG.
            </p>
        ),
    },
    {
        id: 'responsabilite',
        titre: 'Responsabilité',
        contenu: (
            <p>
                DAG est tenue d'une obligation de moyens. Sa responsabilité est
                limitée aux dommages directs et, en tout état de cause, au
                montant payé pour la prestation concernée. Elle ne peut être
                engagée en cas de force majeure ou de faute du client.
            </p>
        ),
    },
    {
        id: 'donnees',
        titre: 'Données personnelles',
        contenu: (
            <p>
                Les données nécessaires au traitement des commandes sont
                traitées conformément à la{' '}
                <Link href={politiqueConfidentialite()}>
                    politique de confidentialité
                </Link>
                . L'utilisation de la plateforme est régie par les{' '}
                <Link href={conditionsUtilisation()}>
                    conditions générales d'utilisation
                </Link>
                .
            </p>
        ),
    },
    {
        id: 'litiges',
        titre: 'Droit applicable et litiges',
        contenu: (
            <p>
                Les présentes CGV sont soumises au droit camerounais. Les
                parties s'efforcent de régler à l'amiable tout différend ; à
                défaut, il est porté devant les juridictions compétentes de
                Douala.
            </p>
        ),
    },
];

export default function ConditionsVente() {
    return (
        <PageJuridique
            titre="Conditions générales de vente"
            badge="Informations légales"
            introduction="Les conditions qui s'appliquent aux prestations, abonnements et formations vendus par DAG."
            miseAJour="6 novembre 2026"
            sections={SECTIONS}
        />
    );
}
