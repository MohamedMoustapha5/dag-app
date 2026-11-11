import { Head, Link, router, useForm } from '@inertiajs/react';
import { STATUT_CHIP, STATUT_LABEL } from '@/lib/demande-statut';
import formationsAdmin from '@/routes/espace-admin/formations';
import seancesAdmin from '@/routes/espace-admin/seances';
import sessionsAdmin from '@/routes/espace-admin/sessions';
import ChampFraisInscription from '@/components/espaces/champ-frais-inscription';

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
    prix: string | null;
    statut: string;
    image: string | null;
};

type ResumePresence = {
    etat: 'a_venir' | 'a_pointer' | 'incomplete' | 'pointee';
    pointes: number;
    inscrits: number;
    presents: number;
    absents: number;
};

type Seance = {
    id: string;
    titre: string | null;
    date: string;
    presence: ResumePresence;
};

type Assiduite = {
    inscriptionId: string;
    nom: string;
    presents: number;
    retards: number;
    absents: number;
    excuses: number;
    pointees: number;
    taux: number | null;
};

type Props = {
    session: Session;
    seances: Seance[];
    assiduite: Assiduite[];
    peutModifier: boolean;
    peutCreer: boolean;
    peutPointer: boolean;
    peutTelecharger: boolean;
};

function EtatPresence({ presence }: { presence: ResumePresence }) {
    if (presence.etat === 'a_venir') {
        return <span className="chip chip-gray">À venir</span>;
    }

    if (presence.inscrits === 0) {
        return <span className="muted">Aucun apprenant confirmé</span>;
    }

    if (presence.etat === 'a_pointer') {
        return <span className="chip chip-orange">À pointer</span>;
    }

    if (presence.etat === 'incomplete') {
        return (
            <span className="chip chip-orange">
                {presence.pointes}/{presence.inscrits} pointés
            </span>
        );
    }

    return (
        <span className="chip chip-green">
            {presence.presents}/{presence.inscrits} présents
        </span>
    );
}

function couleurTaux(taux: number): string {
    if (taux >= 80) {
        return 'var(--dag-green)';
    }

    return taux >= 50 ? 'var(--dag-orange)' : 'var(--dag-red)';
}

