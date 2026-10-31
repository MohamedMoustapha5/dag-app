import { Head, Link, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import espaceAdmin from '@/routes/espace-admin';
import grilleTarifaireAdmin from '@/routes/espace-admin/grille-tarifaire';

type Ligne = {
    id: string;
    categorie: string;
    categorieOrdre: number;
    prestation: string;
    tarif: string;
    ordre: number;
};

type Props = {
    lignes: Ligne[];
    categoriesExistantes: { nom: string; ordre: number }[];
    droits: {
        creer: boolean;
        modifier: boolean;
        supprimer: boolean;
    };
};

export default function AdminGrilleTarifaire({
    lignes,
    categoriesExistantes,
    droits,
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
            <div className="mx-auto flex min-h-svh max-w-3xl flex-col gap-6 p-6">
                <div>
                    <Link
                        href={espaceAdmin.dashboard()}
                        className="text-sm text-muted-foreground underline"
                    >
                        ← Espace administrateur
                    </Link>
                    <h1 className="mt-2 text-2xl font-semibold">
                        Grille tarifaire
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        {droits.modifier || droits.supprimer
                            ? 'Gérez les prestations et tarifs indicatifs affichés publiquement.'
                            : 'Lecture seule : votre rôle ne permet pas de modifier la grille tarifaire.'}
                    </p>
                </div>

                {droits.creer && (
                    <form
                        onSubmit={submitCreate}
                        className="flex flex-col gap-3 rounded-xl border p-4"
                    >
                        <p className="text-sm font-medium">Ajouter une ligne</p>
                        <div className="grid grid-cols-2 gap-3">
                            <div className="flex flex-col gap-1">
                                <label
                                    htmlFor="categorie"
                                    className="text-xs text-muted-foreground"
                                >
                                    Catégorie
                                </label>
                                <input
                                    id="categorie"
                                    list="categories-existantes"
                                    className="rounded-md border px-2 py-1.5 text-sm"
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
                                    <p className="text-xs text-destructive">
                                        {createForm.errors.categorie}
                                    </p>
                                )}
                            </div>
                            <div className="flex flex-col gap-1">
                                <label
                                    htmlFor="categorie_ordre"
                                    className="text-xs text-muted-foreground"
                                >
                                    N° de catégorie (ordre d'affichage)
                                </label>
                                <input
                                    id="categorie_ordre"
                                    type="number"
                                    min={1}
                                    className="rounded-md border px-2 py-1.5 text-sm"
                                    value={createForm.data.categorie_ordre}
                                    onChange={(event) =>
                                        createForm.setData(
                                            'categorie_ordre',
                                            Number(event.target.value),
                                        )
                                    }
                                />
                                {createForm.errors.categorie_ordre && (
                                    <p className="text-xs text-destructive">
                                        {createForm.errors.categorie_ordre}
                                    </p>
                                )}
                            </div>
                            <div className="flex flex-col gap-1">
                                <label
                                    htmlFor="prestation"
                                    className="text-xs text-muted-foreground"
                                >
                                    Prestation
                                </label>
                                <input
                                    id="prestation"
                                    className="rounded-md border px-2 py-1.5 text-sm"
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
                                    <p className="text-xs text-destructive">
                                        {createForm.errors.prestation}
                                    </p>
                                )}
                            </div>
                            <div className="flex flex-col gap-1">
                                <label
                                    htmlFor="tarif"
                                    className="text-xs text-muted-foreground"
                                >
                                    Tarif indicatif
                                </label>
                                <input
                                    id="tarif"
                                    className="rounded-md border px-2 py-1.5 text-sm"
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
                                    <p className="text-xs text-destructive">
                                        {createForm.errors.tarif}
                                    </p>
                                )}
                            </div>
                        </div>
                        <button
                            type="submit"
                            disabled={createForm.processing}
                            className="self-start rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground disabled:opacity-60"
                        >
                            {createForm.processing
                                ? 'Ajout…'
                                : 'Ajouter la ligne'}
                        </button>
                    </form>
                )}

                <div className="flex flex-col gap-6">
                    {groupes.map((groupe) => (
                        <div key={groupe.categorie}>
                            <p className="mb-2 text-sm font-semibold">
                                {groupe.lignes[0]?.categorieOrdre}.{' '}
                                {groupe.categorie}
                            </p>
                            <div className="divide-y rounded-xl border">
                                {groupe.lignes.map((ligne) => (
                                    <LigneRow
                                        key={ligne.id}
                                        ligne={ligne}
                                        editing={editingId === ligne.id}
                                        droits={droits}
                                        onEdit={() => setEditingId(ligne.id)}
                                        onCancel={() => setEditingId(null)}
                                        onSaved={() => setEditingId(null)}
                                    />
                                ))}
                            </div>
                        </div>
                    ))}
                </div>
            </div>
        </>
    );
}

function LigneRow({
    ligne,
    editing,
    droits,
    onEdit,
    onCancel,
    onSaved,
}: {
    ligne: Ligne;
    editing: boolean;
    droits: Props['droits'];
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
            <form
                onSubmit={submitEdit}
                className="flex flex-wrap items-center gap-2 p-3"
            >
                <input
                    className="min-w-[160px] flex-1 rounded-md border px-2 py-1.5 text-sm"
                    value={editForm.data.prestation}
                    onChange={(event) =>
                        editForm.setData('prestation', event.target.value)
                    }
                />
                <input
                    className="min-w-[160px] flex-1 rounded-md border px-2 py-1.5 text-sm"
                    value={editForm.data.tarif}
                    onChange={(event) =>
                        editForm.setData('tarif', event.target.value)
                    }
                />
                <button
                    type="submit"
                    disabled={editForm.processing}
                    className="rounded-md bg-primary px-3 py-1.5 text-xs font-medium text-primary-foreground disabled:opacity-60"
                >
                    Enregistrer
                </button>
                <button
                    type="button"
                    onClick={onCancel}
                    className="rounded-md border px-3 py-1.5 text-xs font-medium"
                >
                    Annuler
                </button>
            </form>
        );
    }

    return (
        <div className="flex items-center justify-between gap-3 p-3 text-sm">
            <div className="min-w-0">
                <p className="font-medium">{ligne.prestation}</p>
                <p className="text-muted-foreground">{ligne.tarif}</p>
            </div>
            {(droits.modifier || droits.supprimer) && (
                <div className="flex shrink-0 gap-2">
                    {droits.modifier && (
                        <button
                            type="button"
                            onClick={onEdit}
                            className="text-xs text-muted-foreground underline"
                        >
                            Modifier
                        </button>
                    )}
                    {droits.supprimer && (
                        <button
                            type="button"
                            onClick={supprimer}
                            className="text-xs text-destructive underline"
                        >
                            Supprimer
                        </button>
                    )}
                </div>
            )}
        </div>
    );
}
