import { Head, Link } from '@inertiajs/react';
import {
    Check,
    Clock,
    FileSignature,
    FileText,
    Plus,
    Wallet,
} from 'lucide-react';
import { type EspaceClientInfo } from '@/layouts/espace-client-layout';
import demandes from '@/routes/espace-client/demandes';
import espaceClient from '@/routes/espace-client';

type Activite = {
    label: string;
    date: string;
    type: 'info' | 'succes' | 'attente' | 'erreur';
};

type Formule = {
    nom: string;
    montant: string;
    echeance: string | null;
    progression: number;
    servicesInclus: string[];
};

type Props = {
    client: EspaceClientInfo;
    stats: {
        demandesEnCours: number;
        facturesEnAttenteNombre: number;
        facturesEnAttenteMontant: string;
        prochaineEcheance: string | null;
        contratsASigner: number;
    };
    activiteRecente: Activite[];
    formule: Formule | null;
};

function formatDateCourte(dateIso: string | null): string {
    if (!dateIso) {
        return '—';
    }
    const date = new Date(`${dateIso}T00:00:00`);
    return new Intl.DateTimeFormat('fr-FR', {
        day: 'numeric',
        month: 'short',
    }).format(date);
}

function formatDateActivite(dateIso: string): string {
    const date = new Date(dateIso);
    const maintenant = new Date();
    const hier = new Date(maintenant);
    hier.setDate(hier.getDate() - 1);

    const heure = new Intl.DateTimeFormat('fr-FR', {
        hour: '2-digit',
        minute: '2-digit',
    }).format(date);

    if (date.toDateString() === maintenant.toDateString()) {
        return `Aujourd'hui, ${heure}`;
    }
    if (date.toDateString() === hier.toDateString()) {
        return `Hier, ${heure}`;
    }
    return new Intl.DateTimeFormat('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
    }).format(date);
}

const ACTIVITE_ICON_STYLE: Record<
    Activite['type'],
    { bg: string; color: string }
> = {
    info: { bg: 'var(--dag-blue-light)', color: 'var(--dag-blue)' },
    succes: { bg: 'var(--dag-green-bg)', color: 'var(--dag-green)' },
    attente: { bg: 'var(--dag-orange-bg)', color: 'var(--dag-orange-ink)' },
    erreur: { bg: 'var(--dag-red-bg)', color: 'var(--dag-red)' },
};

