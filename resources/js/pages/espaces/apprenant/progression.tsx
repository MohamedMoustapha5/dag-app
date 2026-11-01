import { Head, Link } from '@inertiajs/react';
import { Check, X } from 'lucide-react';
import espaceApprenant from '@/routes/espace-apprenant';

type Seance = {
    id: string;
    titre: string;
    date: string | null;
    present: boolean | null;
};

type Props = {
    formation: string | null;
    progression: number;
    seances: Seance[];
};

export default function ApprenantProgression({
    formation,
    progression,
    seances,
}: Props) {
    return (
        <>
            <Head title="Progression" />

            <div
                className="espace-hero"
                style={{
                    backgroundImage:
                        "url('/images/photos/presentation-data-review.jpg')",
                }}
            >
                <div className="espace-hero-overlay" />
                <div className="espace-hero-content">
                    <h1>Progression</h1>
                    <p>
                        {formation
                            ? `Votre avancement dans ${formation}.`
                            : "Le détail de votre progression s'affiche dès votre inscription validée."}
                    </p>
                </div>
            </div>

            {formation === null ? (
                <div
                    className="card"
                    style={{ padding: 32, textAlign: 'center' }}
                >
                    <p style={{ fontSize: 14, fontWeight: 700 }}>
                        Aucune formation en cours
                    </p>
                    <p className="muted" style={{ marginTop: 8, fontSize: 13 }}>
                        Inscrivez-vous à une session pour suivre votre
                        progression ici.
                    </p>
                    <Link
                        href={espaceApprenant.formations()}
                        className="btn-primary"
                        style={{ marginTop: 18, display: 'inline-flex' }}
                    >
                        Parcourir les formations
                    </Link>
                </div>
            ) : (
                <>
                    <div
                        className="card"
                        style={{ padding: 24, marginBottom: 24 }}
                    >
                        <div
                            style={{
                                display: 'flex',
                                justifyContent: 'space-between',
                                fontSize: 13,
                            }}
                        >
                            <span className="muted">Progression globale</span>
                            <strong style={{ color: 'var(--dag-navy)' }}>
                                {progression}%
                            </strong>
                        </div>
                        <div className="espace-formule-progress">
                            <div
                                className="espace-formule-progress-bar"
                                style={{ width: `${progression}%` }}
                            />
                        </div>
                    </div>

                    <div className="card" style={{ padding: 24 }}>
                        <p
                            style={{
                                fontSize: 14,
                                fontWeight: 700,
                                marginBottom: 16,
                            }}
                        >
                            Séances
                        </p>
                        {seances.length === 0 ? (
                            <p className="muted" style={{ fontSize: 13 }}>
                                Aucune séance programmée pour le moment.
                            </p>
                        ) : (
                            <div>
                                {seances.map((seance) => (
                                    <div
                                        key={seance.id}
                                        className="espace-timeline-item"
                                    >
                                        <span
                                            className="espace-timeline-icon"
                                            style={{
                                                background:
                                                    seance.present === true
                                                        ? 'var(--dag-green-bg)'
                                                        : seance.present ===
                                                            false
                                                          ? 'var(--dag-red-bg)'
                                                          : '#f1f2f6',
                                                color:
                                                    seance.present === true
                                                        ? 'var(--dag-green)'
                                                        : seance.present ===
                                                            false
                                                          ? 'var(--dag-red)'
                                                          : 'var(--dag-text-muted)',
                                            }}
                                        >
                                            {seance.present === true ? (
                                                <Check size={14} />
                                            ) : seance.present === false ? (
                                                <X size={14} />
                                            ) : (
                                                '—'
                                            )}
                                        </span>
                                        <div>
                                            <p className="espace-timeline-label">
                                                {seance.titre}
                                            </p>
                                            <p className="espace-timeline-date">
                                                {seance.date ??
                                                    'Date à confirmer'}
                                                {seance.present === true &&
                                                    ' — présent'}
                                                {seance.present === false &&
                                                    ' — absent'}
                                                {seance.present === null &&
                                                    ' — à venir'}
                                            </p>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}
                    </div>
                </>
            )}
        </>
    );
}
