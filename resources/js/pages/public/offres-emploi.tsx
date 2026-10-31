import { Head, Link } from '@inertiajs/react';
import PublicLayout from '@/layouts/public-layout';
import { register } from '@/routes';

type Offre = {
    id: string;
    titre: string;
    description: string;
    entreprise: string;
    typeContrat: string;
    duree: string | null;
    ville: string | null;
    dateLimite: string | null;
};

type OffresEmploiProps = {
    offres: Offre[];
};

export default function OffresEmploi({ offres }: OffresEmploiProps) {
    return (
        <PublicLayout>
            <Head title="Offres de stage & emploi — DAG" />

            <section className="service-catalogue-intro reveal container">
                <div
                    className="service-catalogue-hero"
                    style={{
                        backgroundImage:
                            "url('/images/photos/handshake-dashboard.jpg')",
                    }}
                >
                    <div className="service-catalogue-hero-content">
                        <span className="badge">Offres de stage & emploi</span>
                        <h1>Rejoignez une entreprise partenaire de DAG</h1>
                        <p>
                            Stages et emplois proposés par nos entreprises
                            partenaires, pour nos apprenants formés.
                        </p>
                    </div>
                </div>
            </section>

            <section
                className="section-gap container"
                style={{ marginBottom: 88 }}
            >
                {offres.length === 0 ? (
                    <p className="service-empty-state">
                        Aucune offre ouverte pour le moment.
                    </p>
                ) : (
                    <div className="grid-3">
                        {offres.map((offre) => (
                            <div
                                key={offre.id}
                                className="card"
                                style={{
                                    padding: 24,
                                    display: 'flex',
                                    flexDirection: 'column',
                                }}
                            >
                                <span
                                    className="chip chip-blue"
                                    style={{ alignSelf: 'start' }}
                                >
                                    {offre.typeContrat}
                                </span>
                                <h3
                                    style={{
                                        marginTop: 14,
                                        fontSize: 16,
                                        color: 'var(--dag-navy)',
                                    }}
                                >
                                    {offre.titre}
                                </h3>
                                <p
                                    className="muted"
                                    style={{ marginTop: 6, fontSize: 12.5 }}
                                >
                                    {offre.entreprise}
                                </p>
                                <p
                                    className="muted"
                                    style={{
                                        marginTop: 10,
                                        fontSize: 13,
                                        flex: 1,
                                    }}
                                >
                                    {offre.description}
                                </p>
                                <div
                                    className="training-session-meta"
                                    style={{ marginTop: 14 }}
                                >
                                    {offre.ville && (
                                        <span>📍 {offre.ville}</span>
                                    )}
                                    {offre.duree && (
                                        <span>⏱️ {offre.duree}</span>
                                    )}
                                    {offre.dateLimite && (
                                        <span>
                                            📅 Jusqu'au {offre.dateLimite}
                                        </span>
                                    )}
                                </div>
                                <Link
                                    href={register()}
                                    className="btn-primary btn-block"
                                    style={{ marginTop: 18 }}
                                >
                                    Postuler
                                </Link>
                            </div>
                        ))}
                    </div>
                )}
            </section>
        </PublicLayout>
    );
}
