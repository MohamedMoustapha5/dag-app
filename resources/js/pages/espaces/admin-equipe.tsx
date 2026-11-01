import { Head, Link, router, useForm } from '@inertiajs/react';
import espaceAdmin from '@/routes/espace-admin';
import equipeAdmin from '@/routes/espace-admin/equipe';

type Membre = {
    id: string;
    nom: string;
    email: string;
    role: string;
    roleLabel: string;
    statut: string;
    soiMeme: boolean;
};

type Role = { value: string; label: string };

type Props = {
    membres: Membre[];
    roles: Role[];
};

export default function AdminEquipe({ membres, roles }: Props) {
    const createForm = useForm({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
        role: roles[0]?.value ?? '',
    });

    const submitCreate = (event: React.FormEvent) => {
        event.preventDefault();
        createForm.post(equipeAdmin.store().url, {
            onSuccess: () =>
                createForm.reset(
                    'name',
                    'email',
                    'password',
                    'password_confirmation',
                ),
        });
    };

    const changerRole = (membreId: string, role: string) => {
        router.patch(
            equipeAdmin.update(membreId).url,
            { role },
            { preserveScroll: true },
        );
    };

    const basculerStatut = (membre: Membre) => {
        const statut = membre.statut === 'ACTIF' ? 'INACTIF' : 'ACTIF';
        if (
            statut === 'INACTIF' &&
            !confirm(`Désactiver le compte de ${membre.nom} ?`)
        ) {
            return;
        }
        router.patch(
            equipeAdmin.update(membre.id).url,
            { statut },
            { preserveScroll: true },
        );
    };

    return (
        <>
            <Head title="Équipe DAG — Administration" />
            <div className="mx-auto flex min-h-svh max-w-3xl flex-col gap-6 p-6">
                <div>
                    <Link
                        href={espaceAdmin.dashboard()}
                        className="text-sm text-muted-foreground underline"
                    >
                        ← Espace administrateur
                    </Link>
                    <h1 className="mt-2 text-2xl font-semibold">Équipe DAG</h1>
                    <p className="text-sm text-muted-foreground">
                        Seule la Promotrice peut créer un compte ou changer le
                        rôle d'un membre de l'équipe. Désactiver un compte lui
                        retire immédiatement l'accès.
                    </p>
                </div>

                <form
                    onSubmit={submitCreate}
                    className="flex flex-col gap-3 rounded-xl border p-4"
                >
                    <p className="text-sm font-medium">
                        Créer un compte membre de l'équipe
                    </p>
                    <div className="grid grid-cols-2 gap-3">
                        <div className="flex flex-col gap-1">
                            <label
                                htmlFor="name"
                                className="text-xs text-muted-foreground"
                            >
                                Nom
                            </label>
                            <input
                                id="name"
                                className="rounded-md border px-2 py-1.5 text-sm"
                                value={createForm.data.name}
                                onChange={(event) =>
                                    createForm.setData(
                                        'name',
                                        event.target.value,
                                    )
                                }
                            />
                            {createForm.errors.name && (
                                <p className="text-xs text-destructive">
                                    {createForm.errors.name}
                                </p>
                            )}
                        </div>
                        <div className="flex flex-col gap-1">
                            <label
                                htmlFor="email"
                                className="text-xs text-muted-foreground"
                            >
                                E-mail
                            </label>
                            <input
                                id="email"
                                type="email"
                                className="rounded-md border px-2 py-1.5 text-sm"
                                value={createForm.data.email}
                                onChange={(event) =>
                                    createForm.setData(
                                        'email',
                                        event.target.value,
                                    )
                                }
                            />
                            {createForm.errors.email && (
                                <p className="text-xs text-destructive">
                                    {createForm.errors.email}
                                </p>
                            )}
                        </div>
                        <div className="flex flex-col gap-1">
                            <label
                                htmlFor="password"
                                className="text-xs text-muted-foreground"
                            >
                                Mot de passe
                            </label>
                            <input
                                id="password"
                                type="password"
                                className="rounded-md border px-2 py-1.5 text-sm"
                                value={createForm.data.password}
                                onChange={(event) =>
                                    createForm.setData(
                                        'password',
                                        event.target.value,
                                    )
                                }
                            />
                            {createForm.errors.password && (
                                <p className="text-xs text-destructive">
                                    {createForm.errors.password}
                                </p>
                            )}
                        </div>
                        <div className="flex flex-col gap-1">
                            <label
                                htmlFor="password_confirmation"
                                className="text-xs text-muted-foreground"
                            >
                                Confirmer le mot de passe
                            </label>
                            <input
                                id="password_confirmation"
                                type="password"
                                className="rounded-md border px-2 py-1.5 text-sm"
                                value={createForm.data.password_confirmation}
                                onChange={(event) =>
                                    createForm.setData(
                                        'password_confirmation',
                                        event.target.value,
                                    )
                                }
                            />
                        </div>
                        <div className="col-span-2 flex flex-col gap-1">
                            <label
                                htmlFor="role"
                                className="text-xs text-muted-foreground"
                            >
                                Rôle
                            </label>
                            <select
                                id="role"
                                className="rounded-md border px-2 py-1.5 text-sm"
                                value={createForm.data.role}
                                onChange={(event) =>
                                    createForm.setData(
                                        'role',
                                        event.target.value,
                                    )
                                }
                            >
                                {roles.map((role) => (
                                    <option key={role.value} value={role.value}>
                                        {role.label}
                                    </option>
                                ))}
                            </select>
                            {createForm.errors.role && (
                                <p className="text-xs text-destructive">
                                    {createForm.errors.role}
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
                            ? 'Création…'
                            : 'Créer le compte'}
                    </button>
                </form>

                <div className="divide-y rounded-xl border">
                    {membres.map((membre) => (
                        <div
                            key={membre.id}
                            className="flex flex-wrap items-center justify-between gap-3 p-3 text-sm"
                        >
                            <div className="min-w-0">
                                <p className="font-medium">
                                    {membre.nom}
                                    {membre.soiMeme && (
                                        <span className="ml-2 text-xs text-muted-foreground">
                                            (vous)
                                        </span>
                                    )}
                                </p>
                                <p className="text-muted-foreground">
                                    {membre.email}
                                </p>
                            </div>
                            <div className="flex shrink-0 flex-wrap items-center gap-2">
                                <select
                                    className="rounded-md border px-2 py-1.5 text-xs"
                                    value={membre.role}
                                    onChange={(event) =>
                                        changerRole(
                                            membre.id,
                                            event.target.value,
                                        )
                                    }
                                >
                                    {roles.map((role) => (
                                        <option
                                            key={role.value}
                                            value={role.value}
                                        >
                                            {role.label}
                                        </option>
                                    ))}
                                </select>
                                <span
                                    className={`rounded-full px-2 py-1 text-xs font-medium ${
                                        membre.statut === 'ACTIF'
                                            ? 'bg-emerald-100 text-emerald-700'
                                            : 'bg-neutral-200 text-neutral-600'
                                    }`}
                                >
                                    {membre.statut === 'ACTIF'
                                        ? 'Actif'
                                        : 'Inactif'}
                                </span>
                                {!membre.soiMeme && (
                                    <button
                                        type="button"
                                        onClick={() => basculerStatut(membre)}
                                        className="text-xs text-muted-foreground underline"
                                    >
                                        {membre.statut === 'ACTIF'
                                            ? 'Désactiver'
                                            : 'Réactiver'}
                                    </button>
                                )}
                            </div>
                        </div>
                    ))}
                </div>
            </div>
        </>
    );
}
