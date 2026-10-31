import { Head, Link } from '@inertiajs/react';
import PublicLayout from '@/layouts/public-layout';
import { catalogue, contact } from '@/routes';

type AProposProps = {
    stats: {
        services: number;
        partenariats: number;
        contratsEnLigne: number;
        membresEquipe: number;
    };
    equipe: Array<{ nom: string; role: string }>;
};

const VALEURS = [
    'Moderne & digitale',
    'Professionnelle & structurée',
    'Innovante sans être agressive',
    'Accessible & dynamique',
    'Orientée résultats',
];

const PARTENAIRES = [
    {
        nom: 'Nova-Tech',
        logo: '/images/brand/partners/nova-tech.png',
    },
    {
        nom: 'Nordex Consulting Group',
        logo: '/images/brand/partners/nordex-consulting-group.jpg',
    },
];

const EQUIPE_TAGLINES: Record<string, string> = {
    Promotrice: 'Pilote la vision et la relation client.',
    Juriste: 'Sécurise chaque contrat, à la lettre.',
    'Communication & marketing digital':
        'Orchestre vos campagnes et votre présence en ligne.',
    'Contrôle de gestion':
        'Garde un œil sur chaque facture et chaque échéance.',
    'Création de contenu': 'Donne vie à vos idées en visuels et en mots.',
};

const EQUIPE_PHOTOS: Record<string, string> = {
    Promotrice: '/images/photos/woman-desk-seo.jpg',
    Juriste: '/images/photos/woman-thinking-social.jpg',
    'Communication & marketing digital': '/images/photos/man-phone-social.jpg',
    'Contrôle de gestion': '/images/photos/man-suit-growth.jpg',
    'Création de contenu': '/images/photos/man-laptop-smile.jpg',
};

const DEVELOPPEUR = {
    nom: 'Yannick B.',
    role: 'Développeur',
    tagline: 'Conçoit et fait évoluer la plateforme DAG, de bout en bout.',
    photo: '/images/photos/office-admin-laptop.jpg',
};

