import { Head, Link } from '@inertiajs/react';
import { Award, Tag, Users } from 'lucide-react';
import attestationsAdmin from '@/routes/espace-admin/attestations';
import equipeAdmin from '@/routes/espace-admin/equipe';
import grilleTarifaireAdmin from '@/routes/espace-admin/grille-tarifaire';

type Props = {
    membre: {
        role: string;
        roleLabel: string;
    };
    droits: Record<string, string>;
};

const DONNEE_LABELS: Record<string, string> = {
    CATALOGUE: 'Catalogue de services',
    FORMATIONS: 'Formations & sessions',
    DEMANDES_CLIENTS: 'Demandes clients',
    ABONNEMENTS: 'Abonnements',
    FACTURES_PAIEMENTS: 'Factures & paiements',
    CONTRATS_CLAUSES: 'Contrats & clauses',
    DOSSIERS_APPRENANTS: 'Dossiers apprenants',
    ENTREPRISES_OFFRES: 'Entreprises partenaires & offres',
    STATISTIQUES: 'Statistiques',
};

const NIVEAU_CHIP: Record<string, string> = {
    TOTAL: 'chip-green',
    GESTION: 'chip-blue',
    VALIDATION: 'chip-orange',
    LECTURE: 'chip-gray',
    AUCUN: 'chip-red',
};

const NIVEAU_LABEL: Record<string, string> = {
    TOTAL: 'Total',
    GESTION: 'Gestion',
    VALIDATION: 'Validation',
    LECTURE: 'Lecture',
    AUCUN: 'Aucun',
};

export default function AdminDashboard({ membre, droits }: Props) {
    return (
        <>
            <Head title="Tableau de bord — Administration" />

            <div
                className="espace-hero"
                style={{
                    backgroundImage:
                        "url('/images/photos/office-glass-dubai.jpg')",
                }}
            >
                <div className="espace-hero-overlay" />
                <div className="espace-hero-content">
                    <h1>Espace administrateur DAG</h1>
                    <p>Connecté en tant que {membre.roleLabel}.</p>
                </div>
            </div>

            <div className="card" style={{ padding: 24, marginBottom: 28 }}>
                <p style={{ fontSize: 15, fontWeight: 700, marginBottom: 16 }}>
                    Vos droits d'accès
                </p>
                <div className="data-table-wrap">
                    <table className="data-table">
                        <thead>
                            <tr>
                                <th>Domaine</th>
                                <th>Niveau</th>
                            </tr>
                        </thead>
                        <tbody>
                            {Object.entries(droits).map(([donnee, niveau]) => (
                                <tr key={donnee}>
                                    <td style={{ fontWeight: 600 }}>
                                        {DONNEE_LABELS[donnee] ?? donnee}
                                    </td>
                                    <td>
                                        <span
                                            className={`chip ${NIVEAU_CHIP[niveau] ?? 'chip-gray'}`}
                                        >
                                            {NIVEAU_LABEL[niveau] ?? niveau}
                                        </span>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>

            <div className="grid-3">
                {droits.CATALOGUE !== 'AUCUN' && (
                    <Link
                        href={grilleTarifaireAdmin.index()}
                        className="card"
                        style={{ padding: 24 }}
                    >
                        <span
                            className="espace-stat-icon"
                            style={{
                                background: 'var(--dag-blue-light)',
                                color: 'var(--dag-blue)',
                            }}
                        >
                            <Tag size={18} />
                        </span>
                        <p
                            style={{
                                fontSize: 14.5,
                                fontWeight: 700,
                                marginTop: 14,
                                color: 'var(--dag-navy)',
                            }}
                        >
                            Grille tarifaire
                        </p>
                        <p className="muted" style={{ fontSize: 12.5 }}>
                            Gérer les prestations et tarifs publiés.
                        </p>
                    </Link>
                )}
                {droits.DOSSIERS_APPRENANTS !== 'AUCUN' && (
                    <Link
                        href={attestationsAdmin.index()}
                        className="card"
                        style={{ padding: 24 }}
                    >
                        <span
                            className="espace-stat-icon"
                            style={{
                                background: 'var(--dag-green-bg)',
                                color: 'var(--dag-green)',
                            }}
                        >
                            <Award size={18} />
                        </span>
                        <p
                            style={{
                                fontSize: 14.5,
                                fontWeight: 700,
                                marginTop: 14,
                                color: 'var(--dag-navy)',
                            }}
                        >
                            Attestations
                        </p>
                        <p className="muted" style={{ fontSize: 12.5 }}>
                            Valider les formations terminées.
                        </p>
                    </Link>
                )}
                {membre.role === 'PROMOTRICE' && (
                    <Link
                        href={equipeAdmin.index()}
                        className="card"
                        style={{ padding: 24 }}
                    >
                        <span
                            className="espace-stat-icon"
                            style={{
                                background: '#f1f2f6',
                                color: 'var(--dag-text-muted)',
                            }}
                        >
                            <Users size={18} />
                        </span>
                        <p
                            style={{
                                fontSize: 14.5,
                                fontWeight: 700,
                                marginTop: 14,
                                color: 'var(--dag-navy)',
                            }}
                        >
                            Équipe DAG
                        </p>
                        <p className="muted" style={{ fontSize: 12.5 }}>
                            Créer et gérer les comptes de l'équipe.
                        </p>
                    </Link>
                )}
            </div>
        </>
    );
}
