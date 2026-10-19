import { Head, Link } from '@inertiajs/react';
import PublicLayout from '@/layouts/public-layout';
import { contact, formations } from '@/routes';

type Formation = {
    id: string;
    nom: string;
    description: string | null;
    programme: string | null;
    duree: string | null;
    prerequis: string | null;
    categorie: string;
};

type Session = {
    id: string;
    ville: string | null;
    mode: string;
    lieu: string | null;
    formateur: string | null;
    dateDebut: string | null;
    dateFin: string | null;
    placesRestantes: number;
    statut: string;
};

type FormationDetailProps = {
    formation: Formation;
    sessions: Session[];
};

export default function FormationDetail({
    formation,
    sessions,
}: FormationDetailProps) {
    return (
        <PublicLayout>
            <Head title={`${formation.nom} — Formations DAG`} />

            <section className="container" style={{ paddingTop: 32 }}>
                <p className="breadcrumb">
                    <Link href={formations()}>Formations</Link> /{' '}
                    {formation.nom}
                </p>

                <div className="split-layout-wide" style={{ marginTop: 20 }}>
                    <div>
                        <span className="badge">{formation.categorie}</span>
                        <h1
                            style={{
                                marginTop: 14,
                                fontSize: 30,
                                color: 'var(--dag-navy)',
                            }}
                        >
                            {formation.nom}
                        </h1>
                        {formation.description && (
                            <p
                                className="muted"
                                style={{
                                    marginTop: 14,
                                    fontSize: 14.5,
                                    lineHeight: 1.7,
                                }}
                            >
                                {formation.description}
                            </p>
                        )}

                        {formation.programme && (
                            <div
                                className="card reveal"
                                style={{ padding: 24, marginTop: 28 }}
                            >
                                <h2
                                    style={{
                                        fontSize: 17,
                                        color: 'var(--dag-navy)',
                                    }}
                                >
                                    Programme
                                </h2>
                                <p
                                    style={{
                                        marginTop: 10,
                                        fontSize: 13.5,
                                        lineHeight: 1.8,
                                        whiteSpace: 'pre-line',
                                    }}
                                >
                                    {formation.programme}
                                </p>
                            </div>
                        )}

                        <div
                            style={{ display: 'flex', gap: 24, marginTop: 24 }}
                        >
                            {formation.duree && (
                                <div>
                                    <span
                                        className="muted"
                                        style={{
                                            fontSize: 11.5,
                                            fontWeight: 700,
                                            textTransform: 'uppercase',
                                        }}
                                    >
                                        Durée
                                    </span>
                                    <p style={{ fontSize: 14, marginTop: 4 }}>
                                        {formation.duree}
                                    </p>
                                </div>
                            )}
                            {formation.prerequis && (
                                <div>
                                    <span
                                        className="muted"
                                        style={{
                                            fontSize: 11.5,
                                            fontWeight: 700,
                                            textTransform: 'uppercase',
                                        }}
                                    >
                                        Prérequis
                                    </span>
                                    <p style={{ fontSize: 14, marginTop: 4 }}>
                                        {formation.prerequis}
                                    </p>
                                </div>
                            )}
                        </div>
                    </div>

                    <aside>
                        <h2 className="training-section-title">
                            Sessions ouvertes
                        </h2>
                        {sessions.length === 0 ? (
                            <p className="service-empty-state">
                                Aucune session ouverte pour cette formation
                                actuellement.
                            </p>
                        ) : (
                            <div
                                style={{
                                    display: 'flex',
                                    flexDirection: 'column',
                                    gap: 16,
                                }}
                            >
                                {sessions.map((session) => (
                                    <div
                                        key={session.id}
                                        className="card"
                                        style={{ padding: 20 }}
                                    >
                                        <span className="chip chip-blue">
                                            {session.mode}
                                        </span>
                                        <div
                                            className="training-session-meta"
                                            style={{ marginTop: 12 }}
                                        >
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
                                                    🎓 {session.formateur}
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
                                                {session.placesRestantes}{' '}
                                                place(s) restante(s)
                                            </span>
                                        </div>
                                        <Link
                                            href={contact()}
                                            className="btn-primary btn-block"
                                            style={{ marginTop: 16 }}
                                        >
                                            S'inscrire
                                        </Link>
                                    </div>
                                ))}
                            </div>
                        )}
                    </aside>
                </div>
            </section>
        </PublicLayout>
    );
}
