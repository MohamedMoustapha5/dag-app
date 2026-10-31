import { Head, Link } from '@inertiajs/react';
import {
    Briefcase,
    CheckCircle2,
    FileSignature,
    Plus,
    TrendingUp,
    UserCheck,
    Users,
} from 'lucide-react';
import espaceClient from '@/routes/espace-client';
import candidatures from '@/routes/espace-client/candidatures';
import demandes from '@/routes/espace-client/demandes';
import offres from '@/routes/espace-client/offres';
import { STATUT_CHIP, STATUT_LABEL } from '@/lib/demande-statut';
import RepartitionDonut from '@/components/espaces/repartition-donut';

type Offre = {
    id: string;
    titre: string;
    statut: string;
    dateLimite: string | null;
    candidaturesCount: number;
};

type Props = {
    stats: {
        offresActives: number;
        candidaturesRecues: number;
        placementsRealises: number;
        tauxConversion: number;
    };
    repartition: {
        enEtude: number;
        retenues: number;
        refusees: number;
    };
    offres: Offre[];
    candidaturesASigner: number;
};

function formatDateCourte(dateIso: string | null): string {
    if (!dateIso) {
        return '—';
    }
    const date = new Date(`${dateIso}T00:00:00`);
    return new Intl.DateTimeFormat('fr-FR', {
        day: '2-digit',
        month: 'long',
        year: 'numeric',
    }).format(date);
}

