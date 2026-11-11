import { Head, Link } from '@inertiajs/react';
import { useState } from 'react';
import PayerFactureModal, {
    type MoyenPaiement,
} from '@/components/espaces/payer-facture-modal';
import { STATUT_CHIP, STATUT_LABEL } from '@/lib/demande-statut';
import demandesRoutes from '@/routes/espace-client/demandes';
import facturesRoutes from '@/routes/espace-client/factures';

type Facture = {
    id: string;
    reference: string;
    objet: string;
    montant: string;
    moyenPaiement: string | null;
    statut: string;
    solde: string;
    paiementEnAttente: boolean;
    attendSignatureContrat: boolean;
    demandeId: string | null;
};

type Props = {
    stats: {
        regleCetteAnnee: string;
        enAttente: string;
        moyenPlusUtilise: string | null;
    };
    factures: Facture[];
    moyensPaiement: MoyenPaiement[];
};

export default function FacturesIndex({
    stats,
    factures,
    moyensPaiement,
}: Props) {
    const [aPayer, setAPayer] = useState<Facture | null>(null);

    return (
        <>
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

            <div
                className="grid-3 espace-stats-grid"
                style={{ marginBottom: 24 }}
            >
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
                                            facture.attendSignatureContrat &&
                                            facture.demandeId ? (
                                                <Link
                                                    href={demandesRoutes.show(
                                                        facture.demandeId,
                                                    )}
                                                    className="link-action"
                                                    style={{ fontSize: 12.5 }}
                                                >
                                                    Signer le contrat d'abord
                                                </Link>
                                            ) : facture.paiementEnAttente ? (
                                                <span
                                                    className="muted"
                                                    style={{ fontSize: 12 }}
                                                >
                                                    Paiement en attente de
                                                    confirmation
                                                </span>
                                            ) : (
                                                <button
                                                    type="button"
                                                    className="btn-primary"
                                                    style={{
                                                        padding: '6px 16px',
                                                        fontSize: 12.5,
                                                    }}
                                                    onClick={() =>
                                                        setAPayer(facture)
                                                    }
                                                >
                                                    Payer
                                                </button>
                                            )
                                        ) : facture.statut === 'PAYEE' ? (
                                            <a
                                                className="link-action"
                                                href={
                                                    facturesRoutes.pdf(
                                                        facture.id,
                                                    ).url
                                                }
                                            >
                                                PDF ↓
                                            </a>
                                        ) : null}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                )}
            </div>

            {aPayer && (
                <PayerFactureModal
                    facture={aPayer}
                    moyens={moyensPaiement}
                    onClose={() => setAPayer(null)}
                />
            )}
        </>
    );
}
