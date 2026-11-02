import { Head } from '@inertiajs/react';
import {
    Award,
    Briefcase,
    FileSignature,
    FileText,
    GraduationCap,
    Shield,
    Users,
} from 'lucide-react';
import { STATUT_CHIP, STATUT_LABEL } from '@/lib/demande-statut';

type Props = {
    activite: {
        apprenants: number;
        formationsPubliees: number;
        sessionsOuvertes: number;
        entreprisesActives: number;
        offresPubliees: number;
        abonnementsActifs: number;
        contratsActifs: number;
    };
    demandesParStatut: Record<string, number>;
    finances: {
        factureEnAttente: number;
        factureEncaissee: number;
        facturesImpayeesCount: number;
    };
};

export default function AdminStatistiques({
    activite,
    demandesParStatut,
    finances,
}: Props) {
    const formatMontant = (montant: number) =>
        new Intl.NumberFormat('fr-FR').format(montant);

    return (
        <>
            <Head title="Statistiques — Administration" />

            <div
                className="espace-hero"
                style={{
                    backgroundImage: "url('/images/photos/glass-charts.jpg')",
                }}
            >
                <div className="espace-hero-overlay" />
                <div className="espace-hero-content">
                    <h1>Statistiques</h1>
                    <p>Vue d'ensemble de l'activité DAG, en temps réel.</p>
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
                        <Users size={18} />
                    </span>
                    <strong
                        style={{
                            display: 'block',
                            fontSize: 24,
                            fontWeight: 800,
                        }}
                    >
                        {activite.apprenants}
                    </strong>
                    <span className="muted" style={{ fontSize: 12.5 }}>
                        Apprenants
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
                        <GraduationCap size={18} />
                    </span>
                    <strong
                        style={{
                            display: 'block',
                            fontSize: 24,
                            fontWeight: 800,
                        }}
                    >
                        {activite.formationsPubliees}
                    </strong>
                    <span className="muted" style={{ fontSize: 12.5 }}>
                        Formations publiées
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
                        <Award size={18} />
                    </span>
                    <strong
                        style={{
                            display: 'block',
                            fontSize: 24,
                            fontWeight: 800,
                        }}
                    >
                        {activite.sessionsOuvertes}
                    </strong>
                    <span className="muted" style={{ fontSize: 12.5 }}>
                        Sessions ouvertes
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
                        <Briefcase size={18} />
                    </span>
                    <strong
                        style={{
                            display: 'block',
                            fontSize: 24,
                            fontWeight: 800,
                        }}
                    >
                        {activite.entreprisesActives}
                    </strong>
                    <span className="muted" style={{ fontSize: 12.5 }}>
                        Entreprises partenaires actives
                    </span>
                </div>
                <div className="card espace-stat-card" style={{ padding: 22 }}>
                    <span
                        className="espace-stat-icon"
                        style={{
                            background: 'var(--dag-blue-light)',
                            color: 'var(--dag-blue)',
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
                        {activite.offresPubliees}
                    </strong>
                    <span className="muted" style={{ fontSize: 12.5 }}>
                        Offres d'emploi publiées
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
                        <Shield size={18} />
                    </span>
                    <strong
                        style={{
                            display: 'block',
                            fontSize: 24,
                            fontWeight: 800,
                        }}
                    >
                        {activite.abonnementsActifs}
                    </strong>
                    <span className="muted" style={{ fontSize: 12.5 }}>
                        Abonnements actifs
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
                        <FileSignature size={18} />
                    </span>
                    <strong
                        style={{
                            display: 'block',
                            fontSize: 24,
                            fontWeight: 800,
                        }}
                    >
                        {activite.contratsActifs}
                    </strong>
                    <span className="muted" style={{ fontSize: 12.5 }}>
                        Contrats actifs
                    </span>
                </div>
            </div>

            <div
                style={{
                    display: 'grid',
                    gridTemplateColumns: 'repeat(auto-fit, minmax(280px, 1fr))',
                    gap: 24,
                }}
            >
                <div className="card" style={{ padding: 24 }}>
                    <p
                        style={{
                            fontSize: 14.5,
                            fontWeight: 700,
                            marginBottom: 16,
                        }}
                    >
                        Demandes par statut
                    </p>
                    {Object.keys(demandesParStatut).length === 0 ? (
                        <p className="muted" style={{ fontSize: 13 }}>
                            Aucune demande pour le moment.
                        </p>
                    ) : (
                        <div
                            style={{
                                display: 'flex',
                                flexDirection: 'column',
                                gap: 10,
                            }}
                        >
                            {Object.entries(demandesParStatut).map(
                                ([statut, total]) => (
                                    <div
                                        key={statut}
                                        style={{
                                            display: 'flex',
                                            justifyContent: 'space-between',
                                            alignItems: 'center',
                                            fontSize: 13.5,
                                        }}
                                    >
                                        <span
                                            className={`chip ${STATUT_CHIP[statut] ?? 'chip-gray'}`}
                                        >
                                            {STATUT_LABEL[statut] ?? statut}
                                        </span>
                                        <strong>{total}</strong>
                                    </div>
                                ),
                            )}
                        </div>
                    )}
                </div>

                <div className="card" style={{ padding: 24 }}>
                    <p
                        style={{
                            fontSize: 14.5,
                            fontWeight: 700,
                            marginBottom: 16,
                        }}
                    >
                        Finances
                    </p>
                    <div
                        style={{
                            display: 'flex',
                            flexDirection: 'column',
                            gap: 14,
                        }}
                    >
                        <div>
                            <span className="muted" style={{ fontSize: 12 }}>
                                Encaissé (toutes factures)
                            </span>
                            <p style={{ fontSize: 18, fontWeight: 700 }}>
                                {formatMontant(finances.factureEncaissee)} FCFA
                            </p>
                        </div>
                        <div>
                            <span className="muted" style={{ fontSize: 12 }}>
                                En attente de paiement (
                                {finances.facturesImpayeesCount} facture
                                {finances.facturesImpayeesCount > 1 ? 's' : ''})
                            </span>
                            <p style={{ fontSize: 18, fontWeight: 700 }}>
                                {formatMontant(finances.factureEnAttente)} FCFA
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}
