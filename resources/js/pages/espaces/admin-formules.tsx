import { Head, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
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

type Actions = {
    creer: boolean;
    modifier: boolean;
    supprimer: boolean;
};

type Props = {
    formules: Formule[];
    actions: Actions;
};

export default function AdminFormules({ formules, actions }: Props) {
    const [editingId, setEditingId] = useState<string | null>(null);

    const createForm = useForm({
        nom: '',
        prix_affiche: 'Prix sur devis, négocié après connexion',
        contenu: '',
        services_inclus: '',
        populaire: false,
        statut: 'BROUILLON',
    });

    const submitCreate = (event: React.FormEvent) => {
        event.preventDefault();
        createForm.post(formulesAdmin.store().url, {
            onSuccess: () =>
                createForm.reset(
                    'nom',
                    'contenu',
                    'services_inclus',
                    'populaire',
                    'statut',
                ),
        });
    };

    return (
        <>
            <Head title="Formules d'abonnement — Administration" />

            <div
                className="espace-hero"
                style={{
                    backgroundImage: "url('/images/photos/glass-charts.jpg')",
                }}
            >
                <div className="espace-hero-overlay" />
                <div className="espace-hero-content">
                    <h1>Formules d'abonnement</h1>
                    <p>
                        {actions.modifier || actions.supprimer
                            ? 'Gérez les formules affichées sur la page publique « Abonnement ».'
                            : 'Lecture seule : votre rôle ne permet pas de modifier les formules.'}
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
                        Ajouter une formule
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
                                placeholder="Ex. Essentiel"
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
                            <label htmlFor="prix_affiche">Prix affiché</label>
                            <input
                                id="prix_affiche"
                                placeholder="Ex. 150 000 FCFA / mois"
                                value={createForm.data.prix_affiche}
                                onChange={(event) =>
                                    createForm.setData(
                                        'prix_affiche',
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
                                <option value="PUBLIEE">Publiée</option>
                            </select>
                        </div>
                        <div
                            style={{
                                display: 'flex',
                                alignItems: 'center',
                            }}
                        >
                            <label
                                style={{
                                    display: 'flex',
                                    alignItems: 'center',
                                    gap: 10,
                                    fontSize: 13.5,
                                    marginTop: 18,
                                }}
                            >
                                <input
                                    type="checkbox"
                                    checked={createForm.data.populaire}
                                    onChange={(event) =>
                                        createForm.setData(
                                            'populaire',
                                            event.target.checked,
                                        )
                                    }
                                />
                                Mettre en avant (« Le plus choisi »)
                            </label>
                        </div>
                        <div style={{ gridColumn: '1 / -1' }}>
                            <label htmlFor="contenu">Description courte</label>
                            <input
                                id="contenu"
                                placeholder="Ex. Pour les petites structures qui démarrent."
                                value={createForm.data.contenu}
                                onChange={(event) =>
                                    createForm.setData(
                                        'contenu',
                                        event.target.value,
                                    )
                                }
                            />
                        </div>
                        <div style={{ gridColumn: '1 / -1' }}>
                            <label htmlFor="services_inclus">
                                Services inclus (un par ligne)
                            </label>
                            <textarea
                                id="services_inclus"
                                rows={4}
                                placeholder={
                                    'Ex.\nLogo et identité visuelle\nSite vitrine 5 pages\n2 campagnes réseaux sociaux / mois'
                                }
                                value={createForm.data.services_inclus}
                                onChange={(event) =>
                                    createForm.setData(
                                        'services_inclus',
                                        event.target.value,
                                    )
                                }
                            />
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
                            : 'Ajouter la formule'}
                    </button>
                </form>
            )}

            {formules.length === 0 ? (
                <div className="card" style={{ padding: 24 }}>
                    <p className="muted" style={{ fontSize: 13 }}>
                        Aucune formule pour le moment.
                    </p>
                </div>
            ) : (
                <div
                    style={{
                        display: 'flex',
                        flexDirection: 'column',
                        gap: 20,
                    }}
                >
                    {formules.map((formule) => (
                        <FormuleCard
                            key={formule.id}
                            formule={formule}
                            editing={editingId === formule.id}
                            actions={actions}
                            onEdit={() => setEditingId(formule.id)}
                            onCancel={() => setEditingId(null)}
                            onSaved={() => setEditingId(null)}
                        />
                    ))}
                </div>
            )}
        </>
    );
}

