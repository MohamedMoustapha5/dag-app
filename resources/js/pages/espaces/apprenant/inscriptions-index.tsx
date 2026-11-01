import { Head } from '@inertiajs/react';
import { Receipt } from 'lucide-react';
import { STATUT_CHIP, STATUT_LABEL } from '@/lib/demande-statut';

type Facture = {
    reference: string;
    montant: string;
    montantPaye: string;
    statut: string;
    echeance: string | null;
};

type Inscription = {
    id: string;
    formation: string;
    ville: string | null;
    mode: string | null;
    dateDebut: string | null;
    statut: string;
    montant: string | null;
    facture: Facture | null;
};

type Props = {
    inscriptions: Inscription[];
};

export default function ApprenantInscriptionsIndex({ inscriptions }: Props) {
    return (
        <>
            <Head title="Mes inscriptions" />

            <div
                className="espace-hero"
                style={{
                    backgroundImage: "url('/images/photos/lms-dashboard.jpg')",
                }}
            >
                <div className="espace-hero-overlay" />
                <div className="espace-hero-content">
                    <h1>Mes inscriptions</h1>
                    <p>Le suivi de vos formations et des factures associées.</p>
                </div>
            </div>

            {inscriptions.length === 0 ? (
                <p className="data-table-empty">
                    Aucune inscription pour le moment.{' '}
                </p>
            ) : (
                <div
                    style={{
                        display: 'flex',
                        flexDirection: 'column',
                        gap: 16,
                    }}
                >
                    {inscriptions.map((inscription) => (
                        <div
                            key={inscription.id}
                            className="card"
                            style={{ padding: 24 }}
                        >
                            <div
                                style={{
                                    display: 'flex',
                                    justifyContent: 'space-between',
                                    alignItems: 'flex-start',
                                    gap: 16,
                                    flexWrap: 'wrap',
                                }}
                            >
                                <div>
                                    <h3
                                        style={{
                                            fontSize: 16,
                                            color: 'var(--dag-navy)',
                                        }}
                                    >
                                        {inscription.formation}
                                    </h3>
                                    <div
                                        className="training-session-meta"
                                        style={{ marginTop: 8 }}
                                    >
                                        {inscription.ville && (
                                            <span>📍 {inscription.ville}</span>
                                        )}
                                        {inscription.mode && (
                                            <span>🖥️ {inscription.mode}</span>
                                        )}
                                        {inscription.dateDebut && (
                                            <span>
                                                🗓️ Du {inscription.dateDebut}
                                            </span>
                                        )}
                                        {inscription.montant && (
                                            <span>
                                                💰 {inscription.montant} FCFA
                                            </span>
                                        )}
                                    </div>
                                </div>
                                <span
                                    className={`chip ${STATUT_CHIP[inscription.statut] ?? 'chip-gray'}`}
                                >
                                    {STATUT_LABEL[inscription.statut] ??
                                        inscription.statut}
                                </span>
                            </div>

                            {inscription.facture && (
                                <div
                                    style={{
                                        marginTop: 18,
                                        paddingTop: 18,
                                        borderTop:
                                            '1px solid var(--dag-border)',
                                        display: 'flex',
                                        alignItems: 'center',
                                        justifyContent: 'space-between',
                                        gap: 16,
                                        flexWrap: 'wrap',
                                    }}
                                >
                                    <div
                                        style={{
                                            display: 'flex',
                                            alignItems: 'center',
                                            gap: 12,
                                        }}
                                    >
                                        <span
                                            className="espace-stat-icon"
                                            style={{
                                                background:
                                                    'var(--dag-blue-light)',
                                                color: 'var(--dag-blue)',
                                                margin: 0,
                                            }}
                                        >
                                            <Receipt size={16} />
                                        </span>
                                        <div>
                                            <p
                                                style={{
                                                    fontSize: 13.5,
                                                    fontWeight: 700,
                                                    color: 'var(--dag-navy)',
                                                }}
                                            >
                                                Facture{' '}
                                                {inscription.facture.reference}
                                            </p>
                                            <p
                                                className="muted"
                                                style={{ fontSize: 12 }}
                                            >
                                                {
                                                    inscription.facture
                                                        .montantPaye
                                                }{' '}
                                                / {inscription.facture.montant}{' '}
                                                FCFA réglés
                                                {inscription.facture.echeance
                                                    ? ` — échéance ${inscription.facture.echeance}`
                                                    : ''}
                                            </p>
                                        </div>
                                    </div>
                                    <div
                                        style={{
                                            display: 'flex',
                                            alignItems: 'center',
                                            gap: 12,
                                        }}
                                    >
                                        <span
                                            className={`chip ${STATUT_CHIP[inscription.facture.statut] ?? 'chip-gray'}`}
                                        >
                                            {STATUT_LABEL[
                                                inscription.facture.statut
                                            ] ?? inscription.facture.statut}
                                        </span>
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
                                    </div>
                                </div>
                            )}
                        </div>
                    ))}
                </div>
            )}
        </>
    );
}
