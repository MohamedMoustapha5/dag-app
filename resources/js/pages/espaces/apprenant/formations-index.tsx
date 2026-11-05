import { Head, Link } from '@inertiajs/react';
import {
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
import { useMemo, useState } from 'react';
import { libelleFraisInscription } from '@/lib/frais-inscription';
import { correspond } from '@/lib/recherche';

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
    prix: string | null;
    peutSInscrire: boolean;
    inscriptionUrl: string;
    statut: string;
    image: string | null;
};

type Props = {
    sessions: Session[];
};

const ALL_CATEGORIES = 'Toutes les catégories';
const ALL_VILLES = 'Toutes les villes';

const CATEGORY_ICONS: Record<string, LucideIcon> = {
    [ALL_CATEGORIES]: LayoutGrid,
    'Community management': Users,
    'Création de contenu': PenTool,
    'Stratégie & publicité': TrendingUp,
};
const DEFAULT_CATEGORY_ICON = Sparkles;

export default function ApprenantFormationsIndex({ sessions }: Props) {
    const [search, setSearch] = useState('');
    const [activeCategory, setActiveCategory] =
        useState<string>(ALL_CATEGORIES);
    const [activeVille, setActiveVille] = useState<string>(ALL_VILLES);

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

    const filteredSessions = useMemo(
        () =>
            sessions.filter((session) => {
                const matchesSearch = correspond(
                    search,
                    session.nom,
                    session.categorie,
                    session.ville,
                );
                const matchesCategory =
                    activeCategory === ALL_CATEGORIES ||
                    session.categorie === activeCategory;
                const matchesVille =
                    activeVille === ALL_VILLES || session.ville === activeVille;
                return matchesSearch && matchesCategory && matchesVille;
            }),
        [sessions, search, activeCategory, activeVille],
    );

    return (
        <>
            <Head title="Formations" />

            <div
                className="espace-hero"
                style={{
                    backgroundImage:
                        "url('/images/photos/formation-digital-marketing.jpg')",
                }}
            >
                <div className="espace-hero-overlay" />
                <div
                    style={{
                        position: 'relative',
                        zIndex: 1,
                        display: 'flex',
                        justifyContent: 'space-between',
                        alignItems: 'center',
                        gap: 16,
                        flexWrap: 'wrap',
                        width: '100%',
                    }}
                >
                    <div className="espace-hero-content">
                        <h1>Formations</h1>
                        <p>
                            Toutes les formations DAG, accessibles sans quitter
                            votre espace apprenant.
                        </p>
                    </div>
                    <span className="badge badge-dark">
                        {sessions.length} session(s)
                    </span>
                </div>
            </div>

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

            <nav
                className="catalogue-category-row"
                aria-label="Filtrer par catégorie"
                style={{ marginTop: 22 }}
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

            <div className="catalogue-results-head" style={{ marginTop: 28 }}>
                <h2 style={{ fontSize: 17, color: 'var(--dag-navy)' }}>
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
                </div>
            </div>

            {filteredSessions.length === 0 ? (
                <p className="data-table-empty">
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
                                <span className="badge">{session.mode}</span>
                            </div>
                            <div className="training-session-body">
                                <span className="training-session-level">
                                    {session.categorie}
                                </span>
                                <h3>{session.nom}</h3>
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
                                            🎓 Formateur : {session.formateur}
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
                                <div
                                    className="training-session-footer"
                                    style={{
                                        display: 'flex',
                                        alignItems: 'center',
                                        justifyContent: 'space-between',
                                        gap: 12,
                                        flexWrap: 'wrap',
                                    }}
                                >
                                    <div>
                                        <p
                                            className="muted"
                                            style={{ fontSize: 11.5 }}
                                        >
                                            Frais d'inscription
                                        </p>
                                        <p
                                            style={{
                                                fontSize: 16,
                                                fontWeight: 800,
                                                color: 'var(--dag-navy)',
                                            }}
                                        >
                                            {libelleFraisInscription(
                                                session.prix,
                                            )}
                                        </p>
                                    </div>
                                    {session.peutSInscrire ? (
                                        <Link
                                            href={session.inscriptionUrl}
                                            className="btn-primary"
                                        >
                                            S'inscrire
                                        </Link>
                                    ) : (
                                        <span className="chip chip-gray">
                                            {session.placesRestantes <= 0
                                                ? 'Complet'
                                                : 'Inscriptions bientôt'}
                                        </span>
                                    )}
                                </div>
                            </div>
                        </div>
                    ))}
                </div>
            )}
        </>
    );
}