export default function EntrepriseDashboard({
    stats,
    repartition,
    offres: offresList,
    candidaturesASigner,
}: Props) {
    const candidaturesATraiter = repartition.enEtude;

    return (
        <>
            <Head title="Tableau de bord" />

            <div
                className="espace-hero"
                style={{
                    backgroundImage:
                        "url('/images/photos/presentation-data-review.jpg')",
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
                        <h1>Tableau de bord</h1>
                        <p>
                            Publiez une offre, consultez les profils, suivez vos
                            placements.
                        </p>
                    </div>
                    <Link href={offres.create()} className="btn-primary">
                        <Plus size={16} />
                        Publier une offre
                    </Link>
                </div>
            </div>

            <div
                className="grid-4 espace-stats-grid"
                style={{ marginBottom: 28 }}
            >
                <div className="card espace-stat-card" style={{ padding: 22 }}>
                    <span
                        className="espace-stat-icon"
                        style={{
                            background: 'var(--dag-blue-light)',
                            color: 'var(--dag-blue)',
                        }}
                    >
                        <Briefcase size={18} />
                    </span>
                    <strong
                        style={{
                            display: 'block',
                            fontSize: 24,
                            fontWeight: 800,
                        }}
                    >
                        {stats.offresActives}
                    </strong>
                    <span className="muted" style={{ fontSize: 12.5 }}>
                        Offres actives
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
                        <Users size={18} />
                    </span>
                    <strong
                        style={{
                            display: 'block',
                            fontSize: 24,
                            fontWeight: 800,
                        }}
                    >
                        {stats.candidaturesRecues}
                    </strong>
                    <span className="muted" style={{ fontSize: 12.5 }}>
                        Candidatures reçues
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
                        <CheckCircle2 size={18} />
                    </span>
                    <strong
                        style={{
                            display: 'block',
                            fontSize: 24,
                            fontWeight: 800,
                        }}
                    >
                        {stats.placementsRealises}
                    </strong>
                    <span className="muted" style={{ fontSize: 12.5 }}>
                        Placements réalisés
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
                        <TrendingUp size={18} />
                    </span>
                    <strong
                        style={{
                            display: 'block',
                            fontSize: 24,
                            fontWeight: 800,
                        }}
                    >
                        {stats.tauxConversion}%
                    </strong>
                    <span className="muted" style={{ fontSize: 12.5 }}>
                        Taux de conversion
                    </span>
                </div>
            </div>

            <div className="card" style={{ padding: 24, marginBottom: 28 }}>
                <p style={{ fontSize: 14, fontWeight: 700, marginBottom: 16 }}>
                    Actions rapides
                </p>
                <div style={{ display: 'flex', gap: 12, flexWrap: 'wrap' }}>
                    <Link href={offres.create()} className="espace-action-btn">
                        <Plus size={16} />
                        Publier une offre
                    </Link>
                    <Link
                        href={candidatures.index()}
                        className="espace-action-btn"
                    >
                        <Users size={16} />
                        Candidatures à traiter
                        {candidaturesATraiter > 0 && (
                            <span className="espace-action-badge">
                                {candidaturesATraiter}
                            </span>
                        )}
                    </Link>
                    <Link
                        href={espaceClient.contrats()}
                        className="espace-action-btn"
                    >
                        <FileSignature size={16} />
                        Signer une convention
                        {candidaturesASigner > 0 && (
                            <span className="espace-action-badge">
                                {candidaturesASigner}
                            </span>
                        )}
                    </Link>
                    <Link
                        href={demandes.create()}
                        className="espace-action-btn"
                    >
                        <UserCheck size={16} />
                        Demander un service DAG
                    </Link>
                </div>
            </div>

            <div className="grid-3">
                <div style={{ gridColumn: 'span 2' }}>
                    <h2
                        style={{
                            fontSize: 17,
                            color: 'var(--dag-navy)',
                            marginBottom: 16,
                        }}
                    >
                        Mes offres
                    </h2>

                    {offresList.length === 0 ? (
                        <div
                            className="card"
                            style={{ padding: 32, textAlign: 'center' }}
                        >
                            <p
                                className="muted"
                                style={{ fontSize: 13.5, marginBottom: 16 }}
                            >
                                Vous n'avez pas encore publié d'offre.
                            </p>
                            <Link
                                href={offres.create()}
                                className="btn-primary"
                            >
                                Publier ma première offre
                            </Link>
                        </div>
                    ) : (
                        <div
                            style={{
                                display: 'flex',
                                flexDirection: 'column',
                                gap: 14,
                            }}
                        >
                            {offresList.map((offre) => (
                                <div
                                    key={offre.id}
                                    className="card"
                                    style={{ padding: 20 }}
                                >
                                    <div
                                        style={{
                                            display: 'flex',
                                            justifyContent: 'space-between',
                                            alignItems: 'center',
                                            gap: 12,
                                            flexWrap: 'wrap',
                                        }}
                                    >
                                        <div
                                            style={{
                                                display: 'flex',
                                                alignItems: 'center',
                                                gap: 10,
                                            }}
                                        >
                                            <p
                                                style={{
                                                    fontWeight: 700,
                                                    fontSize: 14.5,
                                                }}
                                            >
                                                {offre.titre}
                                            </p>
                                            <span
                                                className={`chip ${STATUT_CHIP[offre.statut] ?? 'chip-gray'}`}
                                            >
                                                {offre.statut === 'PUBLIEE'
                                                    ? 'Active'
                                                    : (STATUT_LABEL[
                                                          offre.statut
                                                      ] ?? offre.statut)}
                                            </span>
                                        </div>
                                        <Link
                                            href={offres.show(offre.id)}
                                            className="btn-outline"
                                        >
                                            Voir les candidatures
                                        </Link>
                                    </div>
                                    <p
                                        className="muted"
                                        style={{ fontSize: 12.5, marginTop: 6 }}
                                    >
                                        {offre.dateLimite &&
                                            `Date limite : ${formatDateCourte(offre.dateLimite)} · `}
                                        {offre.candidaturesCount} candidature
                                        {offre.candidaturesCount > 1 ? 's' : ''}
                                    </p>
                                </div>
                            ))}
                        </div>
                    )}
                </div>

                <div className="card" style={{ padding: 24 }}>
                    <p
                        style={{
                            fontSize: 14,
                            fontWeight: 700,
                            marginBottom: 16,
                        }}
                    >
                        Répartition des candidatures
                    </p>
                    <RepartitionDonut
                        segments={[
                            {
                                label: 'En étude',
                                value: repartition.enEtude,
                                color: 'var(--dag-blue)',
                            },
                            {
                                label: 'Retenues',
                                value: repartition.retenues,
                                color: 'var(--dag-green)',
                            },
                            {
                                label: 'Refusées',
                                value: repartition.refusees,
                                color: 'var(--dag-red)',
                            },
                        ]}
                    />
                </div>
            </div>
        </>
    );
}
