import { Head } from '@inertiajs/react';
import EspaceClientLayout, {
    type EspaceClientInfo,
} from '@/layouts/espace-client-layout';
import { STATUT_CHIP, STATUT_LABEL } from '@/lib/demande-statut';

type Facture = {
    id: string;
    reference: string;
    objet: string;
    montant: string;
    moyenPaiement: string | null;
    statut: string;
};

type Props = {
    client: EspaceClientInfo;
    stats: {
        regleCetteAnnee: string;
        enAttente: string;
        moyenPlusUtilise: string | null;
    };
    factures: Facture[];
};

export default function FacturesIndex({ client, stats, factures }: Props) {
    return (
        <EspaceClientLayout client={client}>
            <Head title="Factures" />

            <div
                className="espace-hero"
                style={{
                    backgroundImage:
                        "url('/images/photos/payment-approved-phone.jpg')",
                }}
            >
                <div className="espace-hero-overlay" />
                <div className="espace-hero-content">
                    <h1>Factures</h1>
                    <p>
                        Reçu numérique automatique à chaque paiement confirmé.
                    </p>
                </div>
            </div>

            <div className="grid-3" style={{ marginBottom: 24 }}>
                <div className="card espace-stat-card" style={{ padding: 22 }}>
                    <p className="muted" style={{ fontSize: 12.5 }}>
                        Réglé cette année
                    </p>
                    <strong
                        style={{
                            display: 'block',
                            fontSize: 24,
                            fontWeight: 800,
                            color: 'var(--dag-navy)',
                        }}
                    >
                        {stats.regleCetteAnnee} FCFA
                    </strong>
                </div>
                <div className="card espace-stat-card" style={{ padding: 22 }}>
                    <p className="muted" style={{ fontSize: 12.5 }}>
                        En attente
                    </p>
                    <strong
                        style={{
                            display: 'block',
                            fontSize: 24,
                            fontWeight: 800,
                            color: 'var(--dag-orange-ink)',
                        }}
                    >
                        {stats.enAttente} FCFA
                    </strong>
                </div>
                <div className="card espace-stat-card" style={{ padding: 22 }}>
                    <p className="muted" style={{ fontSize: 12.5 }}>
                        Moyen le plus utilisé
                    </p>
                    <strong
                        style={{
                            display: 'block',
                            fontSize: 24,
                            fontWeight: 800,
                            color: 'var(--dag-navy)',
                        }}
                    >
                        {stats.moyenPlusUtilise ?? '—'}
                    </strong>
                </div>
            </div>

            <div className="data-table-wrap">
                {factures.length === 0 ? (
                    <p className="data-table-empty">
                        Aucune facture pour le moment.
                    </p>
                ) : (
                    <table className="data-table">
                        <thead>
                            <tr>
                                <th>Facture</th>
                                <th>Service</th>
                                <th>Montant</th>
                                <th>Moyen</th>
                                <th>Statut</th>
                                <th />
                            </tr>
                        </thead>
                        <tbody>
                            {factures.map((facture) => (
                                <tr key={facture.id}>
                                    <td style={{ fontWeight: 600 }}>
                                        {facture.reference}
                                    </td>
                                    <td className="muted">{facture.objet}</td>
                                    <td>{facture.montant} FCFA</td>
                                    <td className="muted">
                                        {facture.moyenPaiement ?? '—'}
                                    </td>
                                    <td>
                                        <span
                                            className={`chip ${STATUT_CHIP[facture.statut] ?? 'chip-gray'}`}
                                        >
                                            {STATUT_LABEL[facture.statut] ??
                                                facture.statut}
                                        </span>
                                    </td>
                                    <td style={{ textAlign: 'right' }}>
                                        {facture.statut === 'EN_ATTENTE' ||
                                        facture.statut ===
                                            'PARTIELLEMENT_PAYEE' ? (
                                            <span
                                                className="link-action"
                                                style={{
                                                    opacity: 0.5,
                                                    cursor: 'not-allowed',
                                                }}
                                                title="Paiement en ligne bientôt disponible"
                                            >
                                                Payer
                                            </span>
                                        ) : (
                                            <span
                                                className="link-action"
                                                style={{
                                                    opacity: 0.5,
                                                    cursor: 'not-allowed',
                                                }}
                                                title="Export PDF bientôt disponible"
                                            >
                                                PDF ↓
                                            </span>
                                        )}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                )}
            </div>
        </EspaceClientLayout>
    );
}
