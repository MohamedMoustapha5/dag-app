import { Head, Link, router, useForm } from '@inertiajs/react';
import { STATUT_CHIP, STATUT_LABEL } from '@/lib/demande-statut';
import formationsAdmin from '@/routes/espace-admin/formations';
import seancesAdmin from '@/routes/espace-admin/seances';
import sessionsAdmin from '@/routes/espace-admin/sessions';

type Session = {
    id: string;
    formationId: string;
    formationNom: string;
    ville: string;
    mode: string;
    lieu: string | null;
    formateur: string | null;
    dateDebut: string | null;
    dateFin: string | null;
    places: number;
    statut: string;
};

type Seance = {
    id: string;
    titre: string | null;
    date: string;
};

type Props = {
    session: Session;
    seances: Seance[];
    peutModifier: boolean;
    peutCreer: boolean;
};

export default function AdminSessionShow({
    session,
    seances,
    peutModifier,
    peutCreer,
}: Props) {
    const editForm = useForm({
        ville: session.ville,
        mode: session.mode,
        lieu: session.lieu ?? '',
        formateur: session.formateur ?? '',
        date_debut: session.dateDebut ?? '',
        date_fin: session.dateFin ?? '',
        places: session.places,
        statut: session.statut,
    });

    const seanceForm = useForm({
        titre: '',
        date: '',
    });

    const submitEdit = (event: React.FormEvent) => {
        event.preventDefault();
        editForm.patch(sessionsAdmin.update(session.id).url, {
            preserveScroll: true,
        });
    };

    const submitSeance = (event: React.FormEvent) => {
        event.preventDefault();
        seanceForm.post(sessionsAdmin.seances.store(session.id).url, {
            preserveScroll: true,
            onSuccess: () => seanceForm.reset(),
        });
    };

    const supprimerSeance = (seanceId: string) => {
        if (!confirm('Supprimer cette séance ?')) {
            return;
        }
        router.delete(seancesAdmin.destroy(seanceId).url, {
            preserveScroll: true,
        });
    };

    return (
        <>
            <Head title={`${session.ville} — Session — Administration`} />

            <Link
                href={formationsAdmin.show(session.formationId)}
                className="link-action"
                style={{ marginBottom: 16, display: 'inline-block' }}
            >
                ← {session.formationNom}
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
                    Session — {session.ville}
                </h1>
                <span
                    className={`chip ${STATUT_CHIP[session.statut] ?? 'chip-gray'}`}
                    style={{ fontSize: 13, padding: '8px 16px' }}
                >
                    {STATUT_LABEL[session.statut] ?? session.statut}
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
                        Modifier la session
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
                            <label htmlFor="ville">Ville</label>
                            <input
                                id="ville"
                                value={editForm.data.ville}
                                onChange={(event) =>
                                    editForm.setData(
                                        'ville',
                                        event.target.value,
                                    )
                                }
                            />
                        </div>
                        <div>
                            <label htmlFor="mode">Mode</label>
                            <select
                                id="mode"
                                value={editForm.data.mode}
                                onChange={(event) =>
                                    editForm.setData('mode', event.target.value)
                                }
                            >
                                <option value="PRESENTIEL">Présentiel</option>
                                <option value="EN_LIGNE">En ligne</option>
                                <option value="LES_DEUX">Les deux</option>
                            </select>
                        </div>
                        <div>
                            <label htmlFor="lieu">Lieu</label>
                            <input
                                id="lieu"
                                value={editForm.data.lieu}
                                onChange={(event) =>
                                    editForm.setData('lieu', event.target.value)
                                }
                            />
                        </div>
                        <div>
                            <label htmlFor="formateur">Formateur</label>
                            <input
                                id="formateur"
                                value={editForm.data.formateur}
                                onChange={(event) =>
                                    editForm.setData(
                                        'formateur',
                                        event.target.value,
                                    )
                                }
                            />
                        </div>
                        <div>
                            <label htmlFor="date_debut">Date de début</label>
                            <input
                                id="date_debut"
                                type="date"
                                value={editForm.data.date_debut}
                                onChange={(event) =>
                                    editForm.setData(
                                        'date_debut',
                                        event.target.value,
                                    )
                                }
                            />
                        </div>
                        <div>
                            <label htmlFor="date_fin">Date de fin</label>
                            <input
                                id="date_fin"
                                type="date"
                                value={editForm.data.date_fin}
                                onChange={(event) =>
                                    editForm.setData(
                                        'date_fin',
                                        event.target.value,
                                    )
                                }
                            />
                        </div>
                        <div>
                            <label htmlFor="places">Places</label>
                            <input
                                id="places"
                                type="number"
                                min={1}
                                value={editForm.data.places}
                                onChange={(event) =>
                                    editForm.setData(
                                        'places',
                                        Number(event.target.value),
                                    )
                                }
                            />
                        </div>
                        <div>
                            <label htmlFor="statut">Statut</label>
                            <select
                                id="statut"
                                value={editForm.data.statut}
                                onChange={(event) =>
                                    editForm.setData(
                                        'statut',
                                        event.target.value,
                                    )
                                }
                            >
                                <option value="BROUILLON">Brouillon</option>
                                <option value="OUVERTE">Ouverte</option>
                                <option value="COMPLETE">Complète</option>
                                <option value="TERMINEE">Terminée</option>
                            </select>
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
                    Séances ({seances.length})
                </p>
                {seances.length === 0 ? (
                    <p className="data-table-empty">
                        Aucune séance programmée.
                    </p>
                ) : (
                    <div className="data-table-wrap">
                        <table className="data-table">
                            <thead>
                                <tr>
                                    <th>Titre</th>
                                    <th>Date</th>
                                    <th />
                                </tr>
                            </thead>
                            <tbody>
                                {seances.map((seance) => (
                                    <tr key={seance.id}>
                                        <td style={{ fontWeight: 600 }}>
                                            {seance.titre ?? '—'}
                                        </td>
                                        <td className="muted">{seance.date}</td>
                                        <td style={{ textAlign: 'right' }}>
                                            {peutModifier && (
                                                <button
                                                    type="button"
                                                    className="link-action"
                                                    style={{
                                                        color: 'var(--dag-red)',
                                                    }}
                                                    onClick={() =>
                                                        supprimerSeance(
                                                            seance.id,
                                                        )
                                                    }
                                                >
                                                    Supprimer
                                                </button>
                                            )}
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
                    onSubmit={submitSeance}
                    className="card"
                    style={{
                        padding: 24,
                        display: 'flex',
                        flexWrap: 'wrap',
                        alignItems: 'flex-end',
                        gap: 14,
                    }}
                >
                    <div style={{ flex: 1, minWidth: 180 }}>
                        <label htmlFor="seance-titre">Titre (optionnel)</label>
                        <input
                            id="seance-titre"
                            placeholder="Ex. Séance 1"
                            value={seanceForm.data.titre}
                            onChange={(event) =>
                                seanceForm.setData('titre', event.target.value)
                            }
                        />
                    </div>
                    <div style={{ flex: 1, minWidth: 180 }}>
                        <label htmlFor="seance-date">Date</label>
                        <input
                            id="seance-date"
                            type="date"
                            value={seanceForm.data.date}
                            onChange={(event) =>
                                seanceForm.setData('date', event.target.value)
                            }
                        />
                        {seanceForm.errors.date && (
                            <p
                                style={{
                                    color: 'var(--dag-red)',
                                    fontSize: 12,
                                    marginTop: 4,
                                }}
                            >
                                {seanceForm.errors.date}
                            </p>
                        )}
                    </div>
                    <button
                        type="submit"
                        disabled={seanceForm.processing}
                        className="btn-primary"
                    >
                        {seanceForm.processing ? 'Ajout…' : 'Ajouter la séance'}
                    </button>
                </form>
            )}
        </>
    );
}
