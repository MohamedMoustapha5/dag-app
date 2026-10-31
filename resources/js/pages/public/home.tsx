import { Head, Link } from '@inertiajs/react';
import {
    Check,
    CheckCircle2,
    Clock,
    CreditCard,
    FileText,
    ShieldCheck,
    Star,
    UserCheck,
    Users,
} from 'lucide-react';
import { useEffect, useState } from 'react';
import AuditRequestModal from '@/components/public/audit-request-modal';
import PublicLayout from '@/layouts/public-layout';
import { abonnement, catalogue, contact, formations } from '@/routes';

type HomeProps = {
    stats: {
        services: number;
    };
    equipe: Array<{ nom: string; role: string }>;
};

const PROCESS_STEPS = [
    {
        numero: '01',
        titre: 'Choisissez un service',
        description:
            'Parcourez le catalogue ou décrivez votre besoin en quelques mots.',
    },
    {
        numero: '02',
        titre: 'Recevez votre devis',
        description:
            'Un conseiller DAG revient vers vous rapidement avec une offre claire.',
    },
    {
        numero: '03',
        titre: 'Payez et signez en ligne',
        description:
            'Orange Money, MTN MoMo ou carte — contrat généré et signé numériquement.',
    },
    {
        numero: '04',
        titre: 'Suivez la réalisation',
        description:
            "Votre espace client centralise l'avancement, les documents et les échanges.",
    },
];

const FORMATION_STEPS = [
    {
        icon: FileText,
        titre: '1. Choisissez votre formation',
        description:
            'Présentiel à Douala ou en ligne, dates et places disponibles dans le catalogue.',
    },
    {
        icon: UserCheck,
        titre: '2. Inscrivez-vous et suivez les séances',
        description:
            'Votre espace apprenant suit votre présence et votre progression séance après séance.',
    },
    {
        icon: CheckCircle2,
        titre: '3. Attestation & placement',
        description:
            'Attestation délivrée en fin de session, avec accompagnement vers un stage ou un emploi.',
    },
];

const TRUST_ITEMS = [
    { icon: CreditCard, texte: 'Orange Money · MTN MoMo · Carte bancaire' },
    { icon: ShieldCheck, texte: 'Contrats signés 100% numérique' },
    { icon: Users, texte: 'Formation + placement en entreprise' },
    { icon: CheckCircle2, texte: 'Un seul interlocuteur : DAG' },
];

const PAIEMENT_CHECKLIST = [
    'Montant total affiché en FCFA, sans frais cachés',
    'Devis verrouillé 24h, le temps de finaliser',
    'Confirmation immédiate et contrat numérique',
];

const PAYMENT_METHODS = [
    {
        label: 'Orange Money',
        badge: 'OM',
        badgeBg: '#FF6600',
        badgeColor: '#fff',
        selected: true,
    },
    {
        label: 'MTN Mobile Money',
        badge: 'MTN',
        badgeBg: '#FFCB05',
        badgeColor: '#1B2338',
        selected: false,
    },
    {
        label: 'Carte bancaire',
        badge: null,
        badgeBg: 'var(--dag-navy)',
        badgeColor: '#fff',
        selected: false,
    },
];

const TEAM_PHOTOS = [
    '/images/photos/afsa-maira-directrice.jpg',
    '/images/photos/woman-desk-seo.jpg',
    '/images/photos/senghor-responsable-marketing.jpg',
    '/images/photos/layana-d-secretaire.jpg',
    '/images/photos/hussein-createur-contenu.jpg',
    '/images/photos/mustapha-developpeur.jpg',
];

const TEAM_TAGLINES: Record<string, string> = {
    Directrice: 'Pilote chaque dossier du premier message à la livraison.',
    Juriste: 'Sécurise chaque contrat, à la lettre.',
    'Responsable Marketing': 'Fait rayonner vos marques sur les réseaux.',
    Secrétaire: 'Veille sur chaque facture et chaque paiement.',
    'Création de contenu': 'Donne vie à vos idées en visuels et vidéos.',
    Développeur: 'Conçoit et fait évoluer la plateforme DAG, de bout en bout.',
};

