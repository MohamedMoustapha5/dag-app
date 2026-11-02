import { Head, router, useForm } from '@inertiajs/react';
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
    actions: Actions;
};

const formatPrix = (prix: string | null) =>
    prix === null
        ? 'Sur devis'
        : `${new Intl.NumberFormat('fr-FR').format(Number(prix))} FCFA`;

export default function AdminServices({
    services,
    categoriesExistantes,
    actions,
}: Props) {
    const [editingId, setEditingId] = useState<string | null>(null);

    const createForm = useForm({
        nom: '',
        description: '',
        categorie: categoriesExistantes[0] ?? '',
        fournisseur: 'DAG',
        prix: '',
        image: '',
        statut: 'BROUILLON',
    });

    const submitCreate = (event: React.FormEvent) => {
        event.preventDefault();
        createForm.post(servicesAdmin.store().url, {
            onSuccess: () =>
                createForm.reset(
                    'nom',
                    'description',
                    'prix',
                    'image',
                    'statut',
                ),
        });
    };

    const groupes = Object.values(
        services.reduce<
            Record<string, { categorie: string; services: Service[] }>
        >((acc, service) => {
            acc[service.categorie] ??= {
                categorie: service.categorie,
                services: [],
            };
            acc[service.categorie].services.push(service);
            return acc;
        }, {}),
    );

    return (
        <>
            <Head title="Catalogue des services — Administration" />

            <div
                className="espace-hero"
                style={{
                    backgroundImage: "url('/images/photos/glass-charts.jpg')",
                }}
            >
                <div className="espace-hero-overlay" />
                <div className="espace-hero-content">
                    <h1>Catalogue des services</h1>
                    <p>
                        {actions.modifier || actions.supprimer
                            ? 'Gérez les prestations affichées dans le catalogue public et côté client.'
                            : 'Lecture seule : votre rôle ne permet pas de modifier le catalogue.'}
                    </p>
                </div>
            </div>

            {actions.creer && (
                <form
                    onSubmit={submitCreate}
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
                        Ajouter un service
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
                            <label htmlFor="nom">Nom</label>
                            <input
                                id="nom"
                                placeholder="Ex. Création de logo"
                                value={createForm.data.nom}
                                onChange={(event) =>
                                    createForm.setData(
                                        'nom',
                                        event.target.value,
                                    )
                                }
                            />
                            {createForm.errors.nom && (
                                <p
                                    style={{
                                        color: 'var(--dag-red)',
                                        fontSize: 12,
                                        marginTop: 4,
                                    }}
                                >
                                    {createForm.errors.nom}
                                </p>
                            )}
                        </div>
                        <div>
                            <label htmlFor="categorie">Catégorie</label>
                            <input
                                id="categorie"
                                list="categories-existantes"
                                value={createForm.data.categorie}
                                onChange={(event) =>
                                    createForm.setData(
                                        'categorie',
                                        event.target.value,
                                    )
                                }
                            />
                            <datalist id="categories-existantes">
                                {categoriesExistantes.map((categorie) => (
                                    <option key={categorie} value={categorie} />
                                ))}
                            </datalist>
                            {createForm.errors.categorie && (
                                <p
                                    style={{
                                        color: 'var(--dag-red)',
                                        fontSize: 12,
                                        marginTop: 4,
                                    }}
                                >
                                    {createForm.errors.categorie}
                                </p>
                            )}
                        </div>
                        <div>
                            <label htmlFor="fournisseur">Fournisseur</label>
                            <select
                                id="fournisseur"
                                value={createForm.data.fournisseur}
                                onChange={(event) =>
                                    createForm.setData(
                                        'fournisseur',
                                        event.target.value,
                                    )
                                }
                            >
                                <option value="DAG">DAG</option>
                                <option value="Nova-Tech">Nova-Tech</option>
                            </select>
                        </div>
                        <div>
                            <label htmlFor="prix">
                                Prix (FCFA, vide = sur devis)
                            </label>
                            <input
                                id="prix"
                                type="number"
                                min={0}
                                value={createForm.data.prix}
                                onChange={(event) =>
                                    createForm.setData(
                                        'prix',
                                        event.target.value,
                                    )
                                }
                            />
                        </div>
                        <div>
                            <label htmlFor="statut">Statut</label>
                            <select
                                id="statut"
                                value={createForm.data.statut}
                                onChange={(event) =>
                                    createForm.setData(
                                        'statut',
                                        event.target.value,
                                    )
                                }
                            >
                                <option value="BROUILLON">Brouillon</option>
                                <option value="PUBLIE">Publié</option>
                            </select>
                        </div>
                        <div>
                            <label htmlFor="image">
                                Image (chemin ou URL, optionnel)
                            </label>
                            <input
                                id="image"
                                placeholder="/images/services/logo.jpg"
                                value={createForm.data.image}
                                onChange={(event) =>
                                    createForm.setData(
                                        'image',
                                        event.target.value,
                                    )
                                }
                            />
                        </div>
                        <div style={{ gridColumn: '1 / -1' }}>
                            <label htmlFor="description">Description</label>
                            <textarea
                                id="description"
                                rows={2}
                                value={createForm.data.description}
                                onChange={(event) =>
                                    createForm.setData(
                                        'description',
                                        event.target.value,
                                    )
                                }
                            />
                            {createForm.errors.description && (
                                <p
                                    style={{
                                        color: 'var(--dag-red)',
                                        fontSize: 12,
                                        marginTop: 4,
                                    }}
                                >
                                    {createForm.errors.description}
                                </p>
                            )}
                        </div>
                    </div>
                    <button
                        type="submit"
                        disabled={createForm.processing}
                        className="btn-primary"
                        style={{ alignSelf: 'flex-start' }}
                    >
                        {createForm.processing
                            ? 'Ajout…'
                            : 'Ajouter le service'}
                    </button>
                </form>
            )}

            {groupes.length === 0 ? (
                <div className="card" style={{ padding: 24 }}>
                    <p className="muted" style={{ fontSize: 13 }}>
                        Aucun service dans le catalogue pour le moment.
                    </p>
                </div>
            ) : (
                <div
                    style={{
                        display: 'flex',
                        flexDirection: 'column',
                        gap: 24,
                    }}
                >
                    {groupes.map((groupe) => (
                        <div
                            key={groupe.categorie}
                            className="card"
                            style={{ padding: 24 }}
                        >
                            <p
                                style={{
                                    fontSize: 14.5,
                                    fontWeight: 700,
                                    marginBottom: 14,
                                }}
                            >
                                {groupe.categorie}
                            </p>
                            <div className="data-table-wrap">
                                <table className="data-table">
                                    <thead>
                                        <tr>
                                            <th>Service</th>
                                            <th>Fournisseur</th>
                                            <th>Prix</th>
                                            <th>Statut</th>
                                            <th />
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {groupe.services.map((service) => (
                                            <ServiceRow
                                                key={service.id}
                                                service={service}
                                                editing={
                                                    editingId === service.id
                                                }
                                                actions={actions}
                                                categoriesExistantes={
                                                    categoriesExistantes
                                                }
                                                onEdit={() =>
                                                    setEditingId(service.id)
                                                }
                                                onCancel={() =>
                                                    setEditingId(null)
                                                }
                                                onSaved={() =>
                                                    setEditingId(null)
                                                }
                                            />
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    ))}
                </div>
            )}
        </>
    );
}

function ServiceRow({
    service,
    editing,
    actions,
    categoriesExistantes,
    onEdit,
    onCancel,
    onSaved,
}: {
    service: Service;
    editing: boolean;
    actions: Actions;
    categoriesExistantes: string[];
    onEdit: () => void;
    onCancel: () => void;
    onSaved: () => void;
}) {
    const editForm = useForm({
        nom: service.nom,
        description: service.description,
        categorie: service.categorie,
        fournisseur: service.fournisseur,
        prix: service.prix === null ? '' : String(service.prix),
        image: service.image ?? '',
        statut: service.statut,
    });

    const submitEdit = (event: React.FormEvent) => {
        event.preventDefault();
        editForm.patch(servicesAdmin.update(service.id).url, {
            onSuccess: onSaved,
        });
    };

    const basculerStatut = () => {
        router.patch(
            servicesAdmin.update(service.id).url,
            { statut: service.statut === 'PUBLIE' ? 'BROUILLON' : 'PUBLIE' },
            { preserveScroll: true },
        );
    };

    const supprimer = () => {
        if (!confirm(`Supprimer « ${service.nom} » du catalogue ?`)) {
            return;
        }
        router.delete(servicesAdmin.destroy(service.id).url);
    };

    if (editing) {
        return (
            <tr>
                <td colSpan={5}>
                    <form
                        onSubmit={submitEdit}
                        style={{
                            display: 'grid',
                            gridTemplateColumns:
                                'repeat(auto-fit, minmax(160px, 1fr))',
                            gap: 10,
                            padding: '8px 0',
                        }}
                    >
                        <input
                            placeholder="Nom"
                            value={editForm.data.nom}
                            onChange={(event) =>
                                editForm.setData('nom', event.target.value)
                            }
                        />
                        <input
                            list="categories-existantes-edit"
                            placeholder="Catégorie"
                            value={editForm.data.categorie}
                            onChange={(event) =>
                                editForm.setData(
                                    'categorie',
                                    event.target.value,
                                )
                            }
                        />
                        <datalist id="categories-existantes-edit">
                            {categoriesExistantes.map((categorie) => (
                                <option key={categorie} value={categorie} />
                            ))}
                        </datalist>
                        <select
                            value={editForm.data.fournisseur}
                            onChange={(event) =>
                                editForm.setData(
                                    'fournisseur',
                                    event.target.value,
                                )
                            }
                        >
                            <option value="DAG">DAG</option>
                            <option value="Nova-Tech">Nova-Tech</option>
                        </select>
                        <input
                            type="number"
                            min={0}
                            placeholder="Prix (vide = sur devis)"
                            value={editForm.data.prix}
                            onChange={(event) =>
                                editForm.setData('prix', event.target.value)
                            }
                        />
                        <input
                            placeholder="Image (chemin ou URL)"
                            value={editForm.data.image}
                            onChange={(event) =>
                                editForm.setData('image', event.target.value)
                            }
                        />
                        <select
                            value={editForm.data.statut}
                            onChange={(event) =>
                                editForm.setData('statut', event.target.value)
                            }
                        >
                            <option value="BROUILLON">Brouillon</option>
                            <option value="PUBLIE">Publié</option>
                        </select>
                        <textarea
                            rows={2}
                            placeholder="Description"
                            style={{ gridColumn: '1 / -1' }}
                            value={editForm.data.description}
                            onChange={(event) =>
                                editForm.setData(
                                    'description',
                                    event.target.value,
                                )
                            }
                        />
                        <div
                            style={{
                                gridColumn: '1 / -1',
                                display: 'flex',
                                gap: 10,
                            }}
                        >
                            <button
                                type="submit"
                                disabled={editForm.processing}
                                className="btn-primary"
                                style={{ padding: '7px 14px', fontSize: 12.5 }}
                            >
                                Enregistrer
                            </button>
                            <button
                                type="button"
                                onClick={onCancel}
                                className="btn-outline"
                                style={{ padding: '7px 14px', fontSize: 12.5 }}
                            >
                                Annuler
                            </button>
                        </div>
                    </form>
                </td>
            </tr>
        );
    }

    return (
        <tr>
            <td>
                <p style={{ fontWeight: 600 }}>{service.nom}</p>
                <p
                    className="muted"
                    style={{
                        fontSize: 12,
                        maxWidth: 320,
                        overflow: 'hidden',
                        textOverflow: 'ellipsis',
                        whiteSpace: 'nowrap',
                    }}
                >
                    {service.description}
                </p>
            </td>
            <td>
                <span
                    className={`chip ${service.fournisseur === 'Nova-Tech' ? 'chip-orange' : 'chip-blue'}`}
                >
                    {service.fournisseur}
                </span>
            </td>
            <td className="muted">{formatPrix(service.prix)}</td>
            <td>
                <span
                    className={`chip ${service.statut === 'PUBLIE' ? 'chip-green' : 'chip-gray'}`}
                >
                    {service.statut === 'PUBLIE' ? 'Publié' : 'Brouillon'}
                </span>
            </td>
            <td style={{ textAlign: 'right' }}>
                {(actions.modifier || actions.supprimer) && (
                    <div
                        style={{
                            display: 'flex',
                            gap: 10,
                            justifyContent: 'flex-end',
                            flexWrap: 'wrap',
                        }}
                    >
                        {actions.modifier && (
                            <>
                                <button
                                    type="button"
                                    onClick={basculerStatut}
                                    className="link-action"
                                >
                                    {service.statut === 'PUBLIE'
                                        ? 'Mettre en brouillon'
                                        : 'Publier'}
                                </button>
                                <button
                                    type="button"
                                    onClick={onEdit}
                                    className="link-action"
                                >
                                    Modifier
                                </button>
                            </>
                        )}
                        {actions.supprimer && (
                            <button
                                type="button"
                                onClick={supprimer}
                                className="link-action"
                                style={{ color: 'var(--dag-red)' }}
                            >
                                Supprimer
                            </button>
                        )}
                    </div>
                )}
            </td>
        </tr>
    );
}