export default function AdminSessionShow({
    session,
    seances,
    assiduite,
    peutModifier,
    peutCreer,
    peutPointer,
    peutTelecharger,
}: Props) {
    const aPointer = seances.filter(
        (seance) =>
            seance.presence.inscrits > 0 &&
            (seance.presence.etat === 'a_pointer' ||
                seance.presence.etat === 'incomplete'),
    ).length;
    const editForm = useForm<{
        ville: string;
        mode: string;
        lieu: string;
        formateur: string;
        date_debut: string;
        date_fin: string;
        places: number;
        prix: string;
        statut: string;
        image: File | null;
    }>({
        ville: session.ville,
        mode: session.mode,
        lieu: session.lieu ?? '',
        formateur: session.formateur ?? '',
        date_debut: session.dateDebut ?? '',
        date_fin: session.dateFin ?? '',
        places: session.places,
        prix: session.prix ? String(Math.round(Number(session.prix))) : '',
        statut: session.statut,
        image: null,
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
                        <ChampFraisInscription
                            id="prix"
                            valeur={editForm.data.prix}
                            erreur={editForm.errors.prix}
                            onChange={(valeur) =>
                                editForm.setData('prix', valeur)
                            }
                        />
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
                        <div style={{ gridColumn: '1 / -1' }}>
                            <label htmlFor="image">Image (optionnel)</label>
                            <input
                                id="image"
                                type="file"
                                accept="image/jpeg,image/png,image/webp"
                                onChange={(event) =>
                                    editForm.setData(
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
                                {session.image
                                    ? ' Laissez vide pour conserver l’image actuelle.'
                                    : ''}
                            </p>
                            {session.image && (
                                <img
                                    src={session.image}
                                    alt=""
                                    style={{
                                        marginTop: 10,
                                        width: '100%',
                                        maxWidth: 240,
                                        height: 100,
                                        objectFit: 'cover',
                                        borderRadius: 'var(--dag-radius-sm)',
                                    }}
                                />
                            )}
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
                <div
                    style={{
                        display: 'flex',
                        justifyContent: 'space-between',
                        alignItems: 'center',
                        gap: 12,
                        flexWrap: 'wrap',
                        marginBottom: 16,
                    }}
                >
                    <div>
                        <p style={{ fontSize: 14.5, fontWeight: 700 }}>
                            Séances et présence ({seances.length})
                        </p>
                        {aPointer > 0 && (
                            <p
                                style={{
                                    fontSize: 12.5,
                                    color: 'var(--dag-orange-ink)',
                                    marginTop: 2,
                                }}
                            >
                                {aPointer} séance{aPointer > 1 ? 's' : ''}{' '}
                                passée{aPointer > 1 ? 's' : ''} à pointer
                            </p>
                        )}
                    </div>
                    {peutTelecharger && seances.length > 0 && (
                        <a
                            href={
                                sessionsAdmin.presences.export(session.id).url
                            }
                            className="btn-outline"
                            style={{ fontSize: 13, padding: '8px 14px' }}
                        >
                            Exporter la feuille de présence
                        </a>
                    )}
                </div>
                {seances.length === 0 ? (
                    <p className="data-table-empty">
                        Aucune séance programmée.
                    </p>
                ) : (
                    <div className="data-table-wrap">
                        <table className="data-table cartes-mobile">
                            <thead>
                                <tr>
                                    <th>Titre</th>
                                    <th>Date</th>
                                    <th>Présence</th>
                                    <th />
                                </tr>
                            </thead>
                            <tbody>
                                {seances.map((seance, index) => {
                                    const aFaire =
                                        peutPointer &&
                                        seance.presence.inscrits > 0 &&
                                        (seance.presence.etat === 'a_pointer' ||
                                            seance.presence.etat ===
                                                'incomplete');

                                    return (
                                        <tr key={seance.id}>
                                            <td
                                                className="cellule-titre"
                                                data-label="Titre"
                                                style={{ fontWeight: 600 }}
                                            >
                                                {seance.titre ??
                                                    `Séance ${index + 1}`}
                                            </td>
                                            <td
                                                className="muted"
                                                data-label="Date"
                                            >
                                                {new Date(
                                                    `${seance.date}T00:00:00`,
                                                ).toLocaleDateString('fr-FR', {
                                                    weekday: 'short',
                                                    day: 'numeric',
                                                    month: 'short',
                                                    year: 'numeric',
                                                })}
                                            </td>
                                            <td data-label="Présence">
                                                <EtatPresence
                                                    presence={seance.presence}
                                                />
                                            </td>
                                            <td
                                                data-label=""
                                                className="cellule-actions"
                                                style={{ textAlign: 'right' }}
                                            >
                                                <div
                                                    style={{
                                                        display: 'inline-flex',
                                                        gap: 14,
                                                        alignItems: 'center',
                                                    }}
                                                >
                                                    {seance.presence.etat !==
                                                        'a_venir' &&
                                                        seance.presence
                                                            .inscrits > 0 && (
                                                            <Link
                                                                href={seancesAdmin.presences.show(
                                                                    seance.id,
                                                                )}
                                                                className={
                                                                    aFaire
                                                                        ? 'btn-primary'
                                                                        : 'link-action'
                                                                }
                                                                style={
                                                                    aFaire
                                                                        ? {
                                                                              fontSize: 13,
                                                                              padding:
                                                                                  '7px 14px',
                                                                          }
                                                                        : undefined
                                                                }
                                                            >
                                                                {aFaire
                                                                    ? 'Pointer'
                                                                    : 'Voir la présence'}
                                                            </Link>
                                                        )}
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
                                                </div>
                                            </td>
                                        </tr>
                                    );
                                })}
                            </tbody>
                        </table>
                    </div>
                )}
            </div>

            {assiduite.length > 0 && seances.length > 0 && (
                <div className="card" style={{ padding: 24, marginBottom: 28 }}>
                    <p style={{ fontSize: 14.5, fontWeight: 700 }}>
                        Assiduité des apprenants
                    </p>
                    <p
                        className="muted"
                        style={{ fontSize: 12.5, margin: '2px 0 16px' }}
                    >
                        Calculée sur les séances déjà pointées : présent ou en
                        retard compte comme présent.
                    </p>
                    <div className="data-table-wrap">
                        <table className="data-table cartes-mobile">
                            <thead>
                                <tr>
                                    <th>Apprenant</th>
                                    <th>Présences</th>
                                    <th>Retards</th>
                                    <th>Absences</th>
                                    <th>Assiduité</th>
                                </tr>
                            </thead>
                            <tbody>
                                {assiduite.map((ligne) => (
                                    <tr key={ligne.inscriptionId}>
                                        <td
                                            className="cellule-titre"
                                            data-label="Apprenant"
                                            style={{ fontWeight: 600 }}
                                        >
                                            {ligne.nom}
                                        </td>
                                        <td data-label="Présences">
                                            {ligne.presents + ligne.retards}/
                                            {ligne.pointees}
                                        </td>
                                        <td data-label="Retards">
                                            {ligne.retards}
                                        </td>
                                        <td data-label="Absences">
                                            {ligne.absents + ligne.excuses}
                                            {ligne.excuses > 0 && (
                                                <span className="muted">
                                                    {' '}
                                                    (dont {ligne.excuses}{' '}
                                                    excusée
                                                    {ligne.excuses > 1
                                                        ? 's'
                                                        : ''}
                                                    )
                                                </span>
                                            )}
                                        </td>
                                        <td data-label="Assiduité">
                                            {ligne.taux === null ? (
                                                <span className="muted">
                                                    Pas encore pointé
                                                </span>
                                            ) : (
                                                <span
                                                    style={{
                                                        fontWeight: 700,
                                                        color: couleurTaux(
                                                            ligne.taux,
                                                        ),
                                                    }}
                                                >
                                                    <span
                                                        className="assiduite-barre"
                                                        aria-hidden="true"
                                                    >
                                                        <span
                                                            style={{
                                                                width: `${ligne.taux}%`,
                                                                background:
                                                                    couleurTaux(
                                                                        ligne.taux,
                                                                    ),
                                                            }}
                                                        />
                                                    </span>
                                                    {ligne.taux} %
                                                </span>
                                            )}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>
            )}

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
