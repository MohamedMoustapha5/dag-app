import { Link } from '@inertiajs/react';
import PageJuridique from '@/components/public/page-juridique';
import type { SectionJuridique } from '@/components/public/page-juridique';
import { politiqueConfidentialite } from '@/routes';

const SECTIONS: SectionJuridique[] = [
    {
        id: 'objet',
        titre: 'Objet et acceptation',
        contenu: (
            <>
                <p>
                    Les présentes conditions générales d'utilisation (« CGU »)
                    encadrent l'accès et l'utilisation de la plateforme en ligne
                    de DAG — Digital Agency Group (« DAG »), agence digitale
                    établie à Akwa, Douala, Cameroun : site public, espace
                    client et espace apprenant.
                </p>
                <p>
                    En créant un compte, vous déclarez avoir lu et accepté sans
                    réserve les présentes CGU ainsi que la{' '}
                    <Link href={politiqueConfidentialite()}>
                        politique de confidentialité
                    </Link>
                    . Si vous n'acceptez pas ces conditions, vous ne devez pas
                    utiliser la plateforme.
                </p>
            </>
        ),
    },
    {
        id: 'services',
        titre: 'Services proposés',
        contenu: (
            <>
                <p>La plateforme permet notamment :</p>
                <ul>
                    <li>
                        de consulter le catalogue des services, la grille
                        tarifaire, les formules d'abonnement et les formations
                        de DAG ;
                    </li>
                    <li>
                        de demander un devis, un audit gratuit ou de souscrire
                        une formule, puis de suivre ses projets, contrats,
                        factures et paiements depuis l'espace client ;
                    </li>
                    <li>
                        de s'inscrire à une session de formation, d'en régler
                        les frais et de télécharger son attestation depuis
                        l'espace apprenant ;
                    </li>
                    <li>
                        d'échanger avec l'équipe DAG via le support intégré.
                    </li>
                </ul>
                <p>
                    Les prestations commandées sont régies par le devis accepté
                    et le contrat signé entre DAG et le client, qui prévalent
                    sur les présentes CGU en cas de contradiction.
                </p>
            </>
        ),
    },
    {
        id: 'compte',
        titre: 'Création et sécurité du compte',
        contenu: (
            <>
                <p>
                    La création d'un compte est réservée aux personnes majeures
                    ou dûment représentées, et aux entreprises agissant par
                    l'intermédiaire d'une personne habilitée. Vous vous engagez
                    à fournir des informations exactes et à les tenir à jour.
                </p>
                <p>
                    Vos identifiants sont strictement personnels. Vous êtes
                    responsable de leur confidentialité et de toute activité
                    réalisée depuis votre compte. En cas de doute sur une
                    utilisation frauduleuse, modifiez votre mot de passe et
                    prévenez DAG sans délai.
                </p>
            </>
        ),
    },
    {
        id: 'commandes',
        titre: 'Devis, contrats et paiements',
        contenu: (
            <>
                <p>
                    Toute demande de prestation donne lieu à un devis. Une fois
                    accepté, il est formalisé par un contrat que les deux
                    parties signent. Les prix sont exprimés en francs CFA (FCFA)
                    ; les frais d'inscription d'une formation sont ceux affichés
                    pour la session choisie.
                </p>
                <p>
                    Les paiements sont effectués par les moyens proposés sur la
                    plateforme et confirmés par DAG. Chaque paiement validé
                    donne lieu à une facture disponible dans votre espace. Un
                    retard de paiement peut entraîner la suspension de la
                    prestation, après relance.
                </p>
            </>
        ),
    },
    {
        id: 'obligations',
        titre: "Obligations de l'utilisateur",
        contenu: (
            <>
                <p>En utilisant la plateforme, vous vous interdisez :</p>
                <ul>
                    <li>
                        de publier ou transmettre des contenus illicites,
                        diffamatoires, injurieux ou portant atteinte aux droits
                        de tiers ;
                    </li>
                    <li>
                        de tenter d'accéder sans autorisation à un compte, à un
                        espace ou à des données qui ne vous appartiennent pas ;
                    </li>
                    <li>
                        de perturber le fonctionnement de la plateforme
                        (logiciels malveillants, envois automatisés, tentatives
                        d'intrusion) ;
                    </li>
                    <li>
                        d'usurper l'identité d'une personne ou d'une entreprise.
                    </li>
                </ul>
                <p>
                    Tout manquement peut entraîner la suspension ou la fermeture
                    du compte, sans préjudice des poursuites prévues par la loi.
                </p>
            </>
        ),
    },
    {
        id: 'propriete',
        titre: 'Propriété intellectuelle',
        contenu: (
            <p>
                La marque DAG, son logo, les textes, visuels et le code de la
                plateforme sont la propriété de DAG ou de ses partenaires et ne
                peuvent être reproduits sans autorisation écrite. Les livrables
                réalisés pour un client lui sont cédés dans les conditions
                prévues par son contrat. Les supports de formation restent la
                propriété de DAG et sont réservés à l'usage personnel de
                l'apprenant.
            </p>
        ),
    },
    {
        id: 'responsabilite',
        titre: 'Disponibilité et responsabilité',
        contenu: (
            <>
                <p>
                    DAG met tout en œuvre pour assurer l'accès à la plateforme
                    24 h/24, sans pouvoir le garantir : des interruptions
                    peuvent survenir pour maintenance, mise à jour ou cause
                    extérieure.
                </p>
                <p>
                    DAG ne saurait être tenue responsable des dommages indirects
                    liés à l'utilisation de la plateforme, ni d'une
                    indisponibilité due à un cas de force majeure, au réseau
                    internet ou aux services d'un tiers (opérateur de paiement,
                    messagerie).
                </p>
            </>
        ),
    },
    {
        id: 'donnees',
        titre: 'Données personnelles',
        contenu: (
            <p>
                Les données collectées sur la plateforme sont traitées
                conformément à la{' '}
                <Link href={politiqueConfidentialite()}>
                    politique de confidentialité
                </Link>{' '}
                de DAG, qui précise vos droits et la manière de les exercer.
            </p>
        ),
    },
    {
        id: 'fermeture',
        titre: 'Fermeture du compte',
        contenu: (
            <p>
                Vous pouvez fermer votre compte à tout moment depuis les
                paramètres de votre espace. Les contrats, factures et
                attestations, que DAG est légalement tenue de conserver, sont
                alors rattachés à une identité anonymisée ; le reste de vos
                données personnelles est supprimé.
            </p>
        ),
    },
    {
        id: 'modification',
        titre: 'Modification des CGU',
        contenu: (
            <p>
                DAG peut faire évoluer les présentes CGU. La version en vigueur
                est celle publiée sur cette page, avec sa date de mise à jour.
                En cas de changement important, les utilisateurs inscrits en
                sont informés par e-mail ou dans leur espace.
            </p>
        ),
    },
    {
        id: 'droit',
        titre: 'Droit applicable et litiges',
        contenu: (
            <p>
                Les présentes CGU sont régies par le droit camerounais. En cas
                de différend, les parties recherchent d'abord une solution
                amiable ; à défaut, le litige est porté devant les juridictions
                compétentes de Douala.
            </p>
        ),
    },
    {
        id: 'contact',
        titre: 'Nous contacter',
        contenu: (
            <p>
                Pour toute question relative aux présentes CGU : DAG — Digital
                Agency Group, Akwa, Douala, Cameroun —{' '}
                <a href="mailto:contact@agencydag.com">contact@agencydag.com</a>{' '}
                — +237 6 98 57 28 93.
            </p>
        ),
    },
];

export default function ConditionsUtilisation() {
    return (
        <PageJuridique
            titre="Conditions générales d'utilisation"
            badge="Informations légales"
            introduction="Les règles qui encadrent l'utilisation de la plateforme DAG, de ses espaces client et apprenant."
            miseAJour="6 novembre 2026"
            sections={SECTIONS}
        />
    );
}
