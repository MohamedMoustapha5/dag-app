import { Head, router, useForm } from '@inertiajs/react';
import abonnementsAdmin from '@/routes/espace-admin/abonnements';

type Abonnement = {
    id: string;
    client: string;
    formule: string;
    montant: string | null;
    echeance: string | null;
    statut: string;
    progression: number;
};

type Client = { id: string; nom: string; type: string };
type Formule = { id: string; nom: string; prixAffiche?: string };

type Props = {
    abonnements: Abonnement[];
    clients: Client[];
    formules: Formule[];
    peutCreer: boolean;
    peutModifier: boolean;
};

const STATUT_LABEL: Record<string, string> = {
    BROUILLON: 'Brouillon',
    ACTIF: 'Actif',
    SUSPENDU: 'Suspendu',
    TERMINE: 'Terminé',
};

export default function AdminAbonnements({
    abonnements,
    clients,
    formules,
    peutCreer,
    peutModifier,
}: Props) {
    const form = useForm({
        client_id: clients[0]?.id ?? '',
        formule_id: formules[0]?.id ?? '',
        montant_negocie: '',
        date_souscription: new Date().toISOString().slice(0, 10),
        cycle_debut: '',
        echeance: '',
    });

    const submit = (event: React.FormEvent) => {
        event.preventDefault();
        form.post(abonnementsAdmin.store().url, {
            preserveScroll: true,
            onSuccess: () =>
                form.reset('montant_negocie', 'cycle_debut', 'echeance'),
        });
    };

    const changerStatut = (id: string, statut: string) => {
        router.patch(
            abonnementsAdmin.update(id).url,
            { statut },
            { preserveScroll: true },
        );
    };

    return (
        <>
            <Head title="Abonnements — Administration" />

            <div
                className="espace-hero"
                style={{
                    backgroundImage:
                        "url('/images/photos/phone-chart-app.jpg')",
                }}
            >
                <div className="espace-hero-overlay" />
                <div className="espace-hero-content">
                    <h1>Abonnements</h1>
                    <p>
                        Suivez les abonnements souscrits et leur cycle de
                        facturation.
                    </p>
                </div>
            </div>

            {peutCreer && clients.length > 0 && formules.length > 0 && (
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
                        Créer un abonnement
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
                            <label htmlFor="client_id">Client</label>
                            <select
                                id="client_id"
                                value={form.data.client_id}
                                onChange={(event) =>
                                    form.setData(
                                        'client_id',
                                        event.target.value,
                                    )
                                }
                            >
                                {clients.map((client) => (
                                    <option key={client.id} value={client.id}>
                                        {client.nom}
                                    </option>
                                ))}
                            </select>
                        </div>
                        <div>
                            <label htmlFor="formule_id">Formule</label>
                            <select
                                id="formule_id"
                                value={form.data.formule_id}
                                onChange={(event) =>
                                    form.setData(
                                        'formule_id',
                                        event.target.value,
                                    )
                                }
                            >
                                {formules.map((formule) => (
                                    <option key={formule.id} value={formule.id}>
                                        {formule.nom}
                                    </option>
                                ))}
                            </select>
                        </div>
                        <div>
                            <label htmlFor="montant_negocie">
                                Montant (FCFA)
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
                        <div>
                            <label htmlFor="date_souscription">
                                Date de souscription
                            </label>
                            <input
                                id="date_souscription"
                                type="date"
                                value={form.data.date_souscription}
                                onChange={(event) =>
                                    form.setData(
                                        'date_souscription',
                                        event.target.value,
                                    )
                                }
                            />
                        </div>
                        <div>
                            <label htmlFor="cycle_debut">Début de cycle</label>
                            <input
                                id="cycle_debut"
                                type="date"
                                value={form.data.cycle_debut}
                                onChange={(event) =>
                                    form.setData(
                                        'cycle_debut',
                                        event.target.value,
                                    )
                                }
                            />
                        </div>
                        <div>
                            <label htmlFor="echeance">Échéance</label>
                            <input
                                id="echeance"
                                type="date"
                                value={form.data.echeance}
                                onChange={(event) =>
                                    form.setData('echeance', event.target.value)
                                }
                            />
                            {form.errors.echeance && (
                                <p
                                    style={{
                                        color: 'var(--dag-red)',
                                        fontSize: 12,
                                        marginTop: 4,
                                    }}
                                >
                                    {form.errors.echeance}
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
                        {form.processing ? 'Création…' : "Créer l'abonnement"}
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
                    Abonnements ({abonnements.length})
                </p>
                {abonnements.length === 0 ? (
                    <p className="data-table-empty">
                        Aucun abonnement pour le moment.
                    </p>
                ) : (
                    <div className="data-table-wrap">
                        <table className="data-table">
                            <thead>
                                <tr>
                                    <th>Client</th>
                                    <th>Formule</th>
                                    <th>Montant</th>
                                    <th>Échéance</th>
                                    <th>Cycle</th>
                                    <th>Statut</th>
                                </tr>
                            </thead>
                            <tbody>
                                {abonnements.map((abonnement) => (
                                    <tr key={abonnement.id}>
                                        <td style={{ fontWeight: 600 }}>
                                            {abonnement.client}
                                        </td>
                                        <td className="muted">
                                            {abonnement.formule}
                                        </td>
                                        <td className="muted">
                                            {abonnement.montant
                                                ? `${abonnement.montant} FCFA`
                                                : '—'}
                                        </td>
                                        <td className="muted">
                                            {abonnement.echeance ?? '—'}
                                        </td>
                                        <td style={{ minWidth: 100 }}>
                                            <div className="espace-formule-progress">
                                                <div
                                                    className="espace-formule-progress-bar"
                                                    style={{
                                                        width: `${abonnement.progression}%`,
                                                    }}
                                                />
                                            </div>
                                        </td>
                                        <td>
                                            {peutModifier ? (
                                                <select
                                                    value={abonnement.statut}
                                                    style={{
                                                        padding: '6px 10px',
                                                        fontSize: 12.5,
                                                    }}
                                                    onChange={(event) =>
                                                        changerStatut(
                                                            abonnement.id,
                                                            event.target.value,
                                                        )
                                                    }
                                                >
                                                    {Object.entries(
                                                        STATUT_LABEL,
                                                    ).map(([valeur, label]) => (
                                                        <option
                                                            key={valeur}
                                                            value={valeur}
                                                        >
                                                            {label}
                                                        </option>
                                                    ))}
                                                </select>
                                            ) : (
                                                <span className="chip chip-gray">
                                                    {STATUT_LABEL[
                                                        abonnement.statut
                                                    ] ?? abonnement.statut}
                                                </span>
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
