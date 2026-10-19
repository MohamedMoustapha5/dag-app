import { Head, Link } from '@inertiajs/react';
import { useMemo, useState } from 'react';
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
    placesRestantes: number;
    statut: string;
    image: string | null;
};

type FormationsProps = {
    sessions: Session[];
};

const ALL_CATEGORIES = 'Toutes';

export default function Formations({ sessions }: FormationsProps) {
    const [activeCategory, setActiveCategory] =
        useState<string>(ALL_CATEGORIES);

    const categories = useMemo(() => {
        const unique = Array.from(
            new Set(sessions.map((session) => session.categorie)),
        );
        return [ALL_CATEGORIES, ...unique];
    }, [sessions]);

    const filteredSessions = useMemo(
        () =>
            sessions.filter(
                (session) =>
                    activeCategory === ALL_CATEGORIES ||
                    session.categorie === activeCategory,
            ),
        [sessions, activeCategory],
    );

    return (
        <PublicLayout>
            <Head title="Formations — DAG" />

            <section className="training-hero-wrap reveal container">
                <div className="training-hero">
                    <div className="training-hero-content">
                        <span className="badge">Formations</span>
                        <h1>
                            Montez en compétence avec des formateurs experts
                        </h1>
                        <p>
                            Sessions en présentiel ou à distance, animées par
                            des professionnels en activité.
                        </p>
                        <div className="training-hero-details">
                            <div className="training-hero-message">
                                <h2>
                                    Des parcours concrets, directement
                                    applicables en entreprise
                                </h2>
                            </div>
                            <div className="training-hero-stats">
                                <div>
                                    <strong>{sessions.length}</strong>
                                    <span>Sessions ouvertes</span>
                                </div>
                                <div>
                                    <strong>{categories.length - 1}</strong>
                                    <span>Domaines couverts</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section className="section-gap container">
                <h2 className="training-section-title">Sessions disponibles</h2>

                <nav className="cat-nav" style={{ marginBottom: 24 }}>
                    {categories.map((category) => (
                        <button
                            key={category}
                            type="button"
                            className={`pill${activeCategory === category ? ' active' : ''}`}
                            onClick={() => setActiveCategory(category)}
                        >
                            {category}
                        </button>
                    ))}
                </nav>

                {filteredSessions.length === 0 ? (
                    <p className="service-empty-state">
                        Aucune session ouverte dans cette catégorie pour le
                        moment.
                    </p>
                ) : (
                    <div className="training-session-grid">
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
