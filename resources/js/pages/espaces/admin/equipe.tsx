import { Head, router, useForm } from '@inertiajs/react';
import { NIVEAU_CHIP, NIVEAU_LABEL } from '@/lib/niveau-droit';
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
type Domaine = { value: string; label: string };
type Niveau = { value: string; label: string };
type MatriceRole = {
    modifiable: boolean;
    personnalises: string[];
    role: string;
    roleLabel: string;
    droits: Record<string, string>;
};

type Props = {
    membres: Membre[];
    roles: Role[];
    domaines: Domaine[];
    niveaux: Niveau[];
    matriceRoles: MatriceRole[];
};

export default function AdminEquipe({
    membres,
    roles,
    domaines,
    niveaux,
    matriceRoles,
}: Props) {
    const changerDroit = (role: string, donnee: string, niveau: string) => {
        router.put(
            equipeAdmin.droits.update().url,
            { role, donnee, niveau },
            { preserveScroll: true },
        );
    };

    const reinitialiserDroits = (matrice: MatriceRole) => {
        if (
            !confirm(
                `Remettre les droits de « ${matrice.roleLabel} » à leurs valeurs par défaut ?`,
            )
        ) {
            return;
        }
        router.delete(equipeAdmin.droits.reset(matrice.role).url, {
            preserveScroll: true,
        });
    };

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

            <div
                className="espace-hero"
                style={{
                    backgroundImage:
                        "url('/images/photos/team-meeting-laptop.jpg')",
                }}
            >
                <div className="espace-hero-overlay" />
                <div className="espace-hero-content">
                    <h1>Équipe DAG</h1>
                    <p>
                        Seule la Promotrice peut créer un compte ou changer le
                        rôle d'un membre. Désactiver un compte lui retire
                        immédiatement l'accès.
                    </p>
                </div>
            </div>

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
                    Créer un compte membre de l'équipe
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
                        <label htmlFor="name">Nom</label>
                        <input
                            id="name"
                            value={createForm.data.name}
                            onChange={(event) =>
                                createForm.setData('name', event.target.value)
                            }
                        />
                        {createForm.errors.name && (
                            <p
                                style={{
                                    color: 'var(--dag-red)',
                                    fontSize: 12,
                                    marginTop: 4,
                                }}
                            >
                                {createForm.errors.name}
                            </p>
                        )}
                    </div>
                    <div>
                        <label htmlFor="email">E-mail</label>
                        <input
                            id="email"
                            type="email"
                            value={createForm.data.email}
                            onChange={(event) =>
                                createForm.setData('email', event.target.value)
                            }
                        />
                        {createForm.errors.email && (
                            <p
                                style={{
                                    color: 'var(--dag-red)',
                                    fontSize: 12,
                                    marginTop: 4,
                                }}
                            >
                                {createForm.errors.email}
                            </p>
                        )}
                    </div>
                    <div>
                        <label htmlFor="password">Mot de passe</label>
                        <input
                            id="password"
                            type="password"
                            value={createForm.data.password}
                            onChange={(event) =>
                                createForm.setData(
                                    'password',
                                    event.target.value,
                                )
                            }
                        />
                        {createForm.errors.password && (
                            <p
                                style={{
                                    color: 'var(--dag-red)',
                                    fontSize: 12,
                                    marginTop: 4,
                                }}
                            >
                                {createForm.errors.password}
                            </p>
                        )}
                    </div>
                    <div>
                        <label htmlFor="password_confirmation">
                            Confirmer le mot de passe
                        </label>
                        <input
                            id="password_confirmation"
                            type="password"
                            value={createForm.data.password_confirmation}
                            onChange={(event) =>
                                createForm.setData(
                                    'password_confirmation',
                                    event.target.value,
                                )
                            }
                        />
                    </div>
                    <div>
                        <label htmlFor="role">Rôle</label>
                        <select
                            id="role"
                            value={createForm.data.role}
                            onChange={(event) =>
                                createForm.setData('role', event.target.value)
                            }
                        >
                            {roles.map((role) => (
                                <option key={role.value} value={role.value}>
                                    {role.label}
                                </option>
                            ))}
                        </select>
                        {createForm.errors.role && (
                            <p
                                style={{
                                    color: 'var(--dag-red)',
                                    fontSize: 12,
                                    marginTop: 4,
                                }}
                            >
                                {createForm.errors.role}
                            </p>
                        )}
                    </div>
                </div>

                <DroitsPreview
                    domaines={domaines}
                    matrice={matriceRoles}
                    role={createForm.data.role}
                />

                <button
                    type="submit"
                    disabled={createForm.processing}
                    className="btn-primary"
                    style={{ alignSelf: 'flex-start' }}
                >
                    {createForm.processing ? 'Création…' : 'Créer le compte'}
                </button>
            </form>

            <div className="card" style={{ padding: 24 }}>
                <p
                    style={{
                        fontSize: 14.5,
                        fontWeight: 700,
                        marginBottom: 16,
                    }}
                >
                    Membres de l'équipe ({membres.length})
                </p>
                <div className="data-table-wrap">
                    <table className="data-table">
                        <thead>
                            <tr>
                                <th>Membre</th>
                                <th>Rôle</th>
                                <th>Statut</th>
                                <th />
                            </tr>
                        </thead>
                        <tbody>
                            {membres.map((membre) => (
                                <tr key={membre.id}>
                                    <td>
                                        <p style={{ fontWeight: 600 }}>
                                            {membre.nom}
                                            {membre.soiMeme && (
                                                <span
                                                    className="muted"
                                                    style={{
                                                        marginLeft: 8,
                                                        fontSize: 11.5,
                                                        fontWeight: 400,
                                                    }}
                                                >
                                                    (vous)
                                                </span>
                                            )}
                                        </p>
                                        <p
                                            className="muted"
                                            style={{ fontSize: 12.5 }}
                                        >
                                            {membre.email}
                                        </p>
                                    </td>
                                    <td>
                                        {membre.soiMeme ? (
                                            <span
                                                className="muted"
                                                title="Vous ne pouvez pas modifier votre propre rôle"
                                            >
                                                {membre.roleLabel}
                                            </span>
                                        ) : (
                                            <select
                                                value={membre.role}
                                                style={{
                                                    padding: '6px 10px',
                                                    fontSize: 12.5,
                                                }}
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
                                        )}
                                    </td>
                                    <td>
                                        <span
                                            className={`chip ${membre.statut === 'ACTIF' ? 'chip-green' : 'chip-gray'}`}
                                        >
                                            {membre.statut === 'ACTIF'
                                                ? 'Actif'
                                                : 'Inactif'}
                                        </span>
                                    </td>
                                    <td style={{ textAlign: 'right' }}>
                                        {!membre.soiMeme && (
                                            <button
                                                type="button"
                                                onClick={() =>
                                                    basculerStatut(membre)
                                                }
                                                className="link-action"
                                                style={
                                                    membre.statut === 'ACTIF'
                                                        ? {
                                                              color: 'var(--dag-red)',
                                                          }
                                                        : undefined
                                                }
                                            >
                                                {membre.statut === 'ACTIF'
                                                    ? 'Désactiver'
                                                    : 'Réactiver'}
                                            </button>
                                        )}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>

            <div className="card" style={{ padding: 24, marginTop: 28 }}>
                <p style={{ fontSize: 14.5, fontWeight: 700 }}>
                    Droits par rôle
                </p>
                <p
                    className="muted"
                    style={{ fontSize: 12.5, marginTop: 4, marginBottom: 18 }}
                >
                    Le rôle d'un membre détermine ce qu'il peut voir et gérer :
                    changer son rôle change ses droits immédiatement. Ajustez
                    ici ce que chaque rôle autorise, domaine par domaine ; les
                    cellules orange s'écartent des valeurs par défaut du cahier
                    des charges. Les droits de la Directrice sont fixes.
                </p>
                <div className="data-table-wrap">
                    <table className="data-table">
                        <thead>
                            <tr>
                                <th>Domaine</th>
                                {matriceRoles.map((matrice) => (
                                    <th key={matrice.role}>
                                        {matrice.roleLabel}
                                    </th>
                                ))}
                            </tr>
                        </thead>
                        <tbody>
                            {domaines.map((domaine) => (
                                <tr key={domaine.value}>
                                    <td style={{ fontWeight: 600 }}>
                                        {domaine.label}
                                    </td>
                                    {matriceRoles.map((matrice) => {
                                        const niveau =
                                            matrice.droits[domaine.value] ??
                                            'AUCUN';
                                        const personnalise =
                                            matrice.personnalises.includes(
                                                domaine.value,
                                            );

                                        return (
                                            <td key={matrice.role}>
                                                {matrice.modifiable ? (
                                                    <select
                                                        value={niveau}
                                                        aria-label={`${matrice.roleLabel} — ${domaine.label}`}
                                                        style={{
                                                            padding: '6px 10px',
                                                            fontSize: 12.5,
                                                            borderColor:
                                                                personnalise
                                                                    ? 'var(--dag-orange)'
                                                                    : undefined,
                                                        }}
                                                        onChange={(event) =>
                                                            changerDroit(
                                                                matrice.role,
                                                                domaine.value,
                                                                event.target
                                                                    .value,
                                                            )
                                                        }
                                                    >
                                                        {niveaux.map((n) => (
                                                            <option
                                                                key={n.value}
                                                                value={n.value}
                                                            >
                                                                {n.label}
                                                            </option>
                                                        ))}
                                                    </select>
                                                ) : (
                                                    <span
                                                        className={`chip ${NIVEAU_CHIP[niveau] ?? 'chip-gray'}`}
                                                    >
                                                        {NIVEAU_LABEL[niveau] ??
                                                            niveau}
                                                    </span>
                                                )}
                                            </td>
                                        );
                                    })}
                                </tr>
                            ))}
                            <tr>
                                <td />
                                {matriceRoles.map((matrice) => (
                                    <td key={matrice.role}>
                                        {matrice.modifiable &&
                                            matrice.personnalises.length >
                                                0 && (
                                                <button
                                                    type="button"
                                                    className="link-action"
                                                    onClick={() =>
                                                        reinitialiserDroits(
                                                            matrice,
                                                        )
                                                    }
                                                >
                                                    Réinitialiser
                                                </button>
                                            )}
                                    </td>
                                ))}
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </>
    );
}

function DroitsPreview({
    domaines,
    matrice,
    role,
}: {
    domaines: Domaine[];
    matrice: MatriceRole[];
    role: string;
}) {
    const droits = matrice.find((item) => item.role === role)?.droits ?? {};

    return (
        <div
            style={{
                background: '#f6f7fb',
                borderRadius: 'var(--dag-radius-sm)',
                padding: 14,
                display: 'flex',
                flexDirection: 'column',
                gap: 10,
            }}
        >
            <span className="muted" style={{ fontSize: 12 }}>
                Accès accordés par ce rôle :
            </span>
            <div style={{ display: 'flex', flexWrap: 'wrap', gap: 8 }}>
                {domaines.map((domaine) => {
                    const niveau = droits[domaine.value] ?? 'AUCUN';

                    return (
                        <span
                            key={domaine.value}
                            className={`chip ${NIVEAU_CHIP[niveau] ?? 'chip-gray'}`}
                            style={{ fontSize: 11.5 }}
                        >
                            {domaine.label} · {NIVEAU_LABEL[niveau] ?? niveau}
                        </span>
                    );
                })}
            </div>
        </div>
    );
}
