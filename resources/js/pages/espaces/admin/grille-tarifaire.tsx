import { Head, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import grilleTarifaireAdmin from '@/routes/espace-admin/grille-tarifaire';

type Ligne = {
    id: string;
    categorie: string;
    categorieOrdre: number;
    prestation: string;
    tarif: string;
    ordre: number;
};

type Actions = {
    creer: boolean;
    modifier: boolean;
    supprimer: boolean;
};

type Props = {
    lignes: Ligne[];
    categoriesExistantes: { nom: string; ordre: number }[];
    actions: Actions;
};

export default function AdminGrilleTarifaire({
    lignes,
    categoriesExistantes,
    actions,
}: Props) {
    const [editingId, setEditingId] = useState<string | null>(null);

    const createForm = useForm({
        categorie: categoriesExistantes[0]?.nom ?? '',
        categorie_ordre: categoriesExistantes[0]?.ordre ?? 1,
        prestation: '',
        tarif: '',
        ordre: 0,
    });

    const submitCreate = (event: React.FormEvent) => {
        event.preventDefault();
        createForm.post(grilleTarifaireAdmin.store().url, {
            onSuccess: () => createForm.reset('prestation', 'tarif', 'ordre'),
        });
    };

    const groupes = Object.values(
        lignes.reduce<Record<string, { categorie: string; lignes: Ligne[] }>>(
            (acc, ligne) => {
                acc[ligne.categorie] ??= {
                    categorie: ligne.categorie,
                    lignes: [],
                };
                acc[ligne.categorie].lignes.push(ligne);
                return acc;
            },
            {},
        ),
    );

    return (
        <>
            <Head title="Grille tarifaire — Administration" />

            <div
                className="espace-hero"
                style={{
                    backgroundImage: "url('/images/photos/glass-charts.jpg')",
                }}
            >
                <div className="espace-hero-overlay" />
                <div className="espace-hero-content">
                    <h1>Grille tarifaire</h1>
                    <p>
                        {actions.modifier || actions.supprimer
                            ? 'Gérez les prestations et tarifs indicatifs affichés publiquement.'
                            : 'Lecture seule : votre rôle ne permet pas de modifier la grille tarifaire.'}
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
                        Ajouter une ligne
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
                                    <option
                                        key={categorie.nom}
                                        value={categorie.nom}
                                    />
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
                            <label htmlFor="categorie_ordre">
                                N° de catégorie (ordre d'affichage)
                            </label>
                            <input
                                id="categorie_ordre"
                                type="number"
                                min={1}
                                value={createForm.data.categorie_ordre}
                                onChange={(event) =>
                                    createForm.setData(
                                        'categorie_ordre',
                                        Number(event.target.value),
                                    )
                                }
                            />
                            {createForm.errors.categorie_ordre && (
                                <p
                                    style={{
                                        color: 'var(--dag-red)',
                                        fontSize: 12,
                                        marginTop: 4,
                                    }}
                                >
                                    {createForm.errors.categorie_ordre}
                                </p>
                            )}
                        </div>
                        <div>
                            <label htmlFor="prestation">Prestation</label>
                            <input
                                id="prestation"
                                placeholder="Ex. Logo simple"
                                value={createForm.data.prestation}
                                onChange={(event) =>
                                    createForm.setData(
                                        'prestation',
                                        event.target.value,
                                    )
                                }
                            />
                            {createForm.errors.prestation && (
                                <p
                                    style={{
                                        color: 'var(--dag-red)',
                                        fontSize: 12,
                                        marginTop: 4,
                                    }}
                                >
                                    {createForm.errors.prestation}
                                </p>
                            )}
                        </div>
                        <div>
                            <label htmlFor="tarif">Tarif indicatif</label>
                            <input
                                id="tarif"
                                placeholder="Ex. 25 000–60 000 ou Sur devis"
                                value={createForm.data.tarif}
                                onChange={(event) =>
                                    createForm.setData(
                                        'tarif',
                                        event.target.value,
                                    )
                                }
                            />
                            {createForm.errors.tarif && (
                                <p
                                    style={{
                                        color: 'var(--dag-red)',
                                        fontSize: 12,
                                        marginTop: 4,
                                    }}
                                >
                                    {createForm.errors.tarif}
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
                        {createForm.processing ? 'Ajout…' : 'Ajouter la ligne'}
                    </button>
                </form>
            )}

            <div style={{ display: 'flex', flexDirection: 'column', gap: 24 }}>
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
                            {groupe.lignes[0]?.categorieOrdre}.{' '}
                            {groupe.categorie}
                        </p>
                        <div className="data-table-wrap">
                            <table className="data-table">
                                <thead>
                                    <tr>
                                        <th>Prestation</th>
                                        <th>Tarif</th>
                                        <th />
                                    </tr>
                                </thead>
                                <tbody>
                                    {groupe.lignes.map((ligne) => (
                                        <LigneRow
                                            key={ligne.id}
                                            ligne={ligne}
                                            editing={editingId === ligne.id}
                                            actions={actions}
                                            onEdit={() =>
                                                setEditingId(ligne.id)
                                            }
                                            onCancel={() => setEditingId(null)}
                                            onSaved={() => setEditingId(null)}
                                        />
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>
                ))}
            </div>
        </>
    );
}

function LigneRow({
    ligne,
    editing,
    actions,
    onEdit,
    onCancel,
    onSaved,
}: {
    ligne: Ligne;
    editing: boolean;
    actions: Actions;
    onEdit: () => void;
    onCancel: () => void;
    onSaved: () => void;
}) {
    const editForm = useForm({
        prestation: ligne.prestation,
        tarif: ligne.tarif,
    });

    const submitEdit = (event: React.FormEvent) => {
        event.preventDefault();
        editForm.patch(grilleTarifaireAdmin.update(ligne.id).url, {
            onSuccess: onSaved,
        });
    };

    const supprimer = () => {
        if (!confirm(`Supprimer « ${ligne.prestation} » ?`)) {
            return;
        }
        router.delete(grilleTarifaireAdmin.destroy(ligne.id).url);
    };

    if (editing) {
        return (
            <tr>
                <td colSpan={3}>
                    <form
                        onSubmit={submitEdit}
                        style={{
                            display: 'flex',
                            flexWrap: 'wrap',
                            alignItems: 'center',
                            gap: 10,
                        }}
                    >
                        <input
                            style={{ flex: 1, minWidth: 160 }}
                            value={editForm.data.prestation}
                            onChange={(event) =>
                                editForm.setData(
                                    'prestation',
                                    event.target.value,
                                )
                            }
                        />
                        <input
                            style={{ flex: 1, minWidth: 160 }}
                            value={editForm.data.tarif}
                            onChange={(event) =>
                                editForm.setData('tarif', event.target.value)
                            }
                        />
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
                    </form>
                </td>
            </tr>
        );
    }

    return (
        <tr>
            <td style={{ fontWeight: 600 }}>{ligne.prestation}</td>
            <td className="muted">{ligne.tarif}</td>
            <td style={{ textAlign: 'right' }}>
                {(actions.modifier || actions.supprimer) && (
                    <div
                        style={{
                            display: 'flex',
                            gap: 10,
                            justifyContent: 'flex-end',
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
            </td>
        </tr>
    );
}