function FormuleCard({
    formule,
    editing,
    actions,
    onEdit,
    onCancel,
    onSaved,
}: {
    formule: Formule;
    editing: boolean;
    actions: Actions;
    onEdit: () => void;
    onCancel: () => void;
    onSaved: () => void;
}) {
    const editForm = useForm({
        nom: formule.nom,
        prix_affiche: formule.prixAffiche ?? '',
        contenu: formule.contenu ?? '',
        services_inclus: formule.servicesInclus.join('\n'),
        populaire: formule.populaire,
        statut: formule.statut,
    });

    const submitEdit = (event: React.FormEvent) => {
        event.preventDefault();
        editForm.patch(formulesAdmin.update(formule.id).url, {
            onSuccess: onSaved,
        });
    };

    const supprimer = () => {
        if (!confirm(`Supprimer la formule « ${formule.nom} » ?`)) {
            return;
        }
        router.delete(formulesAdmin.destroy(formule.id).url);
    };

    if (editing) {
        return (
            <form
                onSubmit={submitEdit}
                className="card"
                style={{
                    padding: 24,
                    display: 'flex',
                    flexDirection: 'column',
                    gap: 14,
                }}
            >
                <div
                    style={{
                        display: 'grid',
                        gridTemplateColumns:
                            'repeat(auto-fit, minmax(200px, 1fr))',
                        gap: 14,
                    }}
                >
                    <div>
                        <label>Nom</label>
                        <input
                            value={editForm.data.nom}
                            onChange={(event) =>
                                editForm.setData('nom', event.target.value)
                            }
                        />
                    </div>
                    <div>
                        <label>Prix affiché</label>
                        <input
                            value={editForm.data.prix_affiche}
                            onChange={(event) =>
                                editForm.setData(
                                    'prix_affiche',
                                    event.target.value,
                                )
                            }
                        />
                    </div>
                    <div>
                        <label>Statut</label>
                        <select
                            value={editForm.data.statut}
                            onChange={(event) =>
                                editForm.setData('statut', event.target.value)
                            }
                        >
                            <option value="BROUILLON">Brouillon</option>
                            <option value="PUBLIEE">Publiée</option>
                        </select>
                    </div>
                    <div style={{ display: 'flex', alignItems: 'center' }}>
                        <label
                            style={{
                                display: 'flex',
                                alignItems: 'center',
                                gap: 10,
                                fontSize: 13.5,
                                marginTop: 18,
                            }}
                        >
                            <input
                                type="checkbox"
                                checked={editForm.data.populaire}
                                onChange={(event) =>
                                    editForm.setData(
                                        'populaire',
                                        event.target.checked,
                                    )
                                }
                            />
                            Mettre en avant (« Le plus choisi »)
                        </label>
                    </div>
                    <div style={{ gridColumn: '1 / -1' }}>
                        <label>Description courte</label>
                        <input
                            value={editForm.data.contenu}
                            onChange={(event) =>
                                editForm.setData('contenu', event.target.value)
                            }
                        />
                    </div>
                    <div style={{ gridColumn: '1 / -1' }}>
                        <label>Services inclus (un par ligne)</label>
                        <textarea
                            rows={4}
                            value={editForm.data.services_inclus}
                            onChange={(event) =>
                                editForm.setData(
                                    'services_inclus',
                                    event.target.value,
                                )
                            }
                        />
                    </div>
                </div>
                <div style={{ display: 'flex', gap: 10 }}>
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
        );
    }

    return (
        <div className="card" style={{ padding: 24 }}>
            <div
                style={{
                    display: 'flex',
                    justifyContent: 'space-between',
                    alignItems: 'flex-start',
                    gap: 16,
                    flexWrap: 'wrap',
                }}
            >
                <div>
                    <div
                        style={{
                            display: 'flex',
                            alignItems: 'center',
                            gap: 10,
                        }}
                    >
                        <p style={{ fontSize: 16, fontWeight: 700 }}>
                            {formule.nom}
                        </p>
                        {formule.populaire && (
                            <span className="chip chip-blue">
                                Le plus choisi
                            </span>
                        )}
                        <span
                            className={`chip ${formule.statut === 'PUBLIEE' ? 'chip-green' : 'chip-gray'}`}
                        >
                            {formule.statut === 'PUBLIEE'
                                ? 'Publiée'
                                : 'Brouillon'}
                        </span>
                    </div>
                    {formule.contenu && (
                        <p
                            className="muted"
                            style={{ fontSize: 13, marginTop: 6 }}
                        >
                            {formule.contenu}
                        </p>
                    )}
                    <p
                        style={{
                            fontSize: 15,
                            fontWeight: 700,
                            marginTop: 8,
                            color: 'var(--dag-navy)',
                        }}
                    >
                        {formule.prixAffiche}
                    </p>
                </div>
                <span className="muted" style={{ fontSize: 12.5 }}>
                    {formule.abonnementsCount} abonnement
                    {formule.abonnementsCount > 1 ? 's' : ''}
                </span>
            </div>

            {formule.servicesInclus.length > 0 && (
                <ul
                    style={{
                        marginTop: 16,
                        display: 'flex',
                        flexWrap: 'wrap',
                        gap: 10,
                        padding: 0,
                        listStyle: 'none',
                    }}
                >
                    {formule.servicesInclus.map((service) => (
                        <li
                            key={service}
                            className="muted"
                            style={{
                                fontSize: 12.5,
                                background: '#f6f7fb',
                                borderRadius: 999,
                                padding: '5px 12px',
                            }}
                        >
                            {service}
                        </li>
                    ))}
                </ul>
            )}

            {(actions.modifier || actions.supprimer) && (
                <div
                    style={{
                        display: 'flex',
                        gap: 14,
                        marginTop: 18,
                        paddingTop: 16,
                        borderTop: '1px solid var(--dag-border)',
                    }}
                >
                    {actions.modifier && (
                        <button
                            type="button"
                            onClick={onEdit}
                            className="link-action"
                        >
                            Modifier
                        </button>
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
        </div>
    );
}
