import { Head, Link } from '@inertiajs/react';
import { Award, FileText, Folder, Mic, TrendingUp, User } from 'lucide-react';
import { type EspaceApprenantInfo } from '@/layouts/espace-apprenant-layout';
import espaceApprenant from '@/routes/espace-apprenant';

type FormationDetail = {
    nom: string;
    ville: string | null;
    mode: string | null;
    dateDebut: string | null;
    dateFin: string | null;
};

type Attestation = {
    formation: string;
    dateEmission: string | null;
    seancesRestantes: number;
};

type Props = {
    apprenant: EspaceApprenantInfo;
    stats: {
        formationEnCours: string | null;
        progression: number;
        candidaturesEnvoyees: number;
        seancesRestantes: number;
    };
    formationDetail: FormationDetail | null;
    attestation: Attestation | null;
};

export default function ApprenantDashboard({
    apprenant,
    stats,
    formationDetail,
    attestation,
}: Props) {
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

            <div className="card" style={{ padding: 24, marginTop: 28 }}>
                <p style={{ fontSize: 14, fontWeight: 700, marginBottom: 16 }}>
                    Actions rapides
                </p>
                <div style={{ display: 'flex', gap: 12, flexWrap: 'wrap' }}>
                    <Link
                        href={espaceApprenant.inscriptions()}
                        className="espace-action-btn"
                    >
                        <FileText size={16} />
                        Mes inscriptions
                    </Link>
                    <Link
                        href={espaceApprenant.dossier()}
                        className="espace-action-btn"
                    >
                        <Folder size={16} />
                        Mon dossier
                    </Link>
                    <span
                        className="espace-action-btn inert"
                        title="Disponible une fois votre formation terminée"
                    >
                        <Award size={16} />
                        Aperçu attestation
                    </span>
                </div>
            </div>

            <div className="grid-3" style={{ marginTop: 28 }}>
                <div
                    className="card"
                    style={{ padding: 24, gridColumn: 'span 2' }}
                >
                    <p
                        style={{
                            fontSize: 14,
                            fontWeight: 700,
                            marginBottom: 16,
                        }}
                    >
                        Formation en cours
                    </p>
                    {formationDetail === null ? (
                        <>
                            <p className="muted" style={{ fontSize: 13 }}>
                                Aucune formation en cours pour le moment.
                            </p>
                            <Link
                                href={espaceApprenant.formations()}
                                className="btn-outline"
                                style={{ marginTop: 14 }}
                            >
                                Parcourir les formations
                            </Link>
                        </>
                    ) : (
                        <>
                            <h3
                                style={{
                                    fontSize: 15.5,
                                    color: 'var(--dag-navy)',
                                }}
                            >
                                {formationDetail.nom}
                            </h3>
                            <div
                                className="training-session-meta"
                                style={{ marginTop: 10 }}
                            >
                                {formationDetail.ville && (
                                    <span>📍 {formationDetail.ville}</span>
                                )}
                                {formationDetail.mode && (
                                    <span>🖥️ {formationDetail.mode}</span>
                                )}
                                {formationDetail.dateDebut && (
                                    <span>
                                        🗓️ Du {formationDetail.dateDebut}{' '}
                                        {formationDetail.dateFin
                                            ? `au ${formationDetail.dateFin}`
                                            : ''}
                                    </span>
                                )}
                            </div>
                            <Link
                                href={espaceApprenant.progression()}
                                className="btn-outline"
                                style={{ marginTop: 16 }}
                            >
                                Voir ma progression
                            </Link>
                        </>
                    )}
                </div>

                <div className="card" style={{ padding: 24 }}>
                    <p
                        style={{
                            fontSize: 14,
                            fontWeight: 700,
                            marginBottom: 12,
                        }}
                    >
                        Attestation de formation
                    </p>
                    {attestation === null ? (
                        <p
                            className="muted"
                            style={{ fontSize: 12.5, lineHeight: 1.6 }}
                        >
                            {formationDetail === null
                                ? "Disponible une fois inscrit à une formation, terminée puis validée par l'équipe DAG."
                                : stats.seancesRestantes === 0
                                  ? "Formation terminée — en attente de validation par l'équipe DAG."
                                  : `Disponible une fois votre formation terminée et validée par l'équipe DAG — ${stats.seancesRestantes} séance(s) restante(s).`}
                        </p>
                    ) : (
                        <p
                            className="muted"
                            style={{
                                fontSize: 12.5,
                                lineHeight: 1.6,
                                marginBottom: 14,
                            }}
                        >
                            Délivrée le {attestation.dateEmission} pour{' '}
                            {attestation.formation}.
                        </p>
                    )}
                    <span
                        className="link-action"
                        style={{
                            opacity: 0.5,
                            cursor: 'not-allowed',
                            display: 'inline-block',
                            marginTop: 8,
                        }}
                        title="Aperçu PDF bientôt disponible"
                    >
                        Aperçu de l'attestation
                    </span>
                </div>
            </div>
        </>
    );
}