const DEVELOPPEUR = { nom: 'Mustapha', role: 'Développeur' };

const TESTIMONIALS = [
    {
        nom: 'Aïcha N.',
        meta: 'Cliente — Douala',
        tag: 'Client',
        avis: 'DAG a transformé notre présence sur les réseaux sociaux. Des résultats visibles dès le premier mois, et une équipe toujours réactive.',
        note: 5,
    },
    {
        nom: 'Karim B.',
        meta: 'Fondateur — Atlas Corp',
        tag: 'Entreprise',
        avis: "Un vrai partenaire stratégique : de l'audit initial jusqu'au suivi mensuel des performances.",
        note: 4,
    },
    {
        nom: 'Stéphanie A.',
        meta: 'Apprenante — Community management',
        tag: 'Apprenant',
        avis: "Les formations proposées sont concrètes et directement applicables. L'accompagnement vers le stage a fait toute la différence.",
        note: 3,
    },
    {
        nom: 'Nadia T.',
        meta: 'Responsable marketing — Kiro Digital Solutions',
        tag: 'Entreprise',
        avis: 'Grâce à DAG, nous recrutons des profils déjà formés et opérationnels dès le premier jour. Un vrai gain de temps pour notre équipe.',
        note: 5,
    },
    {
        nom: 'Yves M.',
        meta: 'Client — Douala',
        tag: 'Client',
        avis: "La formule d'abonnement m'a permis de garder une présence régulière sur les réseaux sans y passer mes journées. Facturation claire, zéro surprise.",
        note: 2.5,
    },
    {
        nom: 'Brice K.',
        meta: 'Apprenant — Développement web',
        tag: 'Apprenant',
        avis: "La convention de stage signée avec Kiro Digital Solutions m'a ouvert les portes du marché du travail directement après la formation.",
        note: 5,
    },
];

