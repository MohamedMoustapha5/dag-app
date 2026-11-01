import { Head, Link } from '@inertiajs/react';
import { FileText, Mic, TrendingUp, User } from 'lucide-react';
import { type EspaceApprenantInfo } from '@/layouts/espace-apprenant-layout';
import espaceApprenant from '@/routes/espace-apprenant';

type Props = {
    apprenant: EspaceApprenantInfo;
    stats: {
        formationEnCours: string | null;
        progression: number;
        candidaturesEnvoyees: number;
        seancesRestantes: number;
    };
};

export default function ApprenantDashboard({ apprenant, stats }: Props) {
    return (
        <>
            <Head title="Espace apprenant" />

            <div
                className="espace-hero"
                style={{
                    backgroundImage:
                        "url('/images/photos/auditorium-welcome.jpg')",
                }}
            >
                <div className="espace-hero-overlay" />
                <div className="espace-hero-content">
                    <h1>Bonjour {apprenant.nom.split(' ')[0]} 👋</h1>
                    <p>Voici où en est votre formation DAG.</p>
                    <Link
                        href={espaceApprenant.formations()}
                        className="btn-primary"
                        style={{ marginTop: 16 }}
                    >
                        + Parcourir les formations
                    </Link>
                </div>
            </div>

            <div className="grid-4 espace-stats-grid">
                <div className="card espace-stat-card" style={{ padding: 22 }}>
                    <span
                        className="espace-stat-icon"
                        style={{
                            background: 'var(--dag-blue-light)',
                            color: 'var(--dag-blue)',
                        }}
                    >
                        <User size={18} />
                    </span>
                    <strong
                        style={{
                            display: 'block',
                            fontSize: 20,
                            fontWeight: 800,
                        }}
                    >
                        {stats.formationEnCours ?? 'Aucune'}
                    </strong>
                    <span className="muted" style={{ fontSize: 12.5 }}>
                        Formation en cours
                    </span>
                </div>

                <div className="card espace-stat-card" style={{ padding: 22 }}>
                    <span
                        className="espace-stat-icon"
                        style={{
                            background: 'var(--dag-orange-bg)',
                            color: 'var(--dag-orange-ink)',
                        }}
                    >
                        <TrendingUp size={18} />
                    </span>
                    <strong
                        style={{
                            display: 'block',
                            fontSize: 24,
                            fontWeight: 800,
                        }}
                    >
                        {stats.progression}%
                    </strong>
                    <span className="muted" style={{ fontSize: 12.5 }}>
                        Progression
                    </span>
                </div>

                <div className="card espace-stat-card" style={{ padding: 22 }}>
                    <span
                        className="espace-stat-icon"
                        style={{
                            background: 'var(--dag-green-bg)',
                            color: 'var(--dag-green)',
                        }}
                    >
                        <FileText size={18} />
                    </span>
                    <strong
                        style={{
                            display: 'block',
                            fontSize: 24,
                            fontWeight: 800,
                        }}
                    >
                        {stats.candidaturesEnvoyees}
                    </strong>
                    <span className="muted" style={{ fontSize: 12.5 }}>
                        Candidatures envoyées
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
                        <Mic size={18} />
                    </span>
                    <strong
                        style={{
                            display: 'block',
                            fontSize: 24,
                            fontWeight: 800,
                        }}
                    >
                        {stats.seancesRestantes}
                    </strong>
                    <span className="muted" style={{ fontSize: 12.5 }}>
                        Séances restantes
                    </span>
                </div>
            </div>
        </>
    );
}
