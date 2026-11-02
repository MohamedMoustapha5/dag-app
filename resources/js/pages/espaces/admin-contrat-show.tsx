import { Head, Link, useForm } from '@inertiajs/react';
import { STATUT_CHIP, STATUT_LABEL } from '@/lib/demande-statut';
import contratsAdmin from '@/routes/espace-admin/contrats';

type Props = {
    peutModifier: boolean;
    contrat: {
        id: string;
        reference: string;
        objet: string;
        type: string;
        statut: string;
        montant: string | null;
        contenu: string | null;
        codeVerification: string;
        signataireNom: string | null;
        dateSignature: string | null;
        signatureContestee: boolean;
    };
};

export default function AdminContratShow({ peutModifier, contrat }: Props) {
    const form = useForm({ statut: contrat.statut });

    const submit = (event: React.FormEvent) => {
        event.preventDefault();
        form.patch(contratsAdmin.update(contrat.id).url, {
            preserveScroll: true,
        });
    };

    return (
        <>
            <Head title={`Contrat ${contrat.reference} — Administration`} />

            <Link
                href={contratsAdmin.index()}
                className="link-action"
                style={{ marginBottom: 16, display: 'inline-block' }}
            >
                ← Tous les contrats
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
                        {contrat.objet}
                    </h1>
                    <span className="muted" style={{ fontSize: 13 }}>
                        {contrat.reference} · Code de vérification :{' '}
                        {contrat.codeVerification}
                    </span>
                </div>
                <span
                    className={`chip ${STATUT_CHIP[contrat.statut] ?? 'chip-gray'}`}
                    style={{ fontSize: 13, padding: '8px 16px' }}
                >
                    {STATUT_LABEL[contrat.statut] ?? contrat.statut}
                </span>
            </div>

            {contrat.signatureContestee && (
                <div
                    className="card"
                    style={{
                        padding: 20,
                        marginBottom: 24,
                        borderColor: 'var(--dag-red)',
                    }}
                >
                    <p style={{ color: 'var(--dag-red)', fontWeight: 600 }}>
                        La signature de ce contrat a été contestée.
                    </p>
                </div>
            )}

            <div className="card" style={{ padding: 24, marginBottom: 24 }}>
                <h2 style={{ fontSize: 15, marginBottom: 12 }}>Signature</h2>
                {contrat.dateSignature ? (
                    <p className="muted" style={{ fontSize: 13.5 }}>
                        Signé par {contrat.signataireNom ?? '—'} le{' '}
                        {contrat.dateSignature}.
                    </p>
                ) : (
                    <p className="muted" style={{ fontSize: 13.5 }}>
                        Pas encore signé.
                    </p>
                )}
                {contrat.montant && (
                    <p style={{ marginTop: 12, fontSize: 13.5 }}>
                        Montant : <strong>{contrat.montant} FCFA</strong>
                    </p>
                )}
            </div>

            {contrat.contenu && (
                <div className="card" style={{ padding: 24, marginBottom: 24 }}>
                    <h2 style={{ fontSize: 15, marginBottom: 8 }}>Contenu</h2>
                    <p
                        className="muted"
                        style={{
                            fontSize: 13.5,
                            lineHeight: 1.6,
                            whiteSpace: 'pre-line',
                        }}
                    >
                        {contrat.contenu}
                    </p>
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
                        Faire avancer le contrat
                    </p>
                    <div style={{ maxWidth: 280 }}>
                        <label htmlFor="statut">Statut</label>
                        <select
                            id="statut"
                            value={form.data.statut}
                            onChange={(event) =>
                                form.setData('statut', event.target.value)
                            }
                        >
                            <option value="BROUILLON">Brouillon</option>
                            <option value="ENVOYE">Envoyé</option>
                            <option value="SIGNE">Signé</option>
                            <option value="ACTIF">Actif</option>
                            <option value="TERMINE">Terminé</option>
                            <option value="REFUSE">Refusé</option>
                            <option value="RESILIE">Résilié</option>
                        </select>
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
