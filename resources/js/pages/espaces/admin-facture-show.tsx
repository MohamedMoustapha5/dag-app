import { Head, Link, useForm } from '@inertiajs/react';
import { STATUT_CHIP, STATUT_LABEL } from '@/lib/demande-statut';
import facturesAdmin from '@/routes/espace-admin/factures';

type Facture = {
    id: string;
    reference: string;
    objet: string;
    client: string;
    montant: string;
    montantPaye: string;
    statut: string;
};

type Paiement = {
    id: string;
    montant: string;
    moyen: string;
    referenceTransaction: string | null;
    statut: string;
    datePaiement: string | null;
};

const MOYEN_LABEL: Record<string, string> = {
    OM: 'Orange Money',
    MTN_MOMO: 'MTN Mobile Money',
    CARTE: 'Carte bancaire',
    ESPECES: 'Espèces',
    VIREMENT: 'Virement',
    MANUEL: 'Manuel',
};

type Props = {
    facture: Facture;
    paiements: Paiement[];
    peutCreer: boolean;
};

export default function AdminFactureShow({
    facture,
    paiements,
    peutCreer,
}: Props) {
    const resteAPayer = (
        Number(facture.montant) - Number(facture.montantPaye)
    ).toFixed(2);

    const form = useForm({
        montant: resteAPayer,
        moyen: 'MANUEL',
        reference_transaction: '',
    });

    const submit = (event: React.FormEvent) => {
        event.preventDefault();
        form.post(facturesAdmin.paiements.store(facture.id).url, {
            preserveScroll: true,
            onSuccess: () => form.reset(),
        });
    };

    return (
        <>
            <Head title={`Facture ${facture.reference} — Administration`} />

            <Link
                href={facturesAdmin.index()}
                className="link-action"
                style={{ marginBottom: 16, display: 'inline-block' }}
            >
                ← Toutes les factures
            </Link>

            <div
                style={{
                    display: 'flex',
                    justifyContent: 'space-between',
                    alignItems: 'center',
                    gap: 16,
                    flexWrap: 'wrap',
                    marginBottom: 24,
                }}
            >
                <div>
                    <h1 style={{ fontSize: 22, color: 'var(--dag-navy)' }}>
                        {facture.objet}
                    </h1>
                    <span className="muted" style={{ fontSize: 13 }}>
                        {facture.reference} · {facture.client}
                    </span>
                </div>
                <span
                    className={`chip ${STATUT_CHIP[facture.statut] ?? 'chip-gray'}`}
                    style={{ fontSize: 13, padding: '8px 16px' }}
                >
                    {STATUT_LABEL[facture.statut] ?? facture.statut}
                </span>
            </div>

            <div className="grid-3" style={{ marginBottom: 28 }}>
                <div className="card" style={{ padding: 20 }}>
                    <p className="muted" style={{ fontSize: 12.5 }}>
                        Montant total
                    </p>
                    <p style={{ fontSize: 18, fontWeight: 700 }}>
                        {facture.montant} FCFA
                    </p>
                </div>
                <div className="card" style={{ padding: 20 }}>
                    <p className="muted" style={{ fontSize: 12.5 }}>
                        Déjà payé
                    </p>
                    <p style={{ fontSize: 18, fontWeight: 700 }}>
                        {facture.montantPaye} FCFA
                    </p>
                </div>
                <div className="card" style={{ padding: 20 }}>
                    <p className="muted" style={{ fontSize: 12.5 }}>
                        Reste à payer
                    </p>
                    <p style={{ fontSize: 18, fontWeight: 700 }}>
                        {resteAPayer} FCFA
                    </p>
                </div>
            </div>

            <div className="card" style={{ padding: 24, marginBottom: 28 }}>
                <p
                    style={{
                        fontSize: 14.5,
                        fontWeight: 700,
                        marginBottom: 16,
                    }}
                >
                    Paiements ({paiements.length})
                </p>
                {paiements.length === 0 ? (
                    <p className="data-table-empty">
                        Aucun paiement enregistré.
                    </p>
                ) : (
                    <div className="data-table-wrap">
                        <table className="data-table">
                            <thead>
                                <tr>
                                    <th>Montant</th>
                                    <th>Moyen</th>
                                    <th>Référence</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                {paiements.map((paiement) => (
                                    <tr key={paiement.id}>
                                        <td style={{ fontWeight: 600 }}>
                                            {paiement.montant} FCFA
                                        </td>
                                        <td className="muted">
                                            {MOYEN_LABEL[paiement.moyen] ??
                                                paiement.moyen}
                                        </td>
                                        <td className="muted">
                                            {paiement.referenceTransaction ??
                                                '—'}
                                        </td>
                                        <td className="muted">
                                            {paiement.datePaiement}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </div>

            {peutCreer && facture.statut !== 'PAYEE' && (
                <form
                    onSubmit={submit}
                    className="card"
                    style={{
                        padding: 24,
                        display: 'flex',
                        flexDirection: 'column',
                        gap: 14,
                    }}
                >
                    <p style={{ fontSize: 14.5, fontWeight: 700 }}>
                        Enregistrer un paiement
                    </p>
                    <div
                        style={{
                            display: 'grid',
                            gridTemplateColumns:
                                'repeat(auto-fit, minmax(180px, 1fr))',
                            gap: 14,
                        }}
                    >
                        <div>
                            <label htmlFor="montant">Montant (FCFA)</label>
                            <input
                                id="montant"
                                type="number"
                                min={0.01}
                                step="0.01"
                                value={form.data.montant}
                                onChange={(event) =>
                                    form.setData('montant', event.target.value)
                                }
                            />
                            {form.errors.montant && (
                                <p
                                    style={{
                                        color: 'var(--dag-red)',
                                        fontSize: 12,
                                        marginTop: 4,
                                    }}
                                >
                                    {form.errors.montant}
                                </p>
                            )}
                        </div>
                        <div>
                            <label htmlFor="moyen">Moyen de paiement</label>
                            <select
                                id="moyen"
                                value={form.data.moyen}
                                onChange={(event) =>
                                    form.setData('moyen', event.target.value)
                                }
                            >
                                {Object.entries(MOYEN_LABEL).map(
                                    ([valeur, label]) => (
                                        <option key={valeur} value={valeur}>
                                            {label}
                                        </option>
                                    ),
                                )}
                            </select>
                        </div>
                        <div>
                            <label htmlFor="reference_transaction">
                                Référence (optionnel)
                            </label>
                            <input
                                id="reference_transaction"
                                value={form.data.reference_transaction}
                                onChange={(event) =>
                                    form.setData(
                                        'reference_transaction',
                                        event.target.value,
                                    )
                                }
                            />
                        </div>
                    </div>
                    <button
                        type="submit"
                        disabled={form.processing}
                        className="btn-primary"
                        style={{ alignSelf: 'flex-start' }}
                    >
                        {form.processing
                            ? 'Enregistrement…'
                            : 'Enregistrer le paiement'}
                    </button>
                </form>
            )}
        </>
    );
}