export default function ClientDashboard({
    client,
    stats,
    activiteRecente,
    formule,
}: Props) {
    return (
        <>
            <Head title="Espace client" />

            <div
                className="espace-hero"
                style={{
                    backgroundImage:
                        "url('/images/photos/team-meeting-laptop.jpg')",
                }}
            >
                <div className="espace-hero-overlay" />
                <div className="espace-hero-content">
                    <h1>Bonjour {client.nom.split(' ')[0]} 👋</h1>
                    <p>Voici un résumé de votre compte DAG.</p>
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
                        <FileText size={18} />
                    </span>
                    <strong
                        style={{
                            display: 'block',
                            fontSize: 24,
                            fontWeight: 800,
                        }}
                    >
                        {stats.demandesEnCours}
                    </strong>
                    <span className="muted" style={{ fontSize: 12.5 }}>
                        Demandes en cours
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
                        <Wallet size={18} />
                    </span>
                    <strong
                        style={{
                            display: 'block',
                            fontSize: 24,
                            fontWeight: 800,
                        }}
                    >
                        {stats.facturesEnAttenteNombre}
                    </strong>
                    <span className="muted" style={{ fontSize: 12.5 }}>
                        Factures en attente
                    </span>
                    {stats.facturesEnAttenteNombre > 0 && (
                        <p
                            style={{
                                fontSize: 12,
                                fontWeight: 700,
                                color: 'var(--dag-orange-ink)',
                                marginTop: 4,
                            }}
                        >
                            {stats.facturesEnAttenteMontant} FCFA à régler
                        </p>
                    )}
                </div>

                <div className="card espace-stat-card" style={{ padding: 22 }}>
                    <span
                        className="espace-stat-icon"
                        style={{
                            background: '#f1f2f6',
                            color: 'var(--dag-text-muted)',
                        }}
                    >
                        <Clock size={18} />
                    </span>
                    <strong
                        style={{
                            display: 'block',
                            fontSize: 24,
                            fontWeight: 800,
                        }}
                    >
                        {formatDateCourte(stats.prochaineEcheance)}
                    </strong>
                    <span className="muted" style={{ fontSize: 12.5 }}>
                        Prochaine échéance
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
                        <FileSignature size={18} />
                    </span>
                    <strong
                        style={{
                            display: 'block',
                            fontSize: 24,
                            fontWeight: 800,
                        }}
                    >
                        {stats.contratsASigner}
                    </strong>
                    <span className="muted" style={{ fontSize: 12.5 }}>
                        Contrats à signer
                    </span>
                </div>
            </div>

            <div className="card" style={{ padding: 24, marginBottom: 28 }}>
                <p style={{ fontSize: 14, fontWeight: 700, marginBottom: 16 }}>
                    Actions rapides
                </p>
                <div style={{ display: 'flex', gap: 12, flexWrap: 'wrap' }}>
                    <Link
                        href={demandes.create()}
                        className="espace-action-btn"
                    >
                        <Plus size={16} />
                        Nouvelle demande
                    </Link>
                    <span
                        className="espace-action-btn inert"
                        title="Disponible prochainement"
                    >
                        <Wallet size={16} />
                        Payer ma facture
                        {stats.facturesEnAttenteNombre > 0 && (
                            <span className="espace-action-badge">
                                {stats.facturesEnAttenteNombre}
                            </span>
                        )}
                    </span>
                    <span
                        className="espace-action-btn inert"
                        title="Disponible prochainement"
                    >
                        <FileSignature size={16} />
                        Importer ma signature
                    </span>
                    <Link
                        href={espaceClient.abonnements()}
                        className="espace-action-btn"
                    >
                        <FileText size={16} />
                        Gérer mon abonnement
                    </Link>
                </div>
            </div>

            <div className="grid-3">
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
                        Activité récente
                    </p>
                    {activiteRecente.length === 0 ? (
                        <p className="muted" style={{ fontSize: 13 }}>
                            Aucune activité pour le moment.
                        </p>
                    ) : (
                        <div>
                            {activiteRecente.map((activite, index) => (
                                <div
                                    className="espace-timeline-item"
                                    key={`${activite.label}-${index}`}
                                >
                                    <span
                                        className="espace-timeline-icon"
                                        style={{
                                            background:
                                                ACTIVITE_ICON_STYLE[
                                                    activite.type
                                                ].bg,
                                            color: ACTIVITE_ICON_STYLE[
                                                activite.type
                                            ].color,
                                        }}
                                    >
                                        {activite.type === 'succes' ? (
                                            <Check size={14} />
                                        ) : (
                                            <FileText size={14} />
                                        )}
                                    </span>
                                    <div>
                                        <p className="espace-timeline-label">
                                            {activite.label}
                                        </p>
                                        <p className="espace-timeline-date">
                                            {formatDateActivite(activite.date)}
                                        </p>
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}
                </div>

                <div className="card" style={{ padding: 24 }}>
                    {formule ? (
                        <>
                            <p style={{ fontSize: 15, fontWeight: 700 }}>
                                Formule {formule.nom}
                            </p>
                            <strong
                                style={{
                                    display: 'block',
                                    fontSize: 26,
                                    fontWeight: 800,
                                    color: 'var(--dag-navy)',
                                    marginTop: 8,
                                }}
                            >
                                {formule.montant}
                            </strong>
                            <span className="muted" style={{ fontSize: 12 }}>
                                FCFA / mois
                            </span>

                            <div style={{ marginTop: 18 }}>
                                <div
                                    className="muted"
                                    style={{
                                        display: 'flex',
                                        justifyContent: 'space-between',
                                        fontSize: 12,
                                    }}
                                >
                                    <span>Cycle en cours</span>
                                    <span>
                                        {formatDateCourte(formule.echeance)}
                                    </span>
                                </div>
                                <div className="espace-formule-progress">
                                    <div
                                        className="espace-formule-progress-bar"
                                        style={{
                                            width: `${formule.progression}%`,
                                        }}
                                    />
                                </div>
                            </div>

                            <div style={{ marginTop: 14 }}>
                                {formule.servicesInclus.map((service) => (
                                    <span
                                        key={service}
                                        className="espace-formule-check"
                                    >
                                        <Check
                                            size={14}
                                            style={{
                                                color: 'var(--dag-green)',
                                            }}
                                        />
                                        {service}
                                    </span>
                                ))}
                            </div>

                            <Link
                                href={espaceClient.abonnements()}
                                className="btn-outline btn-block"
                                style={{ marginTop: 16 }}
                            >
                                Gérer mon abonnement
                            </Link>
                        </>
                    ) : (
                        <>
                            <p
                                style={{
                                    fontSize: 14,
                                    fontWeight: 700,
                                    marginBottom: 8,
                                }}
                            >
                                Aucun abonnement actif
                            </p>
                            <p
                                className="muted"
                                style={{ fontSize: 13, marginBottom: 16 }}
                            >
                                Découvrez nos formules pour un accompagnement
                                continu.
                            </p>
                            <Link
                                href={espaceClient.services()}
                                className="btn-primary btn-block"
                            >
                                Voir les formules
                            </Link>
                        </>
                    )}
                </div>
            </div>
        </>
    );
}
