import { Link } from '@inertiajs/react';
import PageJuridique from '@/components/public/page-juridique';
import type { SectionJuridique } from '@/components/public/page-juridique';
import { conditionsUtilisation } from '@/routes';

const SECTIONS: SectionJuridique[] = [
    {
        id: 'responsable',
        titre: 'Responsable du traitement',
        contenu: (
            <p>
                Le responsable du traitement de vos données est DAG — Digital
                Agency Group, Akwa, Douala, Cameroun (
                <a href="mailto:contact@agencydag.com">contact@agencydag.com</a>
                ). DAG traite vos données dans le respect de la réglementation
                camerounaise applicable à la protection des données à caractère
                personnel et à la cybersécurité.
            </p>
        ),
    },
    {
        id: 'donnees',
        titre: 'Données collectées',
        contenu: (
            <>
                <p>Selon votre utilisation de la plateforme, DAG collecte :</p>
                <ul>
                    <li>
                        <strong>Identification et contact</strong> : nom,
                        adresse e-mail, téléphone, entreprise et fonction pour
                        un compte professionnel ;
                    </li>
                    <li>
                        <strong>Compte</strong> : identifiants de connexion (le
                        mot de passe est stocké chiffré, jamais en clair) ;
                    </li>
                    <li>
                        <strong>Relation commerciale</strong> : demandes, devis,
                        contrats, factures, paiements et abonnements ;
                    </li>
                    <li>
                        <strong>Formation</strong> : inscriptions, présence et
                        attestations ;
                    </li>
                    <li>
                        <strong>Échanges</strong> : messages envoyés via le
                        formulaire de contact ou le support, candidatures et CV
                        transmis ;
                    </li>
                    <li>
                        <strong>Données techniques</strong> : journaux de
                        connexion et cookies nécessaires au fonctionnement du
                        site.
                    </li>
                </ul>
            </>
        ),
    },
    {
        id: 'finalites',
        titre: 'Finalités et bases légales',
        contenu: (
            <>
                <p>Vos données sont utilisées pour :</p>
                <ul>
                    <li>
                        créer et gérer votre compte et vous donner accès à votre
                        espace (exécution du contrat) ;
                    </li>
                    <li>
                        établir vos devis, contrats et factures, suivre vos
                        paiements et vos formations (exécution du contrat et
                        obligations légales comptables) ;
                    </li>
                    <li>
                        répondre à vos demandes de contact, d'audit et de
                        support (intérêt légitime) ;
                    </li>
                    <li>
                        vous envoyer les notifications liées à votre compte par
                        e-mail, SMS ou WhatsApp (exécution du contrat) ;
                    </li>
                    <li>
                        sécuriser la plateforme et prévenir la fraude (intérêt
                        légitime).
                    </li>
                </ul>
                <p>DAG ne vend ni ne loue jamais vos données.</p>
            </>
        ),
    },
    {
        id: 'destinataires',
        titre: 'Destinataires',
        contenu: (
            <p>
                Vos données sont accessibles aux seuls membres de l'équipe DAG
                habilités, chacun selon son rôle. Elles peuvent être transmises
                aux prestataires techniques strictement nécessaires
                (hébergement, envoi d'e-mails et de messages, opérateurs de
                paiement), tenus à la confidentialité, ainsi qu'aux autorités
                lorsque la loi l'exige.
            </p>
        ),
    },
    {
        id: 'conservation',
        titre: 'Durée de conservation',
        contenu: (
            <>
                <p>
                    Les données de votre compte sont conservées tant que
                    celui-ci est ouvert. À sa fermeture, vos données
                    personnelles sont supprimées.
                </p>
                <p>
                    Les contrats, factures et attestations sont conservés
                    pendant la durée imposée par les obligations légales et
                    comptables ; ils sont alors rattachés à une identité
                    anonymisée. Les messages de support sont supprimés avec le
                    compte.
                </p>
            </>
        ),
    },
    {
        id: 'securite',
        titre: 'Sécurité',
        contenu: (
            <p>
                DAG met en œuvre des mesures techniques et organisationnelles
                adaptées : connexion chiffrée (HTTPS), mots de passe chiffrés,
                droits d'accès limités par rôle, stockage privé des contrats,
                factures et attestations, accessibles uniquement à leur
                titulaire et à l'équipe habilitée.
            </p>
        ),
    },
    {
        id: 'cookies',
        titre: 'Cookies',
        contenu: (
            <p>
                La plateforme utilise des cookies nécessaires à son
                fonctionnement (session, sécurité des formulaires, préférences
                d'affichage). Votre choix concernant les cookies est enregistré
                dans votre navigateur et peut être modifié à tout moment en
                effaçant les données du site.
            </p>
        ),
    },
    {
        id: 'droits',
        titre: 'Vos droits',
        contenu: (
            <>
                <p>
                    Vous disposez d'un droit d'accès, de rectification,
                    d'opposition et de suppression de vos données. Vous pouvez :
                </p>
                <ul>
                    <li>
                        consulter et modifier vos informations depuis les
                        paramètres de votre espace ;
                    </li>
                    <li>fermer votre compte depuis ces mêmes paramètres ;</li>
                    <li>
                        nous écrire à{' '}
                        <a href="mailto:contact@agencydag.com">
                            contact@agencydag.com
                        </a>{' '}
                        pour toute autre demande — une réponse vous est apportée
                        dans un délai d'un mois.
                    </li>
                </ul>
                <p>
                    Si vous estimez que vos droits ne sont pas respectés, vous
                    pouvez saisir l'autorité camerounaise compétente en matière
                    de protection des données personnelles.
                </p>
            </>
        ),
    },
    {
        id: 'modification',
        titre: 'Modification de la politique',
        contenu: (
            <p>
                DAG peut mettre à jour la présente politique. La version en
                vigueur est celle publiée sur cette page. Elle complète les{' '}
                <Link href={conditionsUtilisation()}>
                    conditions générales d'utilisation
                </Link>
                .
            </p>
        ),
    },
];

export default function PolitiqueConfidentialite() {
    return (
        <PageJuridique
            titre="Politique de confidentialité"
            badge="Protection des données"
            introduction="Quelles données DAG collecte, pourquoi, combien de temps elles sont conservées et comment exercer vos droits."
            miseAJour="6 novembre 2026"
            sections={SECTIONS}
        />
    );
}
