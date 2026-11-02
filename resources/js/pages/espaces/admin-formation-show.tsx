import { Head, Link, useForm } from '@inertiajs/react';
import { STATUT_CHIP, STATUT_LABEL } from '@/lib/demande-statut';
import formationsAdmin from '@/routes/espace-admin/formations';
import sessionsAdmin from '@/routes/espace-admin/sessions';

type Formation = {
    id: string;
    nom: string;
    description: string;
    categorie: string | null;
    duree: string | null;
    prerequis: string | null;
    statut: string;
};

type Session = {
    id: string;
    ville: string;
    mode: string;
    dateDebut: string | null;
    dateFin: string | null;
    places: number;
    placesRestantes: number;
    statut: string;
    seancesCount: number;
    image: string | null;
};

type Props = {
    formation: Formation;
    sessions: Session[];
    peutModifier: boolean;
    peutCreer: boolean;
};

export default function AdminFormationShow({
    formation,
    sessions,
    peutModifier,
    peutCreer,
}: Props) {
    const editForm = useForm({
        nom: formation.nom,
        description: formation.description,
        categorie: formation.categorie ?? '',
        duree: formation.duree ?? '',
        prerequis: formation.prerequis ?? '',
        statut: formation.statut,
    });

    const sessionForm = useForm<{
        ville: string;
        mode: string;
        lieu: string;
        formateur: string;
        date_debut: string;
        date_fin: string;
        places: number;
        image: File | null;
    }>({
        ville: '',
        mode: 'PRESENTIEL',
        lieu: '',
        formateur: '',
        date_debut: '',
        date_fin: '',
        places: 15,
        image: null,
    });

    const submitEdit = (event: React.FormEvent) => {
        event.preventDefault();
        editForm.patch(formationsAdmin.update(formation.id).url, {
            preserveScroll: true,
        });
    };

    const submitSession = (event: React.FormEvent) => {
        event.preventDefault();
        sessionForm.post(formationsAdmin.sessions.store(formation.id).url, {
            preserveScroll: true,
            onSuccess: () => sessionForm.reset(),
        });
    };

    return (
        <>
            <Head title={`${formation.nom} — Administration`} />

            <Link
                href={formationsAdmin.index()}
                className="link-action"
                style={{ marginBottom: 16, display: 'inline-block' }}
            >
                ← Toutes les formations
            </Link>

            <div
                style={{
                    display: 'flex',
                    justifyContent: 'space-between',
                    alignItems: 'center',
                    gap: 16,
                    flexWrap: 'wrap',
                    marginBottom: 24,
                }}
            >
                <h1 style={{ fontSize: 22, color: 'var(--dag-navy)' }}>
                    {formation.nom}
                </h1>
                <span
                    className={`chip ${STATUT_CHIP[formation.statut] ?? 'chip-gray'}`}
                    style={{ fontSize: 13, padding: '8px 16px' }}
                >
                    {STATUT_LABEL[formation.statut] ?? formation.statut}
                </span>
            </div>

            {peutModifier && (
                <form
                    onSubmit={submitEdit}
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
                        Modifier la formation
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
                            <label htmlFor="edit-nom">Nom</label>
                            <input
                                id="edit-nom"
                                value={editForm.data.nom}
                                onChange={(event) =>
                                    editForm.setData('nom', event.target.value)
                                }
                            />
                        </div>
                        <div>
                            <label htmlFor="edit-categorie">Catégorie</label>
                            <input
                                id="edit-categorie"
                                value={editForm.data.categorie}
                                onChange={(event) =>
                                    editForm.setData(
                                        'categorie',
                                        event.target.value,
                                    )
                                }
                            />
                        </div>
                        <div>
                            <label htmlFor="edit-duree">Durée</label>
                            <input
                                id="edit-duree"
                                value={editForm.data.duree}
                                onChange={(event) =>
                                    editForm.setData(
                                        'duree',
                                        event.target.value,
                                    )
                                }
                            />
                        </div>
                        <div>
                            <label htmlFor="edit-statut">Statut</label>
                            <select
                                id="edit-statut"
                                value={editForm.data.statut}
                                onChange={(event) =>
                                    editForm.setData(
                                        'statut',
                                        event.target.value,
                                    )
                                }
                            >
                                <option value="BROUILLON">Brouillon</option>
                                <option value="PUBLIEE">Publiée</option>
                                <option value="EN_COURS">En cours</option>
                                <option value="TERMINEE">Terminée</option>
                            </select>
                        </div>
                        <div style={{ gridColumn: '1 / -1' }}>
                            <label htmlFor="edit-prerequis">Prérequis</label>
                            <input
                                id="edit-prerequis"
                                value={editForm.data.prerequis}
                                onChange={(event) =>
                                    editForm.setData(
                                        'prerequis',
                                        event.target.value,
                                    )
                                }
                            />
                        </div>
                        <div style={{ gridColumn: '1 / -1' }}>
                            <label htmlFor="edit-description">
                                Description
                            </label>
                            <textarea
                                id="edit-description"
                                rows={3}
                                value={editForm.data.description}
                                onChange={(event) =>
                                    editForm.setData(
                                        'description',
                                        event.target.value,
                                    )
                                }
                            />
                        </div>
                    </div>
                    <button
                        type="submit"
                        disabled={editForm.processing}
                        className="btn-primary"
                        style={{ alignSelf: 'flex-start' }}
                    >
                        {editForm.processing
                            ? 'Enregistrement…'
                            : 'Enregistrer'}
                    </button>
                </form>
            )}

            <div className="card" style={{ padding: 24, marginBottom: 28 }}>
                <p
                    style={{
                        fontSize: 14.5,
                        fontWeight: 700,
                        marginBottom: 16,
                    }}
                >
                    Sessions ({sessions.length})
                </p>
                {sessions.length === 0 ? (
                    <p className="data-table-empty">
                        Aucune session pour cette formation.
                    </p>
                ) : (
                    <div className="data-table-wrap">
                        <table className="data-table">
                            <thead>
                                <tr>
                                    <th>Ville / mode</th>
                                    <th>Dates</th>
                                    <th>Places</th>
                                    <th>Séances</th>
                                    <th>Statut</th>
                                </tr>
                            </thead>
                            <tbody>
                                {sessions.map((session) => (
                                    <tr key={session.id}>
                                        <td>
                                            <Link
                                                href={sessionsAdmin.show(
                                                    session.id,
                                                )}
                                                className="link-action"
                                            >
                                                {session.ville}
                                            </Link>
                                            <p
                                                className="muted"
                                                style={{ fontSize: 12 }}
                                            >
                                                {session.mode}
                                            </p>
                                        </td>
                                        <td className="muted">
                                            {session.dateDebut ?? '—'}
                                            {session.dateFin
                                                ? ` au ${session.dateFin}`
                                                : ''}
                                        </td>
                                        <td className="muted">
                                            {session.placesRestantes} /{' '}
                                            {session.places}
                                        </td>
                                        <td className="muted">
                                            {session.seancesCount}
                                        </td>
                                        <td>
                                            <span
                                                className={`chip ${STATUT_CHIP[session.statut] ?? 'chip-gray'}`}
                                            >
                                                {STATUT_LABEL[session.statut] ??
                                                    session.statut}
                                            </span>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </div>

            {peutCreer && (
                <form
                    onSubmit={submitSession}
                    className="card"
                    style={{
                        padding: 24,
                        display: 'flex',
                        flexDirection: 'column',
                        gap: 14,
                    }}
                >
                    <p style={{ fontSize: 14.5, fontWeight: 700 }}>
                        Ajouter une session
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
                            <label htmlFor="session-ville">Ville</label>
                            <input
                                id="session-ville"
                                value={sessionForm.data.ville}
                                onChange={(event) =>
                                    sessionForm.setData(
                                        'ville',
                                        event.target.value,
                                    )
                                }
                            />
                            {sessionForm.errors.ville && (
                                <p
                                    style={{
                                        color: 'var(--dag-red)',
                                        fontSize: 12,
                                        marginTop: 4,
                                    }}
                                >
                                    {sessionForm.errors.ville}
                                </p>
                            )}
                        </div>
                        <div>
                            <label htmlFor="session-mode">Mode</label>
                            <select
                                id="session-mode"
                                value={sessionForm.data.mode}
                                onChange={(event) =>
                                    sessionForm.setData(
                                        'mode',
                                        event.target.value,
                                    )
                                }
                            >
                                <option value="PRESENTIEL">Présentiel</option>
                                <option value="EN_LIGNE">En ligne</option>
                                <option value="LES_DEUX">Les deux</option>
                            </select>
                        </div>
                        <div>
                            <label htmlFor="session-lieu">Lieu</label>
                            <input
                                id="session-lieu"
                                value={sessionForm.data.lieu}
                                onChange={(event) =>
                                    sessionForm.setData(
                                        'lieu',
                                        event.target.value,
                                    )
                                }
                            />
                        </div>
                        <div>
                            <label htmlFor="session-formateur">Formateur</label>
                            <input
                                id="session-formateur"
                                value={sessionForm.data.formateur}
                                onChange={(event) =>
                                    sessionForm.setData(
                                        'formateur',
                                        event.target.value,
                                    )
                                }
                            />
                        </div>
                        <div>
                            <label htmlFor="session-date-debut">
                                Date de début
                            </label>
                            <input
                                id="session-date-debut"
                                type="date"
                                value={sessionForm.data.date_debut}
                                onChange={(event) =>
                                    sessionForm.setData(
                                        'date_debut',
                                        event.target.value,
                                    )
                                }
                            />
                        </div>
                        <div>
                            <label htmlFor="session-date-fin">
                                Date de fin
                            </label>
                            <input
                                id="session-date-fin"
                                type="date"
                                value={sessionForm.data.date_fin}
                                onChange={(event) =>
                                    sessionForm.setData(
                                        'date_fin',
                                        event.target.value,
                                    )
                                }
                            />
                            {sessionForm.errors.date_fin && (
                                <p
                                    style={{
                                        color: 'var(--dag-red)',
                                        fontSize: 12,
                                        marginTop: 4,
                                    }}
                                >
                                    {sessionForm.errors.date_fin}
                                </p>
                            )}
                        </div>
                        <div>
                            <label htmlFor="session-places">Places</label>
                            <input
                                id="session-places"
                                type="number"
                                min={1}
                                value={sessionForm.data.places}
                                onChange={(event) =>
                                    sessionForm.setData(
                                        'places',
                                        Number(event.target.value),
                                    )
                                }
                            />
                        </div>
                        <div style={{ gridColumn: '1 / -1' }}>
                            <label htmlFor="session-image">
                                Image (optionnel)
                            </label>
                            <input
                                id="session-image"
                                type="file"
                                accept="image/jpeg,image/png,image/webp"
                                onChange={(event) =>
                                    sessionForm.setData(
                                        'image',
                                        event.target.files?.[0] ?? null,
                                    )
                                }
                            />
                            <p
                                className="muted"
                                style={{ fontSize: 11.5, marginTop: 4 }}
                            >
                                JPG, PNG ou WebP — 5 Mo maximum.
                            </p>
                        </div>
                    </div>
                    <button
                        type="submit"
                        disabled={sessionForm.processing}
                        className="btn-primary"
                        style={{ alignSelf: 'flex-start' }}
                    >
                        {sessionForm.processing
                            ? 'Création…'
                            : 'Créer la session'}
                    </button>
                </form>
            )}
        </>
    );
}
