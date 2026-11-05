import { Head, Link, router, useForm } from '@inertiajs/react';
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
        statutsAccessibles: string[];
        montant: string | null;
        contenu: string | null;
        codeVerification: string;
        signataireNom: string | null;
        dateSignature: string | null;
        signatureContestee: boolean;
        estSigne: boolean;
    };
    peutTelecharger: boolean;
};

export default function AdminContratShow({
    peutModifier,
    peutTelecharger,
    contrat,
}: Props) {
    const form = useForm({ statut: contrat.statut });

    const envoyerAuClient = () => {
        router.patch(
            contratsAdmin.update(contrat.id).url,
            { statut: 'ENVOYE' },
            { preserveScroll: true },
        );
    };

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
                <div style={{ display: 'flex', gap: 12, alignItems: 'center' }}>
                    {peutModifier && contrat.statut === 'BROUILLON' && (
                        <button
                            type="button"
                            className="btn-primary"
                            style={{ padding: '8px 16px', fontSize: 13 }}
                            onClick={envoyerAuClient}
                        >
                            Envoyer au client pour signature
                        </button>
                    )}
                    {peutTelecharger && contrat.estSigne && (
                        <a
                            href={contratsAdmin.pdf(contrat.id).url}
                            className="btn-outline"
                            style={{ padding: '8px 16px', fontSize: 13 }}
                        >
                            Télécharger le PDF signé
                        </a>
                    )}
                    <span
                        className={`chip ${STATUT_CHIP[contrat.statut] ?? 'chip-gray'}`}
                        style={{ fontSize: 13, padding: '8px 16px' }}
                    >
                        {STATUT_LABEL[contrat.statut] ?? contrat.statut}
                    </span>
                </div>
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

            {peutModifier && contrat.statutsAccessibles.length > 0 && (
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
                            {[
                                contrat.statut,
                                ...contrat.statutsAccessibles,
                            ].map((statut) => (
                                <option key={statut} value={statut}>
                                    {STATUT_LABEL[statut] ?? statut}
                                </option>
                            ))}
                        </select>
                        {form.errors.statut && (
                            <p
                                style={{
                                    color: 'var(--dag-red)',
                                    fontSize: 12,
                                    marginTop: 4,
                                }}
                            >
                                {form.errors.statut}
                            </p>
                        )}
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
