import { Head, Link, useForm } from '@inertiajs/react';
import { useState } from 'react';
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

type DemandeAFacturer = {
    id: string;
    reference: string;
    objet: string;
    montantNegocie: string | null;
};

type Props = {
    factures: Facture[];
    demandesAFacturer: DemandeAFacturer[];
    peutCreer: boolean;
};

export default function AdminFactures({
    factures,
    demandesAFacturer,
    peutCreer,
}: Props) {
    const [demandeId, setDemandeId] = useState(demandesAFacturer[0]?.id ?? '');
    const demandeSelectionnee = demandesAFacturer.find(
        (demande) => demande.id === demandeId,
    );

    const form = useForm({
        demande_id: demandesAFacturer[0]?.id ?? '',
        montant: demandesAFacturer[0]?.montantNegocie ?? '',
    });

    const submit = (event: React.FormEvent) => {
        event.preventDefault();
        form.post(facturesAdmin.store().url);
    };

    return (
        <>
            <Head title="Factures & paiements — Administration" />

            <div
                className="espace-hero"
                style={{
                    backgroundImage:
                        "url('/images/photos/payment-approved-phone.jpg')",
                }}
            >
                <div className="espace-hero-overlay" />
                <div className="espace-hero-content">
                    <h1>Factures & paiements</h1>
                    <p>
                        Générez une facture pour une demande acceptée et suivez
                        les paiements reçus.
                    </p>
                </div>
            </div>

            {peutCreer && demandesAFacturer.length > 0 && (
                <form
                    onSubmit={submit}
                    className="card"
                    style={{
                        padding: 24,
                        marginBottom: 28,
                        display: 'flex',
                        flexDirection: 'column',
                        gap: 14,
                    }}
                >
                    <p style={{ fontSize: 14.5, fontWeight: 700 }}>
                        Générer une facture pour une demande
                    </p>
                    <div
                        style={{
                            display: 'grid',
                            gridTemplateColumns:
                                'repeat(auto-fit, minmax(220px, 1fr))',
                            gap: 14,
                        }}
                    >
                        <div>
                            <label htmlFor="demande_id">Demande</label>
                            <select
                                id="demande_id"
                                value={form.data.demande_id}
                                onChange={(event) => {
                                    const id = event.target.value;
                                    setDemandeId(id);
                                    form.setData('demande_id', id);
                                    const demande = demandesAFacturer.find(
                                        (item) => item.id === id,
                                    );
                                    form.setData(
                                        'montant',
                                        demande?.montantNegocie ?? '',
                                    );
                                }}
                            >
                                {demandesAFacturer.map((demande) => (
                                    <option key={demande.id} value={demande.id}>
                                        {demande.reference} — {demande.objet}
                                    </option>
                                ))}
                            </select>
                        </div>
                        <div>
                            <label htmlFor="montant">Montant (FCFA)</label>
                            <input
                                id="montant"
                                type="number"
                                min={0}
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
                    </div>
                    <button
                        type="submit"
                        disabled={form.processing || !demandeSelectionnee}
                        className="btn-primary"
                        style={{ alignSelf: 'flex-start' }}
                    >
                        {form.processing ? 'Création…' : 'Générer la facture'}
                    </button>
                </form>
            )}

            <div className="card" style={{ padding: 24 }}>
                <p
                    style={{
                        fontSize: 14.5,
                        fontWeight: 700,
                        marginBottom: 16,
                    }}
                >
                    Factures ({factures.length})
                </p>
                {factures.length === 0 ? (
                    <p className="data-table-empty">
                        Aucune facture pour le moment.
                    </p>
                ) : (
                    <div className="data-table-wrap">
                        <table className="data-table">
                            <thead>
                                <tr>
                                    <th>Référence</th>
                                    <th>Client</th>
                                    <th>Objet</th>
                                    <th>Montant</th>
                                    <th>Payé</th>
                                    <th>Statut</th>
                                </tr>
                            </thead>
                            <tbody>
                                {factures.map((facture) => (
                                    <tr key={facture.id}>
                                        <td>
                                            <Link
                                                href={facturesAdmin.show(
                                                    facture.id,
                                                )}
                                                className="link-action"
                                            >
                                                {facture.reference}
                                            </Link>
                                        </td>
                                        <td style={{ fontWeight: 600 }}>
                                            {facture.client}
                                        </td>
                                        <td className="muted">
                                            {facture.objet}
                                        </td>
                                        <td className="muted">
                                            {facture.montant} FCFA
                                        </td>
                                        <td className="muted">
                                            {facture.montantPaye} FCFA
                                        </td>
                                        <td>
                                            <span
                                                className={`chip ${STATUT_CHIP[facture.statut] ?? 'chip-gray'}`}
                                            >
                                                {STATUT_LABEL[facture.statut] ??
                                                    facture.statut}
                                            </span>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </div>
        </>
    );
}
