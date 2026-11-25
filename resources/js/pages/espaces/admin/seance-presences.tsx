import { Head, Link, useForm } from '@inertiajs/react';
import {
    CalendarDays,
    CheckCheck,
    ChevronLeft,
    ChevronRight,
    Clock,
    MapPin,
    Search,
    UserRound,
} from 'lucide-react';
import { useMemo, useState } from 'react';
import seancesAdmin from '@/routes/espace-admin/seances';
import sessionsAdmin from '@/routes/espace-admin/sessions';

type StatutPresence = 'PRESENT' | 'RETARD' | 'ABSENT' | 'EXCUSE';

type Apprenant = {
    inscriptionId: string;
    nom: string;
    email: string;
    statut: StatutPresence | null;
    commentaire: string | null;
};

type SeanceVoisine = { id: string; titre: string; date: string } | null;

type Props = {
    session: {
        id: string;
        formationNom: string;
        ville: string;
        formateur: string | null;
        lieu: string | null;
    };
    seance: {
        id: string;
        titre: string;
        date: string;
        numero: number;
        total: number;
        pointable: boolean;
    };
    precedente: SeanceVoisine;
    suivante: SeanceVoisine;
    apprenants: Apprenant[];
    statuts: { valeur: StatutPresence; libelle: string }[];
    dernierPointage: { par: string | null; le: string | null } | null;
    peutPointer: boolean;
};

type Ligne = { statut: StatutPresence | null; commentaire: string };

const STATUTS_AVEC_REMARQUE: StatutPresence[] = ['RETARD', 'ABSENT', 'EXCUSE'];

function dateLongue(date: string): string {
    const [annee, mois, jour] = date.split('-').map(Number);

    return new Date(annee, mois - 1, jour).toLocaleDateString('fr-FR', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });
}

function dateCourte(date: string): string {
    const [annee, mois, jour] = date.split('-').map(Number);

    return new Date(annee, mois - 1, jour).toLocaleDateString('fr-FR', {
        day: 'numeric',
        month: 'short',
    });
}

function initiales(nom: string): string {
    return nom
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((mot) => mot[0]?.toUpperCase())
        .join('');
}

/**
 * The sheet is keyed by séance: moving to the previous or next séance starts
 * from what was recorded for it, never from the séance just left.
 */
export default function AdminSeancePresences(props: Props) {
    return <FeuillePresence key={props.seance.id} {...props} />;
}

