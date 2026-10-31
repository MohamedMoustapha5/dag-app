import { Head } from '@inertiajs/react';
import { STATUT_CHIP, STATUT_LABEL } from '@/lib/demande-statut';

type Paiement = {
    id: string;
    factureReference: string;
    montant: string;
    moyen: string | null;
    statut: string;
    datePaiement: string | null;
};

type Props = {
    paiements: Paiement[];
};

function formatDate(dateIso: string | null): string {
    if (!dateIso) {
        return '—';
    }
    return new Intl.DateTimeFormat('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
    }).format(new Date(dateIso));
}

export default function PaiementsIndex({ paiements }: Props) {
    return (
        <>
            <Head title="Mes paiements" />

            <h1
                style={{
                    fontSize: 22,
                    color: 'var(--dag-navy)',
                    marginBottom: 24,
                }}
            >
                Mes paiements
            </h1>

            <div className="data-table-wrap">
                {paiements.length === 0 ? (
                    <p className="data-table-empty">
                        Aucun paiement enregistré.
                    </p>
                ) : (
                    <table className="data-table">
                        <thead>
                            <tr>
                                <th>Facture</th>
                                <th>Date</th>
                                <th>Montant</th>
                                <th>Moyen</th>
                                <th>Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            {paiements.map((paiement) => (
                                <tr key={paiement.id}>
                                    <td style={{ fontWeight: 600 }}>
                                        {paiement.factureReference}
                                    </td>
                                    <td className="muted">
                                        {formatDate(paiement.datePaiement)}
                                    </td>
                                    <td>{paiement.montant} FCFA</td>
                                    <td className="muted">
                                        {paiement.moyen ?? '—'}
                                    </td>
                                    <td>
                                        <span
                                            className={`chip ${STATUT_CHIP[paiement.statut] ?? 'chip-gray'}`}
                                        >
                                            {STATUT_LABEL[paiement.statut] ??
                                                paiement.statut}
                                        </span>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                )}
            </div>
        </>
    );
}
