import { Head, router, useForm } from '@inertiajs/react';
import { X } from 'lucide-react';
import { useState } from 'react';
import abonnementsAdmin from '@/routes/espace-admin/abonnements';
import formulesAdmin from '@/routes/espace-admin/formules';

type Formule = {
    id: string;
    nom: string;
    contenu: string | null;
    servicesInclus: string[];
    prixAffiche: string | null;
    populaire: boolean;
    statut: string;
    abonnementsCount: number;
};

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

type Props = {
    abonnements: Abonnement[];
    formules: Formule[];
    servicesDisponibles: string[];
    clients: Client[];
    peutCreer: boolean;
    peutModifier: boolean;
    peutSupprimer: boolean;
};

const STATUT_LABEL: Record<string, string> = {
    BROUILLON: 'Brouillon',
    ACTIF: 'Actif',
    SUSPENDU: 'Suspendu',
    TERMINE: 'Terminé',
};

export default function AdminAbonnements({
    abonnements,
    formules,
    servicesDisponibles,
    clients,
    peutCreer,
    peutModifier,
    peutSupprimer,
}: Props) {
    const [createFormuleOpen, setCreateFormuleOpen] = useState(false);
    const [editingFormule, setEditingFormule] = useState<Formule | null>(null);
    const [createAbonnementOpen, setCreateAbonnementOpen] = useState(false);

    const formulesPubliees = formules.filter(
        (formule) => formule.statut === 'PUBLIEE',
    );

    const supprimerFormule = (formule: Formule) => {
        if (!confirm(`Supprimer la formule « ${formule.nom} » ?`)) {
            return;
        }
        router.delete(formulesAdmin.destroy(formule.id).url);
    };

    const changerStatutAbonnement = (id: string, statut: string) => {
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
                style={{
                    display: 'flex',
                    justifyContent: 'space-between',
                    alignItems: 'flex-start',
                    gap: 16,
                    flexWrap: 'wrap',
                    marginBottom: 24,
                }}
            >
                <div>
                    <h1 style={{ fontSize: 26, color: 'var(--dag-navy)' }}>
                        Abonnements
                    </h1>
                    <p className="muted" style={{ marginTop: 4 }}>
                        Composez des formules combinant plusieurs services DAG
                        (site, application, cybersécurité…), publiées sur la
                        page Abonnement du site public.
                    </p>
                </div>
                {peutCreer && (
                    <button
                        type="button"
                        className="btn-primary"
                        onClick={() => setCreateFormuleOpen(true)}
                    >
                        + Créer un abonnement
                    </button>
                )}
            </div>

            <div className="card" style={{ padding: 0, marginBottom: 28 }}>
                {formules.length === 0 ? (
                    <p className="data-table-empty" style={{ padding: 24 }}>
                        Aucune formule pour le moment.
                    </p>
                ) : (
                    <div className="data-table-wrap">
                        <table className="data-table">
                            <thead>
                                <tr>
                                    <th>Formule</th>
                                    <th>Services inclus</th>
                                    <th>Prix</th>
                                    <th>Statut</th>
                                    <th />
                                </tr>
                            </thead>
                            <tbody>
                                {formules.map((formule) => (
                                    <tr key={formule.id}>
                                        <td style={{ fontWeight: 600 }}>
                                            {formule.nom}
                                            {formule.populaire && (
                                                <span
                                                    className="chip chip-blue"
                                                    style={{ marginLeft: 8 }}
                                                >
                                                    Populaire
                                                </span>
                                            )}
                                        </td>
                                        <td className="muted">
                                            {formule.servicesInclus.join(
                                                ', ',
                                            ) || '—'}
                                        </td>
                                        <td className="muted">
                                            {formule.prixAffiche}
                                        </td>
                                        <td>
                                            <span
                                                className={`chip ${formule.statut === 'PUBLIEE' ? 'chip-green' : 'chip-gray'}`}
                                            >
                                                {formule.statut === 'PUBLIEE'
                                                    ? 'Publié'
                                                    : 'Brouillon'}
                                            </span>
                                        </td>
                                        <td style={{ textAlign: 'right' }}>
                                            {(peutModifier ||
                                                peutSupprimer) && (
                                                <div
                                                    style={{
                                                        display: 'flex',
                                                        gap: 10,
                                                        justifyContent:
                                                            'flex-end',
                                                        flexWrap: 'wrap',
                                                    }}
                                                >
                                                    {peutModifier && (
                                                        <button
                                                            type="button"
                                                            className="link-action"
                                                            onClick={() =>
                                                                setEditingFormule(
                                                                    formule,
                                                                )
                                                            }
                                                        >
                                                            Modifier
                                                        </button>
                                                    )}
                                                    {peutSupprimer && (
                                                        <button
                                                            type="button"
                                                            className="link-action"
                                                            style={{
                                                                color: 'var(--dag-red)',
                                                            }}
                                                            onClick={() =>
                                                                supprimerFormule(
                                                                    formule,
                                                                )
                                                            }
                                                        >
                                                            Supprimer
                                                        </button>
                                                    )}
                                                </div>
                                            )}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </div>

            <div className="card" style={{ padding: 24 }}>
                <div
                    style={{
                        display: 'flex',
                        justifyContent: 'space-between',
                        alignItems: 'center',
                        gap: 16,
                        flexWrap: 'wrap',
                        marginBottom: 16,
                    }}
                >
                    <p style={{ fontSize: 14.5, fontWeight: 700 }}>
                        Abonnements ({abonnements.length})
                    </p>
                    {peutCreer &&
                        clients.length > 0 &&
                        formulesPubliees.length > 0 && (
                            <button
                                type="button"
                                className="btn-outline"
                                style={{ padding: '8px 16px', fontSize: 13 }}
                                onClick={() => setCreateAbonnementOpen(true)}
                            >
                                + Nouvel abonnement
                            </button>
                        )}
                </div>
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
                                                        changerStatutAbonnement(
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

            {createFormuleOpen && (
                <FormuleModal
                    mode="create"
                    servicesDisponibles={servicesDisponibles}
                    onClose={() => setCreateFormuleOpen(false)}
                />
            )}
            {editingFormule && (
                <FormuleModal
                    mode="edit"
                    formule={editingFormule}
                    servicesDisponibles={servicesDisponibles}
                    onClose={() => setEditingFormule(null)}
                />
            )}
            {createAbonnementOpen && (
                <AbonnementModal
                    clients={clients}
                    formules={formulesPubliees}
                    onClose={() => setCreateAbonnementOpen(false)}
                />
            )}
        </>
    );
}

function FormuleModal({
    mode,
    formule,
    servicesDisponibles,
    onClose,
}: {
    mode: 'create' | 'edit';
    formule?: Formule;
    servicesDisponibles: string[];
    onClose: () => void;
}) {
    const form = useForm<{
        nom: string;
        services_inclus: string[];
        contenu: string;
        prix_affiche: string;
        populaire: boolean;
        statut: string;
    }>({
        nom: formule?.nom ?? '',
        services_inclus: formule?.servicesInclus ?? [],
        contenu: formule?.contenu ?? '',
        prix_affiche:
            formule?.prixAffiche ?? 'Prix sur devis, négocié après connexion',
        populaire: formule?.populaire ?? false,
        statut: formule?.statut ?? 'BROUILLON',
    });
    const [nouvelElement, setNouvelElement] = useState('');

    const optionsServices = Array.from(
        new Set([...servicesDisponibles, ...form.data.services_inclus]),
    );

    const toggleService = (service: string) => {
        form.setData(
            'services_inclus',
            form.data.services_inclus.includes(service)
                ? form.data.services_inclus.filter((s) => s !== service)
                : [...form.data.services_inclus, service],
        );
    };

    const ajouterElement = () => {
        const valeur = nouvelElement.trim();
        if (valeur === '' || form.data.services_inclus.includes(valeur)) {
            setNouvelElement('');
            return;
        }
        form.setData('services_inclus', [...form.data.services_inclus, valeur]);
        setNouvelElement('');
    };

    const submit = (event: React.FormEvent) => {
        event.preventDefault();

        if (mode === 'create') {
            form.post(formulesAdmin.store().url, { onSuccess: onClose });
        } else if (formule) {
            form.patch(formulesAdmin.update(formule.id).url, {
                onSuccess: onClose,
            });
        }
    };

    return (
        <div
            className="modal-backdrop"
            role="dialog"
            aria-modal="true"
            aria-label={
                mode === 'create'
                    ? 'Créer un abonnement'
                    : 'Modifier la formule'
            }
            onClick={(event) => {
                if (event.target === event.currentTarget) {
                    onClose();
                }
            }}
        >
            <div className="modal-panel">
                <button
                    type="button"
                    className="modal-close"
                    onClick={onClose}
                    aria-label="Fermer"
                >
                    <X size={16} />
                </button>

                <h2 style={{ fontSize: 19, color: 'var(--dag-navy)' }}>
                    {mode === 'create'
                        ? 'Créer un abonnement'
                        : 'Modifier la formule'}
                </h2>
                <p
                    style={{
                        marginTop: 8,
                        marginBottom: 24,
                        fontSize: 13.5,
                        lineHeight: 1.6,
                        color: 'var(--dag-text-muted)',
                    }}
                >
                    Combine plusieurs services DAG en une formule mensuelle,
                    publiée sur la page Abonnement.
                </p>

                <form
                    onSubmit={submit}
                    style={{
                        display: 'flex',
                        flexDirection: 'column',
                        gap: 16,
                    }}
                >
                    <div>
                        <label htmlFor="nom">Nom de la formule</label>
                        <input
                            id="nom"
                            placeholder="Ex. Présence 360"
                            value={form.data.nom}
                            onChange={(event) =>
                                form.setData('nom', event.target.value)
                            }
                            autoFocus
                        />
                        {form.errors.nom && (
                            <p
                                style={{
                                    color: 'var(--dag-red)',
                                    fontSize: 12,
                                    marginTop: 4,
                                }}
                            >
                                {form.errors.nom}
                            </p>
                        )}
                    </div>

                    <div>
                        <label>Services inclus (cochez pour combiner)</label>
                        <div
                            style={{
                                display: 'flex',
                                flexWrap: 'wrap',
                                gap: 10,
                                marginTop: 8,
                            }}
                        >
                            {optionsServices.map((service) => {
                                const coche =
                                    form.data.services_inclus.includes(service);

                                return (
                                    <label
                                        key={service}
                                        style={{
                                            display: 'flex',
                                            alignItems: 'center',
                                            gap: 8,
                                            padding: '8px 14px',
                                            borderRadius: 999,
                                            fontSize: 13,
                                            cursor: 'pointer',
                                            border: `1px solid ${coche ? 'var(--dag-blue)' : 'var(--dag-border)'}`,
                                            color: coche
                                                ? 'var(--dag-blue)'
                                                : 'var(--dag-text)',
                                        }}
                                    >
                                        <input
                                            type="checkbox"
                                            checked={coche}
                                            onChange={() =>
                                                toggleService(service)
                                            }
                                        />
                                        {service}
                                    </label>
                                );
                            })}
                        </div>
                        <input
                            placeholder="Ajouter un autre élément (optionnel)"
                            style={{ marginTop: 10 }}
                            value={nouvelElement}
                            onChange={(event) =>
                                setNouvelElement(event.target.value)
                            }
                            onBlur={ajouterElement}
                            onKeyDown={(event) => {
                                if (event.key === 'Enter') {
                                    event.preventDefault();
                                    ajouterElement();
                                }
                            }}
                        />
                    </div>

                    <div>
                        <label htmlFor="contenu">Description courte</label>
                        <input
                            id="contenu"
                            placeholder="Visible sur la page Abonnement"
                            value={form.data.contenu}
                            onChange={(event) =>
                                form.setData('contenu', event.target.value)
                            }
                        />
                    </div>

                    <div>
                        <label htmlFor="prix_affiche">Prix affiché</label>
                        <input
                            id="prix_affiche"
                            value={form.data.prix_affiche}
                            onChange={(event) =>
                                form.setData('prix_affiche', event.target.value)
                            }
                        />
                    </div>

                    <label
                        style={{
                            display: 'flex',
                            alignItems: 'center',
                            gap: 10,
                            fontSize: 13.5,
                        }}
                    >
                        <input
                            type="checkbox"
                            checked={form.data.populaire}
                            onChange={(event) =>
                                form.setData('populaire', event.target.checked)
                            }
                        />
                        Mettre en avant (badge « Populaire »)
                    </label>

                    <div>
                        <label htmlFor="statut">Statut</label>
                        <select
                            id="statut"
                            value={form.data.statut}
                            onChange={(event) =>
                                form.setData('statut', event.target.value)
                            }
                        >
                            <option value="BROUILLON">Brouillon</option>
                            <option value="PUBLIEE">Publié</option>
                        </select>
                    </div>

                    <div style={{ display: 'flex', gap: 10 }}>
                        <button
                            type="button"
                            onClick={onClose}
                            className="btn-outline"
                            style={{ flex: 1 }}
                        >
                            Annuler
                        </button>
                        <button
                            type="submit"
                            disabled={form.processing}
                            className="btn-primary"
                            style={{ flex: 1 }}
                        >
                            {form.processing
                                ? 'Enregistrement…'
                                : mode === 'create'
                                  ? 'Créer'
                                  : 'Enregistrer'}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    );
}

function AbonnementModal({
    clients,
    formules,
    onClose,
}: {
    clients: Client[];
    formules: Formule[];
    onClose: () => void;
}) {
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
        form.post(abonnementsAdmin.store().url, { onSuccess: onClose });
    };

    return (
        <div
            className="modal-backdrop"
            role="dialog"
            aria-modal="true"
            aria-label="Nouvel abonnement"
            onClick={(event) => {
                if (event.target === event.currentTarget) {
                    onClose();
                }
            }}
        >
            <div className="modal-panel">
                <button
                    type="button"
                    className="modal-close"
                    onClick={onClose}
                    aria-label="Fermer"
                >
                    <X size={16} />
                </button>

                <h2 style={{ fontSize: 19, color: 'var(--dag-navy)' }}>
                    Nouvel abonnement
                </h2>
                <p
                    style={{
                        marginTop: 8,
                        marginBottom: 24,
                        fontSize: 13.5,
                        lineHeight: 1.6,
                        color: 'var(--dag-text-muted)',
                    }}
                >
                    Souscrit un client à une formule publiée.
                </p>

                <form
                    onSubmit={submit}
                    style={{
                        display: 'flex',
                        flexDirection: 'column',
                        gap: 16,
                    }}
                >
                    <div>
                        <label htmlFor="client_id">Client</label>
                        <select
                            id="client_id"
                            value={form.data.client_id}
                            onChange={(event) =>
                                form.setData('client_id', event.target.value)
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
                                form.setData('formule_id', event.target.value)
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
                        <label htmlFor="montant_negocie">Montant (FCFA)</label>
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
                                form.setData('cycle_debut', event.target.value)
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

                    <div style={{ display: 'flex', gap: 10 }}>
                        <button
                            type="button"
                            onClick={onClose}
                            className="btn-outline"
                            style={{ flex: 1 }}
                        >
                            Annuler
                        </button>
                        <button
                            type="submit"
                            disabled={form.processing}
                            className="btn-primary"
                            style={{ flex: 1 }}
                        >
                            {form.processing
                                ? 'Création…'
                                : "Créer l'abonnement"}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    );
}