function FeuillePresence({
    session,
    seance,
    precedente,
    suivante,
    apprenants,
    statuts,
    dernierPointage,
    peutPointer,
}: Props) {
    const modifiable = peutPointer && seance.pointable;
    const [recherche, setRecherche] = useState('');

    const initial = useMemo(
        () =>
            Object.fromEntries(
                apprenants.map((apprenant) => [
                    apprenant.inscriptionId,
                    {
                        statut: apprenant.statut,
                        commentaire: apprenant.commentaire ?? '',
                    },
                ]),
            ) as Record<string, Ligne>,
        [apprenants],
    );

    const form = useForm<{ lignes: Record<string, Ligne> }>({
        lignes: initial,
    });

    const lignes = Object.fromEntries(
        apprenants.map((apprenant) => [
            apprenant.inscriptionId,
            form.data.lignes[apprenant.inscriptionId] ??
                initial[apprenant.inscriptionId],
        ]),
    ) as Record<string, Ligne>;

    const modifications = apprenants.filter((apprenant) => {
        const avant = initial[apprenant.inscriptionId];
        const apres = lignes[apprenant.inscriptionId];

        return (
            avant.statut !== apres.statut ||
            avant.commentaire.trim() !== apres.commentaire.trim()
        );
    }).length;

    const compte = (statut: StatutPresence | null) =>
        apprenants.filter(
            (apprenant) => lignes[apprenant.inscriptionId]?.statut === statut,
        ).length;

    const nonPointes = compte(null);

    const visibles = apprenants.filter((apprenant) => {
        const terme = recherche.trim().toLowerCase();

        return (
            terme === '' ||
            apprenant.nom.toLowerCase().includes(terme) ||
            apprenant.email.toLowerCase().includes(terme)
        );
    });

    const changer = (inscriptionId: string, ligne: Partial<Ligne>) => {
        form.setData('lignes', {
            ...lignes,
            [inscriptionId]: { ...lignes[inscriptionId], ...ligne },
        });
    };

    const tousPresents = () => {
        form.setData(
            'lignes',
            Object.fromEntries(
                Object.entries(lignes).map(([id, ligne]) => [
                    id,
                    ligne.statut === null
                        ? { ...ligne, statut: 'PRESENT' as StatutPresence }
                        : ligne,
                ]),
            ),
        );
    };

    const enregistrer = (event: React.FormEvent) => {
        event.preventDefault();
        form.transform((data) => ({
            presences: Object.entries(data.lignes)
                .filter(([, ligne]) => ligne.statut !== null)
                .map(([inscriptionId, ligne]) => ({
                    inscription_id: inscriptionId,
                    statut: ligne.statut,
                    commentaire: ligne.commentaire.trim() || null,
                })),
        }));
        form.put(seancesAdmin.presences.update(seance.id).url, {
            preserveScroll: true,
        });
    };

    const erreurs = Object.values(form.errors);

    return (
        <>
            <Head
                title={`Présence — ${seance.titre} — ${session.formationNom} — Administration`}
            />

            <Link
                href={sessionsAdmin.show(session.id)}
                className="link-action"
                style={{ marginBottom: 16, display: 'inline-block' }}
            >
                ← Session {session.ville}
            </Link>

            <div className="feuille-presence-entete">
                <div>
                    <p className="feuille-presence-surtitre">
                        Feuille de présence · séance {seance.numero} sur{' '}
                        {seance.total}
                    </p>
                    <h1>{seance.titre}</h1>
                    <p className="feuille-presence-infos">
                        <span>
                            <CalendarDays size={14} aria-hidden="true" />
                            {dateLongue(seance.date)}
                        </span>
                        <span>{session.formationNom}</span>
                        {session.lieu && (
                            <span>
                                <MapPin size={14} aria-hidden="true" />
                                {session.lieu}
                            </span>
                        )}
                        {session.formateur && (
                            <span>
                                <UserRound size={14} aria-hidden="true" />
                                {session.formateur}
                            </span>
                        )}
                    </p>
                </div>
                <nav
                    className="feuille-presence-navigation"
                    aria-label="Autres séances"
                >
                    {precedente ? (
                        <Link
                            href={seancesAdmin.presences.show(precedente.id)}
                            className="btn-outline"
                            title={precedente.titre}
                        >
                            <ChevronLeft size={16} aria-hidden="true" />
                            {dateCourte(precedente.date)}
                        </Link>
                    ) : null}
                    {suivante ? (
                        <Link
                            href={seancesAdmin.presences.show(suivante.id)}
                            className="btn-outline"
                            title={suivante.titre}
                        >
                            {dateCourte(suivante.date)}
                            <ChevronRight size={16} aria-hidden="true" />
                        </Link>
                    ) : null}
                </nav>
            </div>

            {!seance.pointable && (
                <div className="feuille-presence-bandeau">
                    <Clock size={18} aria-hidden="true" />
                    <p>
                        Cette séance n’a pas encore eu lieu. La feuille de
                        présence s’ouvre le {dateLongue(seance.date)}.
                    </p>
                </div>
            )}

            {apprenants.length === 0 ? (
                <div className="card" style={{ padding: 28 }}>
                    <p style={{ fontWeight: 700, marginBottom: 6 }}>
                        Aucun apprenant à pointer
                    </p>
                    <p className="muted" style={{ fontSize: 13.5 }}>
                        Seuls les apprenants dont l’inscription est confirmée
                        (frais réglés) figurent sur la feuille de présence.
                    </p>
                </div>
            ) : (
                <form onSubmit={enregistrer}>
                    <div className="feuille-presence-compteurs">
                        <div className="present">
                            <strong>{compte('PRESENT')}</strong>
                            <span>Présents</span>
                        </div>
                        <div className="retard">
                            <strong>{compte('RETARD')}</strong>
                            <span>En retard</span>
                        </div>
                        <div className="absent">
                            <strong>{compte('ABSENT')}</strong>
                            <span>Absents</span>
                        </div>
                        <div className="excuse">
                            <strong>{compte('EXCUSE')}</strong>
                            <span>Excusés</span>
                        </div>
                        <div className="non-pointe">
                            <strong>{nonPointes}</strong>
                            <span>Non pointés</span>
                        </div>
                    </div>

                    <div className="card feuille-presence">
                        <div className="feuille-presence-outils">
                            {apprenants.length > 8 ? (
                                <label className="feuille-presence-recherche">
                                    <Search size={15} aria-hidden="true" />
                                    <span className="sr-only">
                                        Rechercher un apprenant
                                    </span>
                                    <input
                                        type="search"
                                        placeholder="Rechercher un apprenant"
                                        value={recherche}
                                        onChange={(event) =>
                                            setRecherche(event.target.value)
                                        }
                                    />
                                </label>
                            ) : (
                                <p className="muted" style={{ fontSize: 13 }}>
                                    {apprenants.length} apprenant
                                    {apprenants.length > 1 ? 's' : ''} sur la
                                    feuille
                                </p>
                            )}
                            {modifiable && nonPointes > 0 && (
                                <button
                                    type="button"
                                    className="btn-outline"
                                    onClick={tousPresents}
                                >
                                    <CheckCheck size={16} aria-hidden="true" />
                                    Marquer les non pointés présents
                                </button>
                            )}
                        </div>

                        <ul className="feuille-presence-liste">
                            {visibles.map((apprenant) => {
                                const ligne = lignes[apprenant.inscriptionId];
                                const avecRemarque =
                                    ligne.commentaire !== '' ||
                                    (ligne.statut !== null &&
                                        STATUTS_AVEC_REMARQUE.includes(
                                            ligne.statut,
                                        ));
                                const libelleStatut = statuts.find(
                                    (statut) => statut.valeur === ligne.statut,
                                )?.libelle;

                                return (
                                    <li
                                        key={apprenant.inscriptionId}
                                        className="feuille-presence-ligne"
                                    >
                                        <div className="feuille-presence-apprenant">
                                            <span
                                                className="feuille-presence-avatar"
                                                aria-hidden="true"
                                            >
                                                {initiales(apprenant.nom)}
                                            </span>
                                            <div>
                                                <p className="feuille-presence-nom">
                                                    {apprenant.nom}
                                                </p>
                                                <p className="feuille-presence-email">
                                                    {apprenant.email}
                                                </p>
                                            </div>
                                        </div>

                                        {modifiable ? (
                                            <div
                                                className="presence-choix"
                                                role="radiogroup"
                                                aria-label={`Présence de ${apprenant.nom}`}
                                            >
                                                {statuts.map((statut) => (
                                                    <button
                                                        key={statut.valeur}
                                                        type="button"
                                                        role="radio"
                                                        aria-checked={
                                                            ligne.statut ===
                                                            statut.valeur
                                                        }
                                                        className={`presence-choix-option ${statut.valeur.toLowerCase()}`}
                                                        onClick={() =>
                                                            changer(
                                                                apprenant.inscriptionId,
                                                                {
                                                                    statut: statut.valeur,
                                                                },
                                                            )
                                                        }
                                                    >
                                                        {statut.libelle}
                                                    </button>
                                                ))}
                                            </div>
                                        ) : (
                                            <span
                                                className={`presence-etiquette ${ligne.statut?.toLowerCase() ?? 'non-pointe'}`}
                                            >
                                                {libelleStatut ?? 'Non pointé'}
                                            </span>
                                        )}

                                        {modifiable && avecRemarque && (
                                            <input
                                                className="feuille-presence-remarque"
                                                aria-label={`Remarque pour ${apprenant.nom}`}
                                                placeholder="Motif ou remarque (facultatif)"
                                                maxLength={255}
                                                value={ligne.commentaire}
                                                onChange={(event) =>
                                                    changer(
                                                        apprenant.inscriptionId,
                                                        {
                                                            commentaire:
                                                                event.target
                                                                    .value,
                                                        },
                                                    )
                                                }
                                            />
                                        )}
                                        {!modifiable && ligne.commentaire && (
                                            <p className="feuille-presence-remarque-lue">
                                                {ligne.commentaire}
                                            </p>
                                        )}
                                    </li>
                                );
                            })}
                            {visibles.length === 0 && (
                                <li className="data-table-empty">
                                    Aucun apprenant ne correspond à la
                                    recherche.
                                </li>
                            )}
                        </ul>
                    </div>

                    {erreurs.length > 0 && (
                        <p
                            role="alert"
                            style={{
                                color: 'var(--dag-red)',
                                fontSize: 13,
                                marginTop: 12,
                            }}
                        >
                            {erreurs[0]}
                        </p>
                    )}

                    {modifiable && (
                        <div className="feuille-presence-barre">
                            <p>
                                {modifications > 0
                                    ? `${modifications} modification${modifications > 1 ? 's' : ''} non enregistrée${modifications > 1 ? 's' : ''}`
                                    : nonPointes > 0
                                      ? `${nonPointes} apprenant${nonPointes > 1 ? 's' : ''} à pointer`
                                      : 'Feuille de présence à jour'}
                            </p>
                            <button
                                type="submit"
                                className="btn-primary"
                                disabled={
                                    form.processing || modifications === 0
                                }
                            >
                                {form.processing
                                    ? 'Enregistrement…'
                                    : 'Enregistrer la présence'}
                            </button>
                        </div>
                    )}

                    {dernierPointage?.le && (
                        <p
                            className="muted"
                            style={{ fontSize: 12.5, marginTop: 14 }}
                        >
                            Dernier pointage
                            {dernierPointage.par
                                ? ` par ${dernierPointage.par}`
                                : ''}{' '}
                            le{' '}
                            {new Date(dernierPointage.le).toLocaleString(
                                'fr-FR',
                                {
                                    day: 'numeric',
                                    month: 'long',
                                    hour: '2-digit',
                                    minute: '2-digit',
                                },
                            )}
                            .
                        </p>
                    )}
                </form>
            )}
        </>
    );
}
