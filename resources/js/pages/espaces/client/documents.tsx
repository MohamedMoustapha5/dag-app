import { Head } from '@inertiajs/react';
import { Download } from 'lucide-react';
import EspaceClientLayout, {
    type EspaceClientInfo,
} from '@/layouts/espace-client-layout';
import { STATUT_CHIP, STATUT_LABEL } from '@/lib/demande-statut';

type Contrat = {
    id: string;
    reference: string;
    objet: string;
    statut: string;
    fichierPdf: string | null;
};

type Facture = {
    id: string;
    reference: string;
    montant: string;
    statut: string;
};

type Props = {
    client: EspaceClientInfo;
    contrats: Contrat[];
    factures: Facture[];
};

export default function Documents({ client, contrats, factures }: Props) {
    return (
        <EspaceClientLayout client={client}>
            <Head title="Mes documents" />

            <div
                className="espace-hero"
                style={{
                    backgroundImage:
                        "url('/images/photos/office-glass-dubai.jpg')",
                }}
            >
                <div className="espace-hero-overlay" />
                <div className="espace-hero-content">
                    <h1>Documents</h1>
                    <p>
                        Tous vos contrats, factures et attestations, réunis au
                        même endroit.
                    </p>
                </div>
            </div>

            <h2 style={{ fontSize: 15, marginBottom: 12 }}>Contrats</h2>
            <div className="data-table-wrap" style={{ marginBottom: 32 }}>
                {contrats.length === 0 ? (
                    <p className="data-table-empty">
                        Aucun contrat pour le moment.
                    </p>
                ) : (
                    <table className="data-table">
                        <thead>
                            <tr>
                                <th>Document</th>
                                <th>Référence</th>
                                <th>Statut</th>
                                <th />
                            </tr>
                        </thead>
                        <tbody>
                            {contrats.map((contrat) => (
                                <tr key={contrat.id}>
                                    <td style={{ fontWeight: 600 }}>
                                        {contrat.objet}
                                    </td>
                                    <td className="muted">
                                        {contrat.reference}
                                    </td>
                                    <td>
                                        <span
                                            className={`chip ${STATUT_CHIP[contrat.statut] ?? 'chip-gray'}`}
                                        >
                                            {STATUT_LABEL[contrat.statut] ??
                                                contrat.statut}
                                        </span>
                                    </td>
                                    <td style={{ textAlign: 'right' }}>
                                        {contrat.fichierPdf && (
                                            <a
                                                href={contrat.fichierPdf}
                                                target="_blank"
                                                rel="noreferrer"
                                                className="link-action"
                                            >
                                                <Download size={14} /> Voir
                                            </a>
                                        )}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                )}
            </div>

            <h2 style={{ fontSize: 15, marginBottom: 12 }}>Factures</h2>
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
                                <th>Montant</th>
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
                                    <td>{facture.montant} FCFA</td>
                                    <td>
                                        <span
                                            className={`chip ${STATUT_CHIP[facture.statut] ?? 'chip-gray'}`}
                                        >
                                            {STATUT_LABEL[facture.statut] ??
                                                facture.statut}
                                        </span>
                                    </td>
                                    <td style={{ textAlign: 'right' }}>
                                        {facture.statut === 'EN_ATTENTE' && (
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
