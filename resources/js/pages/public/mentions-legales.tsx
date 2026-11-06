import { Link } from '@inertiajs/react';
import PageJuridique from '@/components/public/page-juridique';
import type { SectionJuridique } from '@/components/public/page-juridique';
import {
    conditionsUtilisation,
    conditionsVente,
    politiqueConfidentialite,
} from '@/routes';

const SECTIONS: SectionJuridique[] = [
    {
        id: 'editeur',
        titre: 'Éditeur du site',
        contenu: (
            <ul>
                <li>
                    <strong>Dénomination</strong> : DAG — Digital Agency Group
                </li>
                <li>
                    <strong>Activité</strong> : agence digitale (création de
                    contenu, community management, publicité, formation,
                    développement web, cybersécurité)
                </li>
                <li>
                    <strong>Siège</strong> : Akwa, Douala, Cameroun
                </li>
                <li>
                    <strong>E-mail</strong> :{' '}
                    <a href="mailto:contact@agencydag.com">
                        contact@agencydag.com
                    </a>
                </li>
                <li>
                    <strong>Téléphone / WhatsApp</strong> : +237 6 98 57 28 93
                </li>
            </ul>
        ),
    },
    {
        id: 'publication',
        titre: 'Direction de la publication',
        contenu: (
            <p>
                La directrice de la publication est Afsa Maira, directrice de
                DAG — Digital Agency Group.
            </p>
        ),
    },
    {
        id: 'propriete',
        titre: 'Propriété intellectuelle',
        contenu: (
            <p>
                L'ensemble des éléments du site (marque DAG, logo, textes,
                visuels, photographies, mise en page et code) est protégé par le
                droit de la propriété intellectuelle. Toute reproduction,
                représentation ou adaptation, totale ou partielle, sans
                l'autorisation écrite de DAG est interdite. Les logos des
                partenaires restent la propriété de leurs titulaires.
            </p>
        ),
    },
    {
        id: 'donnees',
        titre: 'Données personnelles et cookies',
        contenu: (
            <p>
                Le traitement des données collectées sur le site et
                l'utilisation des cookies sont décrits dans la{' '}
                <Link href={politiqueConfidentialite()}>
                    politique de confidentialité
                </Link>
                .
            </p>
        ),
    },
    {
        id: 'responsabilite',
        titre: 'Responsabilité',
        contenu: (
            <p>
                DAG s'efforce de fournir des informations exactes et à jour,
                sans pouvoir garantir l'absence d'erreur ou d'omission. Les
                liens vers des sites tiers sont fournis à titre indicatif : DAG
                n'est pas responsable de leur contenu.
            </p>
        ),
    },
    {
        id: 'conditions',
        titre: 'Conditions applicables',
        contenu: (
            <p>
                L'utilisation du site est régie par les{' '}
                <Link href={conditionsUtilisation()}>
                    conditions générales d'utilisation
                </Link>{' '}
                ; les prestations commandées, par les{' '}
                <Link href={conditionsVente()}>
                    conditions générales de vente
                </Link>
                . Le site et ses mentions sont soumis au droit camerounais.
            </p>
        ),
    },
];

export default function MentionsLegales() {
    return (
        <PageJuridique
            titre="Mentions légales"
            badge="Informations légales"
            introduction="Qui édite le site DAG, comment nous joindre et les règles qui protègent son contenu."
            miseAJour="6 novembre 2026"
            sections={SECTIONS}
        />
    );
}