export default function Home({ stats, equipe }: HomeProps) {
    const [teamIndex, setTeamIndex] = useState(0);
    const [testimonialIndex, setTestimonialIndex] = useState(0);
    const [auditModalOpen, setAuditModalOpen] = useState(false);

    const equipeAffichee = [...equipe, DEVELOPPEUR];
    const activeMember = equipeAffichee[teamIndex];
    const activeTestimonial = TESTIMONIALS[testimonialIndex];

    useEffect(() => {
        const timer = window.setInterval(() => {
            setTestimonialIndex((index) => (index + 1) % TESTIMONIALS.length);
        }, 6000);

        return () => window.clearInterval(timer);
    }, [testimonialIndex]);

    useEffect(() => {
        if (equipeAffichee.length === 0) {
            return;
        }

        const timer = window.setInterval(() => {
            setTeamIndex((index) => (index + 1) % equipeAffichee.length);
        }, 5000);

        return () => window.clearInterval(timer);
    }, [teamIndex, equipeAffichee.length]);

    return (
        <PublicLayout>
            <Head title="DAG — Digital Agency Group" />

            <section
                className="hero-full home-hero"
                style={{
                    backgroundImage: "url('/images/photos/home-hero.jpg')",
                }}
            >
                <div className="hero-full-overlay" />
                <div className="hero-full-content home-hero-content container">
                    <span className="badge">Bienvenue chez DAG</span>
                    <h1>
                        L'accélérateur digital de <span>votre activité</span>.
                    </h1>
                    <p>
                        DAG est une agence digitale qui accompagne les
                        particuliers, les entreprises et les apprenants dans
                        leur transformation numérique à travers des services,
                        des formations et du placement.
                    </p>
                    <div className="home-hero-actions">
                        <Link
                            href={catalogue()}
                            prefetch
                            className="btn-primary"
                        >
                            Découvrir nos services →
                        </Link>
                        <Link href={contact()} className="btn-ghost">
                            Nous contacter
                        </Link>
                    </div>
                    <div className="home-hero-stats">
                        <div className="home-hero-stat">
                            <strong>{stats.services}</strong>
                            <span>Services au catalogue</span>
                        </div>
                        <div className="home-hero-stat">
                            <strong>100%</strong>
                            <span>Contrats numériques</span>
                        </div>
                        <div className="home-hero-stat">
                            <strong>3</strong>
                            <span>Moyens de paiement</span>
                        </div>
                    </div>
                </div>
            </section>

            <section
                className="reveal"
                style={{ width: '100%', background: 'var(--dag-navy)' }}
            >
                <div
                    className="grid-4 container"
                    style={{ paddingTop: 22, paddingBottom: 22 }}
                >
                    {TRUST_ITEMS.map((item) => (
                        <div
                            key={item.texte}
                            style={{
                                display: 'flex',
                                alignItems: 'center',
                                gap: 12,
                            }}
                        >
                            <item.icon
                                size={18}
                                color="#fff"
                                style={{ flexShrink: 0 }}
                            />
                            <span
                                style={{
                                    color: 'rgba(255,255,255,.92)',
                                    fontSize: 13.5,
                                    fontWeight: 600,
                                }}
                            >
                                {item.texte}
                            </span>
                        </div>
                    ))}
                </div>
            </section>

            <section className="section-gap reveal container">
                <span className="badge">Le parcours DAG</span>
                <h2
                    style={{
                        marginTop: 14,
                        fontSize: 28,
                        color: 'var(--dag-navy)',
                    }}
                >
                    Comment ça marche
                </h2>
                <p className="muted" style={{ marginTop: 10, maxWidth: 560 }}>
                    Du premier message à la livraison, quatre étapes simples et
                    suivies de bout en bout.
                </p>
                <div
                    className="grid-4 reveal-stagger"
                    style={{ marginTop: 32 }}
                >
                    {PROCESS_STEPS.map((step) => (
                        <div
                            key={step.numero}
                            className="card"
                            style={{ padding: 24 }}
                        >
                            <span
                                style={{
                                    fontFamily: "'Sora',sans-serif",
                                    fontSize: 26,
                                    fontWeight: 800,
                                    color: '#CBD2E0',
                                }}
                            >
                                {step.numero}
                            </span>
                            <div
                                style={{
                                    width: 32,
                                    height: 4,
                                    background: 'var(--dag-orange)',
                                    borderRadius: 2,
                                    marginTop: 10,
                                }}
                            />
                            <h3
                                style={{
                                    marginTop: 14,
                                    fontSize: 15.5,
                                    color: 'var(--dag-navy)',
                                }}
                            >
                                {step.titre}
                            </h3>
                            <p
                                className="muted"
                                style={{ marginTop: 8, fontSize: 13 }}
                            >
                                {step.description}
                            </p>
                        </div>
                    ))}
                </div>
                <div style={{ marginTop: 28 }}>
                    <Link href={catalogue()} prefetch className="btn-primary">
                        Parcourir nos services →
                    </Link>
                </div>
            </section>

            <section
                className="section-gap reveal"
                style={{ width: '100%', background: 'var(--dag-navy)' }}
            >
                <div
                    className="container"
                    style={{
                        paddingTop: 56,
                        paddingBottom: 56,
                        display: 'flex',
                        alignItems: 'center',
                        gap: 48,
                        flexWrap: 'wrap',
                    }}
                >
                    <div style={{ flex: '1 1 380px' }}>
                        <span className="badge badge-dark">
                            Paiement simplifié
                        </span>
                        <h2
                            style={{
                                marginTop: 16,
                                fontSize: 32,
                                fontWeight: 800,
                                color: '#fff',
                                lineHeight: 1.25,
                            }}
                        >
                            Payez comme vous le faites déjà,{' '}
                            <span style={{ color: 'var(--dag-orange)' }}>
                                en FCFA
                            </span>
                            .
                        </h2>
                        <p
                            style={{
                                marginTop: 14,
                                fontSize: 14.5,
                                color: 'rgba(255,255,255,.78)',
                                lineHeight: 1.7,
                                maxWidth: 480,
                            }}
                        >
                            Orange Money, MTN Mobile Money ou carte bancaire —
                            aucun compte à l'étranger nécessaire. Dès le
                            paiement confirmé, votre contrat est généré et
                            envoyé automatiquement.
                        </p>
                        <ul
                            style={{
                                marginTop: 22,
                                display: 'flex',
                                flexDirection: 'column',
                                gap: 12,
                            }}
                        >
                            {PAIEMENT_CHECKLIST.map((item) => (
                                <li
                                    key={item}
                                    style={{
                                        display: 'flex',
                                        alignItems: 'center',
                                        gap: 10,
                                    }}
                                >
                                    <span
                                        style={{
                                            width: 20,
                                            height: 20,
                                            borderRadius: 999,
                                            background: 'var(--dag-orange)',
                                            display: 'flex',
                                            alignItems: 'center',
                                            justifyContent: 'center',
                                            flexShrink: 0,
                                        }}
                                    >
                                        <Check
                                            size={12}
                                            color="var(--dag-navy)"
                                            strokeWidth={3}
                                        />
                                    </span>
                                    <span
                                        style={{
                                            fontSize: 13.5,
                                            color: 'rgba(255,255,255,.92)',
                                        }}
                                    >
                                        {item}
                                    </span>
                                </li>
                            ))}
                        </ul>
                        <div style={{ marginTop: 26 }}>
                            <Link href={abonnement()} className="btn-primary">
                                Voir les formules et tarifs →
                            </Link>
                        </div>
                    </div>

                    <div
                        className="phone-mock"
                        style={{
                            flex: '0 1 300px',
                            width: '100%',
                            maxWidth: 300,
                            margin: '0 auto',
                        }}
                    >
                        <div
                            style={{
                                background: '#15192B',
                                borderRadius: 44,
                                padding: '16px 12px',
                                boxShadow: '0 28px 64px -20px rgba(0,0,0,.55)',
                                position: 'relative',
                            }}
                        >
                            <div
                                aria-hidden="true"
                                style={{
                                    position: 'absolute',
                                    top: 16,
                                    left: '50%',
                                    transform: 'translateX(-50%)',
                                    width: 84,
                                    height: 20,
                                    background: '#15192B',
                                    borderRadius: '0 0 14px 14px',
                                    zIndex: 2,
                                }}
                            />
                            <div
                                style={{
                                    background: '#fff',
                                    borderRadius: 30,
                                    padding: '26px 18px 18px',
                                    position: 'relative',
                                    overflow: 'hidden',
                                }}
                            >
                                <p
                                    style={{
                                        fontFamily: "'Sora',sans-serif",
                                        fontWeight: 800,
                                        fontSize: 17,
                                        color: 'var(--dag-navy)',
                                    }}
                                >
                                    Paiement
                                </p>

                                <div
                                    style={{
                                        marginTop: 16,
                                        background: 'var(--dag-orange-bg)',
                                        borderRadius: 12,
                                        padding: '10px 14px',
                                        display: 'flex',
                                        alignItems: 'center',
                                        gap: 10,
                                    }}
                                >
                                    <Clock
                                        size={16}
                                        color="var(--dag-orange-ink)"
                                        style={{ flexShrink: 0 }}
                                    />
                                    <span
                                        style={{
                                            fontSize: 11.5,
                                            color: 'var(--dag-orange-ink)',
                                            fontWeight: 600,
                                            lineHeight: 1.4,
                                        }}
                                    >
                                        Votre devis reste valable encore{' '}
                                        <strong>23:58:10</strong>
                                    </span>
                                </div>

                                <div style={{ marginTop: 16 }}>
                                    <label
                                        style={{
                                            fontSize: 11.5,
                                            fontWeight: 700,
                                            color: 'var(--dag-text-muted)',
                                        }}
                                    >
                                        Montant du service
                                    </label>
                                    <div
                                        style={{
                                            marginTop: 6,
                                            height: 42,
                                            borderRadius: 9,
                                            border: '1px solid var(--dag-border)',
                                            background: '#F6F7FB',
                                        }}
                                    />
                                </div>

                                <div
                                    style={{
                                        marginTop: 16,
                                        display: 'flex',
                                        flexDirection: 'column',
                                        gap: 10,
                                    }}
                                >
                                    {PAYMENT_METHODS.map((method) => (
                                        <div
                                            key={method.label}
                                            style={{
                                                display: 'flex',
                                                alignItems: 'center',
                                                gap: 12,
                                                padding: '10px 12px',
                                                borderRadius: 9,
                                                border: method.selected
                                                    ? '1.5px solid var(--dag-blue)'
                                                    : '1px solid var(--dag-border)',
                                                background: method.selected
                                                    ? 'var(--dag-blue-light)'
                                                    : '#fff',
                                            }}
                                        >
                                            <span
                                                style={{
                                                    width: 28,
                                                    height: 28,
                                                    borderRadius: 999,
                                                    background: method.badgeBg,
                                                    color: method.badgeColor,
                                                    display: 'flex',
                                                    alignItems: 'center',
                                                    justifyContent: 'center',
                                                    flexShrink: 0,
                                                }}
                                            >
                                                {method.badge ? (
                                                    <span
                                                        style={{
                                                            fontSize: 8.5,
                                                            fontWeight: 800,
                                                        }}
                                                    >
                                                        {method.badge}
                                                    </span>
                                                ) : (
                                                    <CreditCard size={14} />
                                                )}
                                            </span>
                                            <span
                                                style={{
                                                    flex: 1,
                                                    fontSize: 12.5,
                                                    fontWeight: 600,
                                                    color: 'var(--dag-navy)',
                                                }}
                                            >
                                                {method.label}
                                            </span>
                                            <span
                                                style={{
                                                    width: 16,
                                                    height: 16,
                                                    borderRadius: 999,
                                                    border: `2px solid ${method.selected ? 'var(--dag-blue)' : '#CBD2E0'}`,
                                                    display: 'flex',
                                                    alignItems: 'center',
                                                    justifyContent: 'center',
                                                    flexShrink: 0,
                                                }}
                                            >
                                                {method.selected && (
                                                    <span
                                                        style={{
                                                            width: 8,
                                                            height: 8,
                                                            borderRadius: 999,
                                                            background:
                                                                'var(--dag-blue)',
                                                        }}
                                                    />
                                                )}
                                            </span>
                                        </div>
                                    ))}
                                </div>

                                <div
                                    className="btn-primary btn-block"
                                    style={{ marginTop: 18, cursor: 'default' }}
                                >
                                    Payer en FCFA
                                </div>
                                <p
                                    style={{
                                        marginTop: 12,
                                        textAlign: 'center',
                                        fontSize: 11.5,
                                        fontWeight: 600,
                                        color: 'var(--dag-blue)',
                                    }}
                                >
                                    J'ai payé, vérifier mon paiement
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section className="section-gap reveal container">
                <div
                    className="card"
                    style={{
                        background: 'var(--dag-blue-light)',
                        border: 'none',
                        padding: 40,
                    }}
                >
                    <div
                        style={{
                            display: 'flex',
                            justifyContent: 'space-between',
                            alignItems: 'start',
                            gap: 20,
                            flexWrap: 'wrap',
                        }}
                    >
                        <div style={{ maxWidth: 560 }}>
                            <span className="badge">Formation & placement</span>
                            <h2
                                style={{
                                    marginTop: 14,
                                    fontSize: 24,
                                    color: 'var(--dag-navy)',
                                }}
                            >
                                Comment suivre une formation DAG
                            </h2>
                            <p
                                className="muted"
                                style={{ marginTop: 8, fontSize: 14 }}
                            >
                                De l'inscription à l'attestation, tout se passe
                                dans votre espace apprenant.
                            </p>
                        </div>
                        <Link href={formations()} className="btn-primary">
                            Voir les formations →
                        </Link>
                    </div>

                    <div className="grid-3" style={{ marginTop: 32 }}>
                        {FORMATION_STEPS.map((step) => (
                            <div
                                key={step.titre}
                                className="card"
                                style={{ padding: 24 }}
                            >
                                <span className="icon-box">
                                    <step.icon
                                        size={20}
                                        color="var(--dag-blue)"
                                    />
                                </span>
                                <h3
                                    style={{
                                        fontSize: 15.5,
                                        color: 'var(--dag-navy)',
                                    }}
                                >
                                    {step.titre}
                                </h3>
                                <p
                                    className="muted"
                                    style={{ marginTop: 8, fontSize: 13 }}
                                >
                                    {step.description}
                                </p>
                            </div>
                        ))}
                    </div>
                </div>
            </section>

            {activeMember && (
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
                    <span className="badge">L'équipe DAG</span>
                    <h2
                        style={{
                            marginTop: 14,
                            fontSize: 28,
                            color: 'var(--dag-navy)',
                        }}
                    >
                        Les visages derrière vos projets
                    </h2>
                    <p
                        className="muted"
                        style={{ marginTop: 10, maxWidth: 520 }}
                    >
                        Une équipe basée à Douala, qui suit chaque demande du
                        premier contact jusqu'à la livraison.
                    </p>

                    <div className="team-spotlight" style={{ marginTop: 32 }}>
                        <div className="team-content">
                            <div className="team-avatar-xl">
                                <img
                                    src={
                                        TEAM_PHOTOS[
                                            teamIndex % TEAM_PHOTOS.length
                                        ]
                                    }
                                    alt={activeMember.nom}
                                />
                            </div>
                            <h3
                                style={{
                                    marginTop: 16,
                                    fontSize: 17,
                                    color: 'var(--dag-navy)',
                                }}
                            >
                                {activeMember.nom}
                            </h3>
                            <span className="badge" style={{ marginTop: 6 }}>
                                {activeMember.role}
                            </span>
                            <p
                                className="muted"
                                style={{
                                    fontSize: 13,
                                    marginTop: 10,
                                    maxWidth: 280,
                                }}
                            >
                                {TEAM_TAGLINES[activeMember.role] ??
                                    'Accompagne vos projets au quotidien.'}
                            </p>
                        </div>
                        <div className="team-thumbs">
                            {equipeAffichee.map((member, index) => (
                                <button
                                    key={member.nom}
                                    type="button"
                                    className={`team-thumb${index === teamIndex ? ' active' : ''}`}
                                    onClick={() => setTeamIndex(index)}
                                    aria-label={member.nom}
                                >
                                    <img
                                        src={
                                            TEAM_PHOTOS[
                                                index % TEAM_PHOTOS.length
                                            ]
                                        }
                                        alt={member.nom}
                                    />
                                </button>
                            ))}
                        </div>
                    </div>
                </section>
            )}

            <section
                className="section-gap reveal container"
                style={{
                    display: 'flex',
                    flexDirection: 'column',
                    alignItems: 'center',
                    textAlign: 'center',
                }}
            >
                <h2
                    style={{
                        fontSize: 38,
                        fontWeight: 800,
                        color: 'var(--dag-navy)',
                    }}
                >
                    Témoignages
                </h2>

                <div
                    style={{
                        maxWidth: 640,
                        width: '100%',
                        marginTop: 36,
                    }}
                >
                    <div
                        style={{
                            display: 'flex',
                            justifyContent: 'center',
                            gap: 4,
                        }}
                    >
                        {Array.from({ length: 5 }).map((_, index) => {
                            const rempli = Math.max(
                                0,
                                Math.min(1, activeTestimonial.note - index),
                            );

                            return (
                                <span
                                    key={index}
                                    style={{
                                        position: 'relative',
                                        width: 17,
                                        height: 17,
                                        flex: 'none',
                                    }}
                                >
                                    <Star size={17} color="var(--dag-orange)" />
                                    {rempli > 0 && (
                                        <span
                                            style={{
                                                position: 'absolute',
                                                inset: 0,
                                                overflow: 'hidden',
                                                width: `${rempli * 100}%`,
                                            }}
                                        >
                                            <Star
                                                size={17}
                                                fill="var(--dag-orange)"
                                                color="var(--dag-orange)"
                                            />
                                        </span>
                                    )}
                                </span>
                            );
                        })}
                    </div>
                    <p
                        style={{
                            marginTop: 18,
                            fontSize: 20,
                            fontStyle: 'italic',
                            lineHeight: 1.7,
                            color: 'var(--dag-text-muted)',
                        }}
                    >
                        « {activeTestimonial.avis} »
                    </p>
                    <div
                        style={{
                            marginTop: 28,
                            display: 'flex',
                            alignItems: 'center',
                            justifyContent: 'center',
                            gap: 14,
                        }}
                    >
                        <span className="testimonial-avatar">
                            {activeTestimonial.nom
                                .split(' ')
                                .map((part) => part.charAt(0))
                                .join('')}
                        </span>
                        <div style={{ textAlign: 'left' }}>
                            <p
                                style={{
                                    fontSize: 16,
                                    color: 'var(--dag-text-muted)',
                                }}
                            >
                                {activeTestimonial.nom}
                            </p>
                            <p className="muted" style={{ fontSize: 13 }}>
                                {activeTestimonial.meta}
                            </p>
                        </div>
                    </div>
                    <div className="testimonial-dots">
                        {TESTIMONIALS.map((item, index) => (
                            <button
                                key={item.nom}
                                type="button"
                                className={`testimonial-dot${index === testimonialIndex ? ' active' : ''}`}
                                aria-label={`Témoignage ${index + 1}`}
                                onClick={() => setTestimonialIndex(index)}
                            />
                        ))}
                    </div>
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
                            Besoin d'un accompagnement sur-mesure ?
                        </h2>
                        <p
                            style={{
                                marginTop: 10,
                                fontSize: 13.5,
                                color: 'rgba(255,255,255,.8)',
                            }}
                        >
                            Contactez-nous directement, ou demandez un audit
                            gratuit de votre page sans créer de compte.
                        </p>
                    </div>
                    <div style={{ display: 'flex', gap: 12, flexWrap: 'wrap' }}>
                        <button
                            type="button"
                            className="btn-primary"
                            onClick={() => setAuditModalOpen(true)}
                        >
                            Audit gratuit
                        </button>
                        <a
                            href="https://wa.me/237699000000"
                            target="_blank"
                            rel="noreferrer"
                            className="btn-ghost btn-ghost-dark"
                        >
                            WhatsApp
                        </a>
                        <a
                            href="tel:+237699000000"
                            className="btn-ghost btn-ghost-dark"
                        >
                            Téléphone
                        </a>
                        <a
                            href="mailto:contact@dag.cm"
                            className="btn-ghost btn-ghost-dark"
                        >
                            E-mail
                        </a>
                    </div>
                </div>
            </section>

            <AuditRequestModal
                open={auditModalOpen}
                onClose={() => setAuditModalOpen(false)}
            />
        </PublicLayout>
    );
}
