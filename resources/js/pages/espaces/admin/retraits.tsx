import { Head, router, useForm } from '@inertiajs/react';
import retraitsAdmin from '@/routes/espace-admin/retraits';

type Retrait = {
    id: string;
    montant: string;
    moyen: string;
    telephone: string;
    beneficiaire: string;
    motif: string;
    statut: 'EN_ATTENTE' | 'CONFIRME' | 'ECHEC';
    motifEchec: string | null;
    reference: string | null;
    demandePar: string | null;
    demandeLe: string | null;
};

type Moyen = { value: string; label: string };

type Props = {
    retraits: Retrait[];
    moyens: Moyen[];
    peutRetirer: boolean;
    retraitsDisponibles: boolean;
};

const STATUT: Record<Retrait['statut'], { label: string; chip: string }> = {
    EN_ATTENTE: { label: 'En cours', chip: 'chip-orange' },
    CONFIRME: { label: 'Effectué', chip: 'chip-green' },
    ECHEC: { label: 'Échoué', chip: 'chip-red' },
};

function Erreur({ message }: { message?: string }) {
    if (!message) {
        return null;
    }

    return (
        <p style={{ color: 'var(--dag-red)', fontSize: 12, marginTop: 4 }}>
            {message}
        </p>
    );
}

export default function AdminRetraits({
    retraits,
    moyens,
    peutRetirer,
    retraitsDisponibles,
}: Props) {
    const form = useForm({
        montant: '',
        moyen: moyens[0]?.value ?? '',
        telephone: '',
        beneficiaire: 'DAG',
        motif: '',
    });

    const montantAffiche = Number(form.data.montant || 0).toLocaleString(
        'fr-FR',
    );

    const submit = (event: React.FormEvent) => {
        event.preventDefault();

        if (
            !window.confirm(
                `Envoyer ${montantAffiche} FCFA vers le ${form.data.telephone} ? Un retrait ne peut pas être annulé.`,
            )
        ) {
            return;
        }

        form.post(retraitsAdmin.store().url, {
            preserveScroll: true,
            onSuccess: () => form.reset('montant', 'telephone', 'motif'),
        });
    };

    return (
        <>
            <Head title="Retraits — Administration" />

            <div
                className="espace-hero"
                style={{
                    backgroundImage:
                        "url('/images/photos/payment-approved-phone.jpg')",
                }}
            >
                <div className="espace-hero-overlay" />
                <div className="espace-hero-content">
                    <h1>Retraits</h1>
                    <p>
                        Retirez l'argent encaissé en ligne vers un compte Orange
                        Money ou MTN Mobile Money.
                    </p>
                </div>
            </div>

            {peutRetirer && !retraitsDisponibles && (
                <div className="card" style={{ padding: 24, marginBottom: 28 }}>
                    <p className="muted">
                        Le prestataire de paiement actif ne permet pas les
                        retraits.
                    </p>
                </div>
            )}

            {peutRetirer && retraitsDisponibles && (
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
                        Nouveau retrait
                    </p>
                    <div
                        style={{
                            display: 'grid',
                            gridTemplateColumns:
                                'repeat(auto-fit, minmax(200px, 1fr))',
                            gap: 14,
                        }}
                    >
                        <div>
                            <label htmlFor="montant">Montant (FCFA)</label>
                            <input
                                id="montant"
                                type="number"
                                min={100}
                                step={1}
                                required
                                value={form.data.montant}
                                onChange={(event) =>
                                    form.setData('montant', event.target.value)
                                }
                            />
                            <Erreur message={form.errors.montant} />
                        </div>
                        <div>
                            <label htmlFor="moyen">Vers</label>
                            <select
                                id="moyen"
                                value={form.data.moyen}
                                onChange={(event) =>
                                    form.setData('moyen', event.target.value)
                                }
                            >
                                {moyens.map((moyen) => (
                                    <option
                                        key={moyen.value}
                                        value={moyen.value}
                                    >
                                        {moyen.label}
                                    </option>
                                ))}
                            </select>
                            <Erreur message={form.errors.moyen} />
                        </div>
                        <div>
                            <label htmlFor="telephone">Numéro mobile</label>
                            <input
                                id="telephone"
                                type="tel"
                                inputMode="tel"
                                placeholder="6 90 00 00 00"
                                required
                                value={form.data.telephone}
                                onChange={(event) =>
                                    form.setData(
                                        'telephone',
                                        event.target.value,
                                    )
                                }
                            />
                            <Erreur message={form.errors.telephone} />
                        </div>
                        <div>
                            <label htmlFor="beneficiaire">
                                Titulaire du compte
                            </label>
                            <input
                                id="beneficiaire"
                                required
                                maxLength={120}
                                value={form.data.beneficiaire}
                                onChange={(event) =>
                                    form.setData(
                                        'beneficiaire',
                                        event.target.value,
                                    )
                                }
                            />
                            <Erreur message={form.errors.beneficiaire} />
                        </div>
                    </div>
                    <div>
                        <label htmlFor="motif">Motif</label>
                        <input
                            id="motif"
                            required
                            maxLength={250}
                            placeholder="Ex. Reversement des encaissements d'octobre"
                            value={form.data.motif}
                            onChange={(event) =>
                                form.setData('motif', event.target.value)
                            }
                        />
                        <Erreur message={form.errors.motif} />
                    </div>
                    <button
                        type="submit"
                        disabled={form.processing}
                        className="btn-primary"
                        style={{ alignSelf: 'flex-start' }}
                    >
                        {form.processing ? 'Envoi…' : 'Retirer'}
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
                    Historique des retraits ({retraits.length})
                </p>
                {retraits.length === 0 ? (
                    <p className="data-table-empty">
                        Aucun retrait pour le moment.
                    </p>
                ) : (
                    <div className="data-table-wrap">
                        <table className="data-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Montant</th>
                                    <th>Vers</th>
                                    <th>Motif</th>
                                    <th>Demandé par</th>
                                    <th>Statut</th>
                                    <th />
                                </tr>
                            </thead>
                            <tbody>
                                {retraits.map((retrait) => (
                                    <tr key={retrait.id}>
                                        <td className="muted">
                                            {retrait.demandeLe}
                                        </td>
                                        <td style={{ fontWeight: 600 }}>
                                            {Number(
                                                retrait.montant,
                                            ).toLocaleString('fr-FR')}{' '}
                                            FCFA
                                        </td>
                                        <td>
                                            {retrait.beneficiaire}
                                            <br />
                                            <span
                                                className="muted"
                                                style={{ fontSize: 12 }}
                                            >
                                                {retrait.moyen} ·{' '}
                                                {retrait.telephone}
                                            </span>
                                        </td>
                                        <td className="muted">
                                            {retrait.motif}
                                        </td>
                                        <td className="muted">
                                            {retrait.demandePar ?? '—'}
                                        </td>
                                        <td>
                                            <span
                                                className={`chip ${STATUT[retrait.statut].chip}`}
                                            >
                                                {STATUT[retrait.statut].label}
                                            </span>
                                            {retrait.motifEchec && (
                                                <p
                                                    className="muted"
                                                    style={{
                                                        fontSize: 12,
                                                        marginTop: 4,
                                                    }}
                                                >
                                                    {retrait.motifEchec}
                                                </p>
                                            )}
                                        </td>
                                        <td style={{ textAlign: 'right' }}>
                                            {peutRetirer &&
                                                retrait.statut ===
                                                    'EN_ATTENTE' &&
                                                retrait.reference && (
                                                    <button
                                                        type="button"
                                                        className="link-action"
                                                        onClick={() =>
                                                            router.post(
                                                                retraitsAdmin.actualiser(
                                                                    retrait.id,
                                                                ).url,
                                                                {},
                                                                {
                                                                    preserveScroll: true,
                                                                },
                                                            )
                                                        }
                                                    >
                                                        Actualiser
                                                    </button>
                                                )}
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
