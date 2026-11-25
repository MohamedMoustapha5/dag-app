import { Head, Link } from '@inertiajs/react';
import { Calendar, Check, TrendingUp, X } from 'lucide-react';
import espaceApprenant from '@/routes/espace-apprenant';

type Seance = {
    id: string;
    titre: string;
    date: string | null;
    present: boolean | null;
    statutPresence: 'PRESENT' | 'RETARD' | 'ABSENT' | 'EXCUSE' | null;
};

type Props = {
    formation: string;
    progression: number;
    stats: {
        suivies: number;
        manquees: number;
        aVenir: number;
    };
    seances: Seance[];
};

export default function ApprenantProgressionShow({
    formation,
    progression,
    stats,
    seances,
}: Props) {
    return (
        <>
            <Head title={`Progression — ${formation}`} />

            <Link
                href={espaceApprenant.progression()}
                className="link-action"
                style={{ marginBottom: 16, display: 'inline-block' }}
            >
                ← Toutes mes formations
            </Link>

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
                    <p>Votre avancement dans {formation}.</p>
                </div>
            </div>

            <div
                className="grid-3 espace-stats-grid"
                style={{ marginBottom: 24 }}
            >
                <div className="card espace-stat-card" style={{ padding: 22 }}>
                    <span
                        className="espace-stat-icon"
                        style={{
                            background: 'var(--dag-green-bg)',
                            color: 'var(--dag-green)',
                        }}
                    >
                        <Check size={18} />
                    </span>
                    <strong
                        style={{
                            display: 'block',
                            fontSize: 24,
                            fontWeight: 800,
                        }}
                    >
                        {stats.suivies}
                    </strong>
                    <span className="muted" style={{ fontSize: 12.5 }}>
                        Séances suivies
                    </span>
                </div>
                <div className="card espace-stat-card" style={{ padding: 22 }}>
                    <span
                        className="espace-stat-icon"
                        style={{
                            background: 'var(--dag-red-bg)',
                            color: 'var(--dag-red)',
                        }}
                    >
                        <X size={18} />
                    </span>
                    <strong
                        style={{
                            display: 'block',
                            fontSize: 24,
                            fontWeight: 800,
                        }}
                    >
                        {stats.manquees}
                    </strong>
                    <span className="muted" style={{ fontSize: 12.5 }}>
                        Séances manquées
                    </span>
                </div>
                <div className="card espace-stat-card" style={{ padding: 22 }}>
                    <span
                        className="espace-stat-icon"
                        style={{
                            background: '#f1f2f6',
                            color: 'var(--dag-text-muted)',
                        }}
                    >
                        <Calendar size={18} />
                    </span>
                    <strong
                        style={{
                            display: 'block',
                            fontSize: 24,
                            fontWeight: 800,
                        }}
                    >
                        {stats.aVenir}
                    </strong>
                    <span className="muted" style={{ fontSize: 12.5 }}>
                        Séances à venir
                    </span>
                </div>
            </div>

            <div className="card" style={{ padding: 24, marginBottom: 24 }}>
                <div
                    style={{
                        display: 'flex',
                        alignItems: 'center',
                        justifyContent: 'space-between',
                        fontSize: 13,
                    }}
                >
                    <span
                        className="muted"
                        style={{
                            display: 'flex',
                            alignItems: 'center',
                            gap: 8,
                        }}
                    >
                        <TrendingUp
                            size={15}
                            style={{ color: 'var(--dag-blue)' }}
                        />
                        Progression globale — {formation}
                    </span>
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
                <p style={{ fontSize: 14, fontWeight: 700, marginBottom: 16 }}>
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
                                                : seance.present === false
                                                  ? 'var(--dag-red-bg)'
                                                  : '#f1f2f6',
                                        color:
                                            seance.present === true
                                                ? 'var(--dag-green)'
                                                : seance.present === false
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
                                        {seance.date ?? 'Date à confirmer'}
                                        {seance.present === true &&
                                            (seance.statutPresence === 'RETARD'
                                                ? ' — présent (en retard)'
                                                : ' — présent')}
                                        {seance.present === false &&
                                            (seance.statutPresence === 'EXCUSE'
                                                ? ' — absence excusée'
                                                : ' — absent')}
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
    );
}