export default function APropos({ stats, equipe }: AProposProps) {
    const equipeAffichee = [
        ...equipe.map((membre) => ({
            nom: membre.nom,
            role: membre.role,
            tagline:
                EQUIPE_TAGLINES[membre.role] ??
                'Accompagne vos projets au quotidien.',
            photo:
                EQUIPE_PHOTOS[membre.role] ??
                '/images/photos/man-laptop-smile.jpg',
        })),
        DEVELOPPEUR,
    ];

    return (
        <PublicLayout>
            <Head title="À propos — DAG" />

            <section className="contact-hero-wrap reveal container">
                <div
                    className="contact-hero"
                    style={{
                        backgroundImage:
                            "url('/images/photos/office-glass-dubai.jpg')",
                    }}
                >
                    <div className="contact-hero-overlay" />
                    <div className="contact-hero-content">
                        <span className="badge">À propos de DAG</span>
                        <h1>
                            L'agence qui simplifie votre transformation digitale
                        </h1>
                        <p>
                            DAG — Digital Agency Group réunit, sous un seul toit
                            à Douala, la création de contenu, le community
                            management, la publicité, la formation, le
                            développement web et la cybersécurité. Un seul
                            interlocuteur, du premier message à la livraison.
                        </p>
                    </div>
                </div>
            </section>

            <section className="section-gap reveal container">
                <div
                    style={{
                        display: 'flex',
                        flexWrap: 'wrap',
                        gap: 40,
                        alignItems: 'center',
                    }}
                >
                    <div style={{ flex: '1 1 340px' }}>
                        <span className="badge">Notre mission</span>
                        <h2
                            style={{
                                marginTop: 14,
                                fontSize: 26,
                                color: 'var(--dag-navy)',
                            }}
                        >
                            Rendre le digital accessible à toutes les
                            entreprises camerounaises
                        </h2>
                        <p
                            className="muted"
                            style={{ marginTop: 12, fontSize: 14 }}
                        >
                            Trop d'entreprises jonglent entre plusieurs
                            prestataires pour leur présence en ligne, leur
                            sécurité informatique et le recrutement de talents
                            formés. DAG a été fondée pour résoudre ce problème :
                            une équipe, une plateforme, un suivi transparent —
                            des particuliers aux grandes entreprises.
                        </p>
                    </div>
                    <div
                        style={{
                            flex: '1.2 1 380px',
                            display: 'flex',
                            flexWrap: 'wrap',
                            gap: 10,
                        }}
                    >
                        {VALEURS.map((valeur) => (
                            <span key={valeur} className="chip chip-blue">
                                {valeur}
                            </span>
                        ))}
                    </div>
                </div>
            </section>

            <section className="section-gap reveal container">
                <div className="grid-4">
                    <div
                        className="card"
                        style={{ padding: 24, textAlign: 'center' }}
                    >
                        <strong
                            style={{
                                display: 'block',
                                fontFamily: "'Sora',sans-serif",
                                fontSize: 32,
                                fontWeight: 800,
                                color: 'var(--dag-blue)',
                            }}
                        >
                            {stats.services}
                        </strong>
                        <span
                            className="muted"
                            style={{ fontSize: 13, marginTop: 6 }}
                        >
                            Services au catalogue
                        </span>
                    </div>
                    <div
                        className="card"
                        style={{ padding: 24, textAlign: 'center' }}
                    >
                        <strong
                            style={{
                                display: 'block',
                                fontFamily: "'Sora',sans-serif",
                                fontSize: 32,
                                fontWeight: 800,
                                color: 'var(--dag-blue)',
                            }}
                        >
                            {stats.partenariats}
                        </strong>
                        <span
                            className="muted"
                            style={{ fontSize: 13, marginTop: 6 }}
                        >
                            Partenariats techniques
                        </span>
                    </div>
                    <div
                        className="card"
                        style={{ padding: 24, textAlign: 'center' }}
                    >
                        <strong
                            style={{
                                display: 'block',
                                fontFamily: "'Sora',sans-serif",
                                fontSize: 32,
                                fontWeight: 800,
                                color: 'var(--dag-blue)',
                            }}
                        >
                            {stats.contratsEnLigne}%
                        </strong>
                        <span
                            className="muted"
                            style={{ fontSize: 13, marginTop: 6 }}
                        >
                            Contrats signés en ligne
                        </span>
                    </div>
                    <div
                        className="card"
                        style={{ padding: 24, textAlign: 'center' }}
                    >
                        <strong
                            style={{
                                display: 'block',
                                fontFamily: "'Sora',sans-serif",
                                fontSize: 32,
                                fontWeight: 800,
                                color: 'var(--dag-blue)',
                            }}
                        >
                            {stats.membresEquipe}
                        </strong>
                        <span
                            className="muted"
                            style={{ fontSize: 13, marginTop: 6 }}
                        >
                            Membres de l'équipe
                        </span>
                    </div>
                </div>
            </section>

            <section
                className="section-gap reveal container"
                style={{
                    display: 'flex',
                    flexDirection: 'column',
                    alignItems: 'center',
                    textAlign: 'center',
                }}
            >
                <span className="badge badge-partner">Écosystème DAG</span>
                <h2
                    style={{
                        marginTop: 14,
                        fontSize: 26,
                        color: 'var(--dag-navy)',
                    }}
                >
                    Nos partenariats
                </h2>
                <p className="muted" style={{ marginTop: 10, maxWidth: 560 }}>
                    Pour certains besoins techniques spécialisés, DAG s'appuie
                    sur un réseau de partenaires de confiance — tout en restant
                    votre unique interlocuteur, de la demande à la facturation.
                </p>

                <div
                    style={{
                        marginTop: 32,
                        display: 'flex',
                        justifyContent: 'center',
                        gap: 24,
                        flexWrap: 'wrap',
                    }}
                >
                    {PARTENAIRES.map((partenaire) => (
                        <div
                            key={partenaire.nom}
                            className="card"
                            style={{
                                padding: 20,
                                width: 180,
                                height: 100,
                                display: 'flex',
                                alignItems: 'center',
                                justifyContent: 'center',
                            }}
                        >
                            <img
                                src={partenaire.logo}
                                alt={partenaire.nom}
                                style={{
                                    maxWidth: '100%',
                                    maxHeight: '100%',
                                    objectFit: 'contain',
                                }}
                            />
                        </div>
                    ))}
                </div>

                <p className="muted" style={{ marginTop: 20, fontSize: 13 }}>
                    Intégrées à notre catalogue sous la catégorie « Technique &
                    digital ».{' '}
                    <Link href={catalogue()}>Voir le catalogue complet →</Link>
                </p>
            </section>

            <section
                id="equipe"
                className="section-gap reveal container"
                style={{
                    display: 'flex',
                    flexDirection: 'column',
                    alignItems: 'center',
                    textAlign: 'center',
                }}
            >
                <h2 style={{ fontSize: 26, color: 'var(--dag-navy)' }}>
                    Les visages derrière vos projets
                </h2>
                <p className="muted" style={{ marginTop: 10, maxWidth: 520 }}>
                    Une équipe basée à Douala, qui suit chaque demande du
                    premier contact jusqu'à la livraison.
                </p>

                <div
                    className="grid-3"
                    style={{ marginTop: 32, width: '100%' }}
                >
                    {equipeAffichee.map((membre) => (
                        <div
                            key={membre.nom}
                            className="card"
                            style={{ padding: 24, textAlign: 'center' }}
                        >
                            <div
                                style={{
                                    width: 72,
                                    height: 72,
                                    borderRadius: 999,
                                    overflow: 'hidden',
                                    margin: '0 auto',
                                    border: '1px solid var(--dag-border)',
                                }}
                            >
                                <img
                                    src={membre.photo}
                                    alt={membre.nom}
                                    style={{
                                        width: '100%',
                                        height: '100%',
                                        objectFit: 'cover',
                                        display: 'block',
                                    }}
                                />
                            </div>
                            <h3
                                style={{
                                    marginTop: 14,
                                    fontSize: 16,
                                    color: 'var(--dag-navy)',
                                }}
                            >
                                {membre.nom}
                            </h3>
                            <span
                                className="chip chip-blue"
                                style={{ marginTop: 8 }}
                            >
                                {membre.role}
                            </span>
                            <p
                                className="muted"
                                style={{ marginTop: 10, fontSize: 13 }}
                            >
                                {membre.tagline}
                            </p>
                        </div>
                    ))}
                </div>
            </section>

            <section
                className="section-gap reveal container"
                style={{ marginBottom: 88 }}
            >
                <div
                    className="card"
                    style={{
                        background: 'var(--dag-navy)',
                        border: 'none',
                        padding: 40,
                        display: 'flex',
                        justifyContent: 'space-between',
                        alignItems: 'center',
                        gap: 32,
                        flexWrap: 'wrap',
                    }}
                >
                    <div style={{ maxWidth: 440 }}>
                        <h2 style={{ fontSize: 22, color: '#fff' }}>
                            Envie de travailler avec nous ?
                        </h2>
                        <p
                            style={{
                                marginTop: 10,
                                fontSize: 13.5,
                                color: 'rgba(255,255,255,.8)',
                            }}
                        >
                            Parlons de votre projet, ou découvrez nos services
                            et nos formules d'abonnement.
                        </p>
                    </div>
                    <div style={{ display: 'flex', gap: 12, flexWrap: 'wrap' }}>
                        <Link href={contact()} className="btn-primary">
                            Nous contacter
                        </Link>
                        <Link
                            href={catalogue()}
                            className="btn-ghost btn-ghost-dark"
                        >
                            Voir nos services
                        </Link>
                    </div>
                </div>
            </section>
        </PublicLayout>
    );
}
