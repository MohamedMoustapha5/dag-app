import { Head, Link, useForm } from '@inertiajs/react';
import { STATUT_CHIP, STATUT_LABEL } from '@/lib/demande-statut';
import demandesAdmin from '@/routes/espace-admin/demandes';

type Props = {
    peutModifier: boolean;
    demande: {
        id: string;
        reference: string;
        client: string;
        clientTelephone: string | null;
        objet: string;
        description: string;
        statut: string;
        montantNegocie: string | null;
        createdAt: string;
        contrat: { reference: string; statut: string } | null;
        facture: { reference: string; statut: string } | null;
    };
    suivi: {
        etapes: string[];
        etapeActuelle: number | null;
        refusee: boolean;
    };
};

export default function AdminDemandeShow({
    peutModifier,
    demande,
    suivi,
}: Props) {
    const form = useForm({
        statut: demande.statut,
        montant_negocie: demande.montantNegocie ?? '',
    });

    const submit = (event: React.FormEvent) => {
        event.preventDefault();
        form.patch(demandesAdmin.update(demande.id).url, {
            preserveScroll: true,
        });
    };

    return (
        <>
            <Head title={`Demande ${demande.reference} — Administration`} />

            <Link
                href={demandesAdmin.index()}
                className="link-action"
                style={{ marginBottom: 16, display: 'inline-block' }}
            >
                ← Toutes les demandes
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
                        {demande.objet}
                    </h1>
                    <span className="muted" style={{ fontSize: 13 }}>
                        {demande.reference} · {demande.client}
                    </span>
                </div>
                <span
                    className={`chip ${STATUT_CHIP[demande.statut] ?? 'chip-gray'}`}
                    style={{ fontSize: 13, padding: '8px 16px' }}
                >
                    {STATUT_LABEL[demande.statut] ?? demande.statut}
                </span>
            </div>

            {!suivi.refusee && (
                <div
                    className="process-track-card"
                    style={{ marginBottom: 32 }}
                >
                    {suivi.etapes.map((etape, index) => (
                        <div
                            key={etape}
                            style={{ display: 'flex', alignItems: 'center' }}
                        >
                            <div className="process-step">
                                <span
                                    className={`process-step-dot${suivi.etapeActuelle !== null && index <= suivi.etapeActuelle ? ' done' : ''}`}
                                >
                                    {index + 1}
                                </span>
                                <span className="process-step-label">
                                    {STATUT_LABEL[etape] ?? etape}
                                </span>
                            </div>
                            {index < suivi.etapes.length - 1 && (
                                <span className="process-step-arrow">→</span>
                            )}
                        </div>
                    ))}
                </div>
            )}

            <div className="card" style={{ padding: 24, marginBottom: 24 }}>
                <h2 style={{ fontSize: 15, marginBottom: 8 }}>Description</h2>
                <p
                    className="muted"
                    style={{ fontSize: 13.5, lineHeight: 1.6 }}
                >
                    {demande.description}
                </p>
                <p className="muted" style={{ fontSize: 12.5, marginTop: 12 }}>
                    Déposée le {demande.createdAt}
                    {demande.clientTelephone
                        ? ` · ${demande.clientTelephone}`
                        : ''}
                </p>
            </div>

            {(demande.contrat || demande.facture) && (
                <div className="grid-3" style={{ marginBottom: 24 }}>
                    {demande.contrat && (
                        <div className="card" style={{ padding: 20 }}>
                            <p className="muted" style={{ fontSize: 12.5 }}>
                                Contrat
                            </p>
                            <p style={{ fontWeight: 600, marginBottom: 6 }}>
                                {demande.contrat.reference}
                            </p>
                            <span
                                className={`chip ${STATUT_CHIP[demande.contrat.statut] ?? 'chip-gray'}`}
                            >
                                {STATUT_LABEL[demande.contrat.statut] ??
                                    demande.contrat.statut}
                            </span>
                        </div>
                    )}
                    {demande.facture && (
                        <div className="card" style={{ padding: 20 }}>
                            <p className="muted" style={{ fontSize: 12.5 }}>
                                Facture
                            </p>
                            <p style={{ fontWeight: 600, marginBottom: 6 }}>
                                {demande.facture.reference}
                            </p>
                            <span
                                className={`chip ${STATUT_CHIP[demande.facture.statut] ?? 'chip-gray'}`}
                            >
                                {STATUT_LABEL[demande.facture.statut] ??
                                    demande.facture.statut}
                            </span>
                        </div>
                    )}
                </div>
            )}

            {peutModifier && (
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
                        Faire avancer la demande
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
                            <label htmlFor="statut">Statut</label>
                            <select
                                id="statut"
                                value={form.data.statut}
                                onChange={(event) =>
                                    form.setData('statut', event.target.value)
                                }
                            >
                                {suivi.etapes.map((etape) => (
                                    <option key={etape} value={etape}>
                                        {STATUT_LABEL[etape] ?? etape}
                                    </option>
                                ))}
                                <option value="REFUSEE">Refusée</option>
                            </select>
                        </div>
                        <div>
                            <label htmlFor="montant_negocie">
                                Montant négocié (FCFA)
                            </label>
                            <input
                                id="montant_negocie"
                                type="number"
                                min={0}
                                value={form.data.montant_negocie}
                                onChange={(event) =>
                                    form.setData(
                                        'montant_negocie',
                                        event.target.value,
                                    )
                                }
                            />
                            {form.errors.montant_negocie && (
                                <p
                                    style={{
                                        color: 'var(--dag-red)',
                                        fontSize: 12,
                                        marginTop: 4,
                                    }}
                                >
                                    {form.errors.montant_negocie}
                                </p>
                            )}
                        </div>
                    </div>
                    <button
                        type="submit"
                        disabled={form.processing}
                        className="btn-primary"
                        style={{ alignSelf: 'flex-start' }}
                    >
                        {form.processing ? 'Mise à jour…' : 'Mettre à jour'}
                    </button>
                </form>
            )}
        </>
    );
}
