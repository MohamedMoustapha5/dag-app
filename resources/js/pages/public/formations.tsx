import { Head, Link } from '@inertiajs/react';
import {
    ArrowRight,
    ChevronDown,
    LayoutGrid,
    PenTool,
    Search,
    Sparkles,
    TrendingUp,
    Users,
    X,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { Fragment, useMemo, useState } from 'react';
import PublicLayout from '@/layouts/public-layout';
import formationsRoutes from '@/routes/formations';

type Session = {
    formationId: string;
    nom: string;
    categorie: string;
    sessionId: string;
    ville: string | null;
    mode: string;
    lieu: string | null;
    formateur: string | null;
    dateDebut: string | null;
    dateFin: string | null;
    places: number;
    placesRestantes: number;
    statut: string;
    image: string | null;
};

type FormationsProps = {
    sessions: Session[];
    stats: {
        placesMoyennes: number;
        tauxPlacement: number;
    };
};

const ALL_CATEGORIES = 'Toutes les catégories';
const ALL_VILLES = 'Toutes les villes';
const ALL_PERIODES = 'Toutes les périodes';

const CATEGORY_ICONS: Record<string, LucideIcon> = {
    [ALL_CATEGORIES]: LayoutGrid,
    'Community management': Users,
    'Création de contenu': PenTool,
    'Stratégie & publicité': TrendingUp,
};
const DEFAULT_CATEGORY_ICON = Sparkles;

const PROCESS_STEPS = [
    'Inscription',
    'Paiement',
    'Validation',
    'Formation',
    'Attestation',
    'Placement',
];

function periodeDe(dateIso: string): string {
    const date = new Date(`${dateIso}T00:00:00`);
    const label = new Intl.DateTimeFormat('fr-FR', {
        month: 'long',
        year: 'numeric',
    }).format(date);
    return label.charAt(0).toUpperCase() + label.slice(1);
}

export default function Formations({ sessions, stats }: FormationsProps) {
    const [search, setSearch] = useState('');
    const [activeCategory, setActiveCategory] =
        useState<string>(ALL_CATEGORIES);
    const [activeVille, setActiveVille] = useState<string>(ALL_VILLES);
    const [activePeriode, setActivePeriode] = useState<string>(ALL_PERIODES);

    const categories = useMemo(() => {
        const unique = Array.from(
            new Set(sessions.map((session) => session.categorie)),
        );
        return [ALL_CATEGORIES, ...unique];
    }, [sessions]);

    const villes = useMemo(() => {
        const unique = Array.from(
            new Set(
                sessions
                    .map((session) => session.ville)
                    .filter((ville): ville is string => Boolean(ville)),
            ),
        );
        return [ALL_VILLES, ...unique];
    }, [sessions]);

    const periodes = useMemo(() => {
        const unique = Array.from(
            new Set(
                sessions
                    .filter((session) => session.dateDebut)
                    .map((session) => periodeDe(session.dateDebut as string)),
            ),
        );
        return [ALL_PERIODES, ...unique];
    }, [sessions]);

    const filteredSessions = useMemo(
        () =>
            sessions.filter((session) => {
                const matchesSearch =
                    search.trim() === '' ||
                    session.nom.toLowerCase().includes(search.toLowerCase()) ||
                    session.categorie
                        .toLowerCase()
                        .includes(search.toLowerCase());
                const matchesCategory =
                    activeCategory === ALL_CATEGORIES ||
                    session.categorie === activeCategory;
                const matchesVille =
                    activeVille === ALL_VILLES || session.ville === activeVille;
                const matchesPeriode =
                    activePeriode === ALL_PERIODES ||
                    (session.dateDebut &&
                        periodeDe(session.dateDebut) === activePeriode);
                return (
                    matchesSearch &&
                    matchesCategory &&
                    matchesVille &&
                    matchesPeriode
                );
            }),
        [sessions, search, activeCategory, activeVille, activePeriode],
    );

    return (
        <PublicLayout>
            <Head title="Formations — DAG" />

            <section className="training-hero-wrap reveal container">
                <div className="training-hero">
                    <div className="training-hero-overlay" />
                    <div className="training-hero-content">
                        <span className="badge">Module formation</span>
                        <h1>Formations & placement en entreprise</h1>
                        <p>
                            Formez-vous en community management et création de
                            contenu, puis candidatez aux offres de nos
                            entreprises partenaires. Prix non affiché
                            publiquement — négocié une fois connecté.
                        </p>
                        <div className="training-hero-details">
                            <div className="training-hero-message">
                                <span className="badge">
                                    Sessions en petits groupes
                                </span>
                                <h2>
                                    Des formations pratiques, animées par des
                                    professionnels en activité
                                </h2>
                            </div>
                            <div className="training-hero-stats">
                                <div>
                                    <strong>{stats.placesMoyennes}</strong>
                                    <span>places / session</span>
                                </div>
                                <div>
                                    <strong>{stats.tauxPlacement}%</strong>
                                    <span>taux de placement</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section className="section-gap reveal container">
                <div className="process-track-card">
                    {PROCESS_STEPS.map((step, index) => {
                        const isLast = index === PROCESS_STEPS.length - 1;
                        return (
                            <Fragment key={step}>
                                <div className="process-step">
                                    <span
                                        className={`process-step-dot${isLast ? ' done' : ''}`}
                                    >
                                        {index + 1}
                                    </span>
                                    <span className="process-step-label">
                                        {step}
                                    </span>
                                </div>
                                {!isLast && (
                                    <span
                                        className="process-step-arrow"
                                        aria-hidden="true"
                                    >
                                        <ArrowRight size={16} />
                                    </span>
                                )}
                            </Fragment>
                        );
                    })}
                </div>
            </section>

            <section className="container">
                <div className="catalogue-search-bar">
                    <Search size={18} />
                    <input
                        type="text"
                        value={search}
                        onChange={(event) => setSearch(event.target.value)}
                        placeholder="Rechercher une formation, une catégorie..."
                        aria-label="Rechercher une formation"
                    />
                    {search && (
                        <button
                            type="button"
                            className="catalogue-search-clear"
                            onClick={() => setSearch('')}
                            aria-label="Effacer la recherche"
                        >
                            <X size={14} />
                        </button>
                    )}
                </div>
            </section>

            <section className="container">
                <nav
                    className="catalogue-category-row"
                    aria-label="Filtrer par catégorie"
                >
                    {categories.map((category) => {
                        const Icon =
                            CATEGORY_ICONS[category] ?? DEFAULT_CATEGORY_ICON;

                        return (
                            <button
                                key={category}
                                type="button"
                                className={`catalogue-category-btn${activeCategory === category ? ' active' : ''}`}
                                onClick={() => setActiveCategory(category)}
                            >
                                <span className="catalogue-category-icon">
                                    <Icon size={20} />
                                </span>
                                <span className="catalogue-category-label">
                                    {category === ALL_CATEGORIES
                                        ? 'Toutes'
                                        : category}
                                </span>
                            </button>
                        );
                    })}
                </nav>
            </section>

            <section className="section-gap container">
                <div className="catalogue-results-head">
                    <h2
                        className="training-section-title"
                        style={{ marginBottom: 0 }}
                    >
                        Sessions à venir
                    </h2>
                    <div className="catalogue-results-controls">
                        <span className="muted" style={{ fontSize: 13 }}>
                            {filteredSessions.length} sur {sessions.length}
                        </span>
                        <div className="select-icon-wrap">
                            <select
                                id="ville"
                                aria-label="Ville"
                                value={activeVille}
                                onChange={(event) =>
                                    setActiveVille(event.target.value)
                                }
                            >
                                {villes.map((ville) => (
                                    <option key={ville} value={ville}>
                                        {ville}
                                    </option>
                                ))}
                            </select>
                            <ChevronDown
                                size={16}
                                className="select-icon-chevron"
                            />
                        </div>
                        <div className="select-icon-wrap">
                            <select
                                id="periode"
                                aria-label="Date de session"
                                value={activePeriode}
                                onChange={(event) =>
                                    setActivePeriode(event.target.value)
                                }
                            >
                                {periodes.map((periode) => (
                                    <option key={periode} value={periode}>
                                        {periode}
                                    </option>
                                ))}
                            </select>
                            <ChevronDown
                                size={16}
                                className="select-icon-chevron"
                            />
                        </div>
                    </div>
                </div>

                {filteredSessions.length === 0 ? (
                    <p className="service-empty-state">
                        Aucune session ouverte ne correspond à ces filtres.
                    </p>
                ) : (
                    <div
                        className="training-session-grid"
                        style={{ marginTop: 22 }}
                    >
                        {filteredSessions.map((session) => (
                            <div
                                key={session.sessionId}
                                className="card training-session-card"
                            >
                                <div
                                    className="training-session-image"
                                    style={{
                                        backgroundImage: `url('${session.image ?? '/images/photos/lms-dashboard.jpg'}')`,
                                        backgroundSize: 'cover',
                                        backgroundPosition: 'center',
                                    }}
                                >
                                    <span className="badge">
                                        {session.mode}
                                    </span>
                                </div>
                                <div className="training-session-body">
                                    <span className="training-session-level">
                                        {session.categorie}
                                    </span>
                                    <h3>
                                        <Link
                                            href={formationsRoutes.show(
                                                session.formationId,
                                            )}
                                        >
                                            {session.nom}
                                        </Link>
                                    </h3>
                                    <div className="training-session-meta">
                                        {session.ville && (
                                            <span>
                                                📍 {session.ville}
                                                {session.lieu
                                                    ? ` — ${session.lieu}`
                                                    : ''}
                                            </span>
                                        )}
                                        {session.formateur && (
                                            <span>
                                                🎓 Formateur :{' '}
                                                {session.formateur}
                                            </span>
                                        )}
                                        {session.dateDebut && (
                                            <span>
                                                🗓️ Du {session.dateDebut}{' '}
                                                {session.dateFin
                                                    ? `au ${session.dateFin}`
                                                    : ''}
                                            </span>
                                        )}
                                        <span>
                                            {session.placesRestantes} place(s)
                                            restante(s)
                                        </span>
                                    </div>
                                    <div className="training-session-footer">
                                        <Link
                                            href={formationsRoutes.show(
                                                session.formationId,
                                            )}
                                            className="btn-primary"
                                        >
                                            Voir la session
                                        </Link>
                                    </div>
                                </div>
                            </div>
                        ))}
                    </div>
                )}
            </section>
        </PublicLayout>
    );
}
