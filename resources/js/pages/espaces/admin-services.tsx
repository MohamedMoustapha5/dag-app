import { Head, router, useForm } from '@inertiajs/react';
import { Upload, X } from 'lucide-react';
import { useState } from 'react';
import servicesAdmin from '@/routes/espace-admin/services';

type Service = {
    id: string;
    nom: string;
    description: string;
    categorie: string;
    fournisseur: string;
    image: string | null;
    prix: string | null;
    statut: string;
};

type Actions = {
    creer: boolean;
    modifier: boolean;
    supprimer: boolean;
};

type Props = {
    services: Service[];
    categoriesExistantes: string[];
    fournisseursExistants: string[];
    droitsResume: string;
    actions: Actions;
};

const afficherFournisseur = (fournisseur: string) =>
    fournisseur === 'DAG' ? 'DAG (interne)' : fournisseur;

export default function AdminServices({
    services,
    categoriesExistantes,
    fournisseursExistants,
    droitsResume,
    actions,
}: Props) {
    const [createOpen, setCreateOpen] = useState(false);
    const [editingService, setEditingService] = useState<Service | null>(null);

    const supprimer = (service: Service) => {
        if (!confirm(`Supprimer « ${service.nom} » du catalogue ?`)) {
            return;
        }
        router.delete(servicesAdmin.destroy(service.id).url);
    };

    return (
        <>
            <Head title="Catalogue de services — Administration" />

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
                        Catalogue de services
                    </h1>
                    <p className="muted" style={{ marginTop: 4 }}>
                        {droitsResume}
                    </p>
                </div>
                {actions.creer && (
                    <button
                        type="button"
                        className="btn-primary"
                        onClick={() => setCreateOpen(true)}
                    >
                        + Ajouter un service
                    </button>
                )}
            </div>

            <div className="card" style={{ padding: 0 }}>
                {services.length === 0 ? (
                    <p className="data-table-empty" style={{ padding: 24 }}>
                        Aucun service dans le catalogue pour le moment.
                    </p>
                ) : (
                    <div className="data-table-wrap">
                        <table className="data-table">
                            <thead>
                                <tr>
                                    <th>Service</th>
                                    <th>Catégorie</th>
                                    <th>Fournisseur</th>
                                    <th>Statut</th>
                                    <th />
                                </tr>
                            </thead>
                            <tbody>
                                {services.map((service) => (
                                    <tr key={service.id}>
                                        <td>
                                            <div
                                                style={{
                                                    display: 'flex',
                                                    alignItems: 'center',
                                                    gap: 12,
                                                }}
                                            >
                                                {service.image ? (
                                                    <img
                                                        src={service.image}
                                                        alt=""
                                                        style={{
                                                            width: 40,
                                                            height: 40,
                                                            borderRadius: 8,
                                                            objectFit: 'cover',
                                                            flex: 'none',
                                                        }}
                                                    />
                                                ) : (
                                                    <span
                                                        style={{
                                                            width: 40,
                                                            height: 40,
                                                            borderRadius: 8,
                                                            background:
                                                                '#f1f2f6',
                                                            flex: 'none',
                                                        }}
                                                    />
                                                )}
                                                <span
                                                    style={{
                                                        fontWeight: 600,
                                                    }}
                                                >
                                                    {service.nom}
                                                </span>
                                            </div>
                                        </td>
                                        <td className="muted">
                                            {service.categorie}
                                        </td>
                                        <td className="muted">
                                            {afficherFournisseur(
                                                service.fournisseur,
                                            )}
                                        </td>
                                        <td>
                                            <span
                                                className={`chip ${service.statut === 'PUBLIE' ? 'chip-green' : 'chip-gray'}`}
                                            >
                                                {service.statut === 'PUBLIE'
                                                    ? 'Publié'
                                                    : 'Brouillon'}
                                            </span>
                                        </td>
                                        <td style={{ textAlign: 'right' }}>
                                            {(actions.modifier ||
                                                actions.supprimer) && (
                                                <div
                                                    style={{
                                                        display: 'flex',
                                                        gap: 10,
                                                        justifyContent:
                                                            'flex-end',
                                                        flexWrap: 'wrap',
                                                    }}
                                                >
                                                    {actions.modifier && (
                                                        <button
                                                            type="button"
                                                            className="link-action"
                                                            onClick={() =>
                                                                setEditingService(
                                                                    service,
                                                                )
                                                            }
                                                        >
                                                            Modifier
                                                        </button>
                                                    )}
                                                    {actions.supprimer && (
                                                        <button
                                                            type="button"
                                                            className="link-action"
                                                            style={{
                                                                color: 'var(--dag-red)',
                                                            }}
                                                            onClick={() =>
                                                                supprimer(
                                                                    service,
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

            {createOpen && (
                <ServiceModal
                    mode="create"
                    categoriesExistantes={categoriesExistantes}
                    fournisseursExistants={fournisseursExistants}
                    onClose={() => setCreateOpen(false)}
                />
            )}
            {editingService && (
                <ServiceModal
                    mode="edit"
                    service={editingService}
                    categoriesExistantes={categoriesExistantes}
                    fournisseursExistants={fournisseursExistants}
                    onClose={() => setEditingService(null)}
                />
            )}
        </>
    );
}

function ServiceModal({
    mode,
    service,
    categoriesExistantes,
    fournisseursExistants,
    onClose,
}: {
    mode: 'create' | 'edit';
    service?: Service;
    categoriesExistantes: string[];
    fournisseursExistants: string[];
    onClose: () => void;
}) {
    const form = useForm<{
        nom: string;
        categorie: string;
        fournisseur: string;
        description: string;
        image: File | null;
        statut: string;
    }>({
        nom: service?.nom ?? '',
        categorie: service?.categorie ?? categoriesExistantes[0] ?? '',
        fournisseur: service?.fournisseur ?? 'DAG',
        description: service?.description ?? '',
        image: null,
        statut: service?.statut ?? 'BROUILLON',
    });

    const submit = (event: React.FormEvent) => {
        event.preventDefault();

        if (mode === 'create') {
            form.post(servicesAdmin.store().url, { onSuccess: onClose });
        } else if (service) {
            form.patch(servicesAdmin.update(service.id).url, {
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
                mode === 'create' ? 'Ajouter un service' : 'Modifier le service'
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
                        ? 'Ajouter un service'
                        : 'Modifier le service'}
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
                    {mode === 'create'
                        ? "Le service apparaît en brouillon tant qu'il n'est pas publié."
                        : 'Les modifications sont enregistrées immédiatement.'}
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
                        <label htmlFor="nom">Nom du service</label>
                        <input
                            id="nom"
                            placeholder="Ex. Montage vidéo"
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
                        <label htmlFor="categorie">Catégorie</label>
                        <input
                            id="categorie"
                            list="categories-existantes"
                            placeholder="Ex. Création & communication…"
                            value={form.data.categorie}
                            onChange={(event) =>
                                form.setData('categorie', event.target.value)
                            }
                        />
                        <datalist id="categories-existantes">
                            {categoriesExistantes.map((categorie) => (
                                <option key={categorie} value={categorie} />
                            ))}
                        </datalist>
                        <p
                            className="muted"
                            style={{ fontSize: 11.5, marginTop: 4 }}
                        >
                            Détermine la section où le service apparaît dans le
                            catalogue public. Tapez une nouvelle catégorie pour
                            en créer une.
                        </p>
                        {form.errors.categorie && (
                            <p
                                style={{
                                    color: 'var(--dag-red)',
                                    fontSize: 12,
                                    marginTop: 4,
                                }}
                            >
                                {form.errors.categorie}
                            </p>
                        )}
                    </div>

                    <div>
                        <label htmlFor="fournisseur">Fournisseur</label>
                        <input
                            id="fournisseur"
                            list="fournisseurs-existants"
                            placeholder="Ex. DAG (interne), Nova-Tech…"
                            value={form.data.fournisseur}
                            onChange={(event) =>
                                form.setData('fournisseur', event.target.value)
                            }
                        />
                        <datalist id="fournisseurs-existants">
                            {fournisseursExistants.map((fournisseur) => (
                                <option key={fournisseur} value={fournisseur} />
                            ))}
                        </datalist>
                        {form.errors.fournisseur && (
                            <p
                                style={{
                                    color: 'var(--dag-red)',
                                    fontSize: 12,
                                    marginTop: 4,
                                }}
                            >
                                {form.errors.fournisseur}
                            </p>
                        )}
                    </div>

                    <div>
                        <label htmlFor="description">
                            Description{' '}
                            <span style={{ color: 'var(--dag-red)' }}>*</span>
                        </label>
                        <textarea
                            id="description"
                            rows={2}
                            placeholder="Visible sur la fiche du catalogue"
                            value={form.data.description}
                            onChange={(event) =>
                                form.setData('description', event.target.value)
                            }
                        />
                        {form.errors.description && (
                            <p
                                style={{
                                    color: 'var(--dag-red)',
                                    fontSize: 12,
                                    marginTop: 4,
                                }}
                            >
                                {form.errors.description}
                            </p>
                        )}
                    </div>

                    <div>
                        <label htmlFor="image">Image du service</label>
                        <label
                            htmlFor="image"
                            className="dashed"
                            style={{
                                display: 'flex',
                                flexDirection: 'column',
                                alignItems: 'center',
                                justifyContent: 'center',
                                gap: 6,
                                padding: '28px 16px',
                                cursor: 'pointer',
                                textAlign: 'center',
                            }}
                        >
                            <Upload
                                size={20}
                                style={{ color: 'var(--dag-text-muted)' }}
                            />
                            <span style={{ fontWeight: 600, fontSize: 13.5 }}>
                                {form.data.image
                                    ? form.data.image.name
                                    : 'Cliquez pour importer une image'}
                            </span>
                            <span className="muted" style={{ fontSize: 11.5 }}>
                                JPG, PNG — ratio 4:3 recommandé
                            </span>
                        </label>
                        <input
                            id="image"
                            type="file"
                            accept="image/jpeg,image/png,image/webp"
                            style={{ display: 'none' }}
                            onChange={(event) =>
                                form.setData(
                                    'image',
                                    event.target.files?.[0] ?? null,
                                )
                            }
                        />
                        {form.errors.image && (
                            <p
                                style={{
                                    color: 'var(--dag-red)',
                                    fontSize: 12,
                                    marginTop: 4,
                                }}
                            >
                                {form.errors.image}
                            </p>
                        )}
                        {mode === 'edit' && service?.image && (
                            <img
                                src={service.image}
                                alt=""
                                style={{
                                    marginTop: 10,
                                    width: '100%',
                                    height: 100,
                                    objectFit: 'cover',
                                    borderRadius: 'var(--dag-radius-sm)',
                                }}
                            />
                        )}
                    </div>

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
                            <option value="PUBLIE">Publié</option>
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
                                  ? 'Ajouter le service'
                                  : 'Enregistrer'}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    );
}
