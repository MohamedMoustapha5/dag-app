import { Head, Link, router, useForm, useHttp } from '@inertiajs/react';
import { X } from 'lucide-react';
import { useState } from 'react';
import { ProchaineEtapeCourte } from '@/components/espaces/suivi-procedure';
import { STATUT_CHIP, STATUT_LABEL } from '@/lib/demande-statut';
import inscriptionsAdmin from '@/routes/espace-admin/inscriptions';
import sessionsAdmin from '@/routes/espace-admin/sessions';
import ChampFraisInscription from '@/components/espaces/champ-frais-inscription';
import { libelleFraisInscription } from '@/lib/frais-inscription';

type Session = {
    id: string;
    formationId: string;
    formationNom: string;
    categorie: string | null;
    description: string;
    programme: string | null;
    duree: string | null;
    prerequis: string | null;
    ville: string;
    mode: string;
    lieu: string | null;
    formateur: string | null;
    dateDebut: string | null;
    dateFin: string | null;
    places: number;
    prix: string | null;
    inscritsCount: number;
    statut: string;
    image: string | null;
};

type Props = {
    sessions: Session[];
    peutCreer: boolean;
    peutModifier: boolean;
    peutSupprimer: boolean;
};

type Inscrit = {
    id: string;
    nom: string;
    email: string;
    statut: string;
    presence: number;
    seancesTotal: number;
    /** Registration fee facture, null for an enrolment made before fees existed. */
    paiement: {
        statut: string;
        montant: string;
        montantPaye: string;
        reference: string;
        url: string | null;
    } | null;
    validable: boolean;
    motif: string | null;
    dossierComplet: boolean;
    prochaineEtape: {
        titre: string;
        aVous: boolean;
        libelleActeur: string;
    } | null;
    pieces: {
        id: string;
        libelle: string;
        nomOriginal: string;
        url: string | null;
    }[];
};

const STATUTS_DECISION = [
    'DOSSIER_EN_ETUDE',
    'DOSSIER_A_COMPLETER',
    'EN_ATTENTE_PAIEMENT',
];

const PAIEMENT_LABEL: Record<string, string> = {
    EN_ATTENTE: 'À régler',
    PARTIELLEMENT_PAYEE: 'Partiellement payé',
    PAYEE: 'Payé',
    EN_ECHEC: 'Annulé',
};

const PAIEMENT_CHIP: Record<string, string> = {
    EN_ATTENTE: 'chip-orange',
    PARTIELLEMENT_PAYEE: 'chip-blue',
    PAYEE: 'chip-green',
    EN_ECHEC: 'chip-gray',
};

type Roster = {
    session: { ville: string; formationNom: string };
    peutValider: boolean;
    piecesRequises: string[];
    inscriptions: Inscrit[];
};

const MODE_LABEL: Record<string, string> = {
    PRESENTIEL: 'Présentiel',
    EN_LIGNE: 'En ligne',
    LES_DEUX: 'Les deux',
};

const MODE_CHIP: Record<string, string> = {
    PRESENTIEL: 'chip-gray',
    EN_LIGNE: 'chip-blue',
    LES_DEUX: 'chip-green',
};

const MOIS_COURT = [
    'janv.',
    'févr.',
    'mars',
    'avr.',
    'mai',
    'juin',
    'juil.',
    'août',
    'sept.',
    'oct.',
    'nov.',
    'déc.',
];

function formatDateCourte(iso: string): string {
    const [, mois, jour] = iso.split('-').map(Number);
    return `${jour} ${MOIS_COURT[mois - 1]}`;
}

function formatPlage(debut: string | null, fin: string | null): string {
    if (!debut && !fin) {
        return '—';
    }
    if (debut && fin && debut !== fin) {
        return `${formatDateCourte(debut)} → ${formatDateCourte(fin)}`;
    }
    return formatDateCourte(debut ?? fin ?? '');
}

export default function AdminFormations({
    sessions,
    peutCreer,
    peutModifier,
    peutSupprimer,
}: Props) {
    const [createOpen, setCreateOpen] = useState(false);
    const [editingSession, setEditingSession] = useState<Session | null>(null);
    const [expanded, setExpanded] = useState<Session | null>(null);
    const [roster, setRoster] = useState<Roster | null>(null);
    const [rosterLoading, setRosterLoading] = useState(false);
    const [dossierOuvert, setDossierOuvert] = useState<Inscrit | null>(null);

    const { submit } = useHttp();

    const rechargerInscrits = async () => {
        if (expanded) {
            const data = (await submit(
                sessionsAdmin.inscrits(expanded.id),
            )) as Roster;
            setRoster(data);
        }
    };

    const chargerInscrits = async (session: Session) => {
        if (expanded?.id === session.id) {
            setExpanded(null);
            setRoster(null);
            return;
        }

        setExpanded(session);
        setRoster(null);
        setRosterLoading(true);

        try {
            const data = (await submit(
                sessionsAdmin.inscrits(session.id),
            )) as Roster;
            setRoster(data);
        } finally {
            setRosterLoading(false);
        }
    };

    /** Confirms a legacy enrolment whose fee was paid before dossiers existed. */
    const confirmerInscription = async (inscriptionId: string) => {
        try {
            await submit(inscriptionsAdmin.valider(inscriptionId));
        } catch {
            alert(
                "L'inscription ne peut pas être confirmée : les frais d'inscription ne sont pas encore réglés.",
            );
        }

        await rechargerInscrits();
    };

    const supprimerSession = (session: Session) => {
        if (
            !confirm(
                `Supprimer la session « ${session.formationNom} — ${session.ville} » ? Ses séances seront également supprimées.`,
            )
        ) {
            return;
        }
        router.delete(sessionsAdmin.destroy(session.id).url, {
            preserveScroll: true,
        });
        if (expanded?.id === session.id) {
            setExpanded(null);
            setRoster(null);
        }
    };

    return (
        <>
            <Head title="Formations & sessions — Administration" />

            <div
                style={{
                    display: 'flex',
                    justifyContent: 'space-between',
                    alignItems: 'flex-start',
                    gap: 16,
                    flexWrap: 'wrap',
                    marginBottom: 24,
                }}
            >
                <div>
                    <h1 style={{ fontSize: 26, color: 'var(--dag-navy)' }}>
                        Formations & sessions
                    </h1>
                    <p className="muted" style={{ marginTop: 4 }}>
                        Gestion des sessions, validation des inscriptions.
                    </p>
                </div>
                {peutCreer && (
                    <button
                        type="button"
                        className="btn-primary"
                        onClick={() => setCreateOpen(true)}
                    >
                        + Nouvelle session
                    </button>
                )}
            </div>

            <div className="card" style={{ padding: 0, marginBottom: 28 }}>
                {sessions.length === 0 ? (
                    <p className="data-table-empty" style={{ padding: 24 }}>
                        Aucune session pour le moment.
                    </p>
                ) : (
                    <div className="data-table-wrap">
                        <table className="data-table cartes-mobile">
                            <thead>
                                <tr>
                                    <th>Session</th>
                                    <th>Ville</th>
                                    <th>Mode</th>
                                    <th>Dates</th>
                                    <th>Inscrits</th>
                                    <th>Statut</th>
                                    <th />
                                </tr>
                            </thead>
                            <tbody>
                                {sessions.map((session) => (
                                    <tr key={session.id}>
                                        <td
                                            className="cellule-titre"
                                            data-label="Session"
                                            style={{ fontWeight: 600 }}
                                        >
                                            {session.formationNom}
                                        </td>
                                        <td
                                            data-label="Ville"
                                            className="muted"
                                        >
                                            {session.ville}
                                        </td>
                                        <td data-label="Mode">
                                            <span
                                                className={`chip ${MODE_CHIP[session.mode] ?? 'chip-gray'}`}
                                            >
                                                {MODE_LABEL[session.mode] ??
                                                    session.mode}
                                            </span>
                                        </td>
                                        <td
                                            data-label="Dates"
                                            className="muted"
                                        >
                                            {formatPlage(
                                                session.dateDebut,
                                                session.dateFin,
                                            )}
                                        </td>
                                        <td
                                            data-label="Inscrits"
                                            className="muted"
                                        >
                                            {session.inscritsCount}/
                                            {session.places}
                                            <br />
                                            <span
                                                style={{
                                                    fontSize: 12,
                                                    color:
                                                        session.prix === null
                                                            ? 'var(--dag-orange-ink)'
                                                            : undefined,
                                                }}
                                            >
                                                {libelleFraisInscription(
                                                    session.prix,
                                                )}
                                            </span>
                                        </td>
                                        <td data-label="Statut">
                                            <span
                                                className={`chip ${STATUT_CHIP[session.statut] ?? 'chip-gray'}`}
                                            >
                                                {STATUT_LABEL[session.statut] ??
                                                    session.statut}
                                            </span>
                                        </td>
                                        <td
                                            data-label=""
                                            style={{ textAlign: 'right' }}
                                        >
                                            <div
                                                style={{
                                                    display: 'flex',
                                                    gap: 10,
                                                    justifyContent: 'flex-end',
                                                    flexWrap: 'wrap',
                                                }}
                                            >
                                                <button
                                                    type="button"
                                                    className="link-action"
                                                    onClick={() =>
                                                        chargerInscrits(session)
                                                    }
                                                >
                                                    Voir les inscrits
                                                </button>
                                                {peutModifier && (
                                                    <button
                                                        type="button"
                                                        className="link-action"
                                                        onClick={() =>
                                                            setEditingSession(
                                                                session,
                                                            )
                                                        }
                                                    >
                                                        Modifier
                                                    </button>
                                                )}
                                                {peutSupprimer && (
                                                    <button
                                                        type="button"
                                                        className="link-action"
                                                        style={{
                                                            color: 'var(--dag-red)',
                                                        }}
                                                        onClick={() =>
                                                            supprimerSession(
                                                                session,
                                                            )
                                                        }
                                                    >
                                                        Supprimer
                                                    </button>
                                                )}
                                            </div>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </div>

            {expanded && (
                <div className="card" style={{ padding: 24 }}>
                    <div
                        style={{
                            display: 'flex',
                            justifyContent: 'space-between',
                            alignItems: 'center',
                            gap: 16,
                            flexWrap: 'wrap',
                            marginBottom: 16,
                        }}
                    >
                        <p style={{ fontSize: 14.5, fontWeight: 700 }}>
                            Inscrits — {expanded.formationNom}, {expanded.ville}{' '}
                            {roster && (
                                <span
                                    className="chip chip-green"
                                    style={{ marginLeft: 8 }}
                                >
                                    {roster.inscriptions.length}/
                                    {expanded.places}
                                </span>
                            )}
                        </p>
                        <a
                            href={sessionsAdmin.export(expanded.id).url}
                            className="btn-outline"
                            style={{ padding: '8px 16px', fontSize: 13 }}
                        >
                            Exporter la liste
                        </a>
                    </div>

                    {rosterLoading && (
                        <p className="muted" style={{ fontSize: 13 }}>
                            Chargement…
                        </p>
                    )}

                    {!rosterLoading && roster && (
                        <>
                            {roster.inscriptions.length === 0 ? (
                                <p className="data-table-empty">
                                    Aucun inscrit pour cette session.
                                </p>
                            ) : (
                                <div className="data-table-wrap">
                                    <table className="data-table cartes-mobile">
                                        <thead>
                                            <tr>
                                                <th>Apprenant</th>
                                                <th>E-mail</th>
                                                <th>Statut</th>
                                                <th>Dossier</th>
                                                <th>Frais d'inscription</th>
                                                <th>Présence</th>
                                                <th />
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {roster.inscriptions.map(
                                                (inscrit) => (
                                                    <tr key={inscrit.id}>
                                                        <td
                                                            className="cellule-titre"
                                                            data-label="Apprenant"
                                                            style={{
                                                                fontWeight: 600,
                                                            }}
                                                        >
                                                            {inscrit.nom}
                                                        </td>
                                                        <td
                                                            data-label="E-mail"
                                                            className="muted"
                                                        >
                                                            {inscrit.email}
                                                        </td>
                                                        <td data-label="Statut">
                                                            <span
                                                                className={`chip ${STATUT_CHIP[inscrit.statut] ?? 'chip-gray'}`}
                                                            >
                                                                {STATUT_LABEL[
                                                                    inscrit
                                                                        .statut
                                                                ] ??
                                                                    inscrit.statut}
                                                            </span>
                                                            {inscrit.prochaineEtape && (
                                                                <div
                                                                    style={{
                                                                        marginTop: 6,
                                                                    }}
                                                                >
                                                                    <ProchaineEtapeCourte
                                                                        prochaine={
                                                                            inscrit.prochaineEtape
                                                                        }
                                                                    />
                                                                </div>
                                                            )}
                                                        </td>
                                                        <td
                                                            data-label="Dossier"
                                                            className="muted"
                                                        >
                                                            {inscrit.pieces
                                                                .length > 0
                                                                ? `${inscrit.pieces.length}/${roster.piecesRequises.length} pièce${inscrit.pieces.length > 1 ? 's' : ''}`
                                                                : '—'}
                                                        </td>
                                                        <td data-label="Frais">
                                                            <PaiementInscrit
                                                                paiement={
                                                                    inscrit.paiement
                                                                }
                                                            />
                                                        </td>
                                                        <td
                                                            data-label="Présence"
                                                            className="muted"
                                                        >
                                                            {inscrit.presence}/
                                                            {
                                                                inscrit.seancesTotal
                                                            }
                                                        </td>
                                                        <td
                                                            data-label=""
                                                            style={{
                                                                textAlign:
                                                                    'right',
                                                            }}
                                                        >
                                                            {roster.peutValider &&
                                                                STATUTS_DECISION.includes(
                                                                    inscrit.statut,
                                                                ) && (
                                                                    <div
                                                                        style={{
                                                                            display:
                                                                                'flex',
                                                                            gap: 10,
                                                                            justifyContent:
                                                                                'flex-end',
                                                                        }}
                                                                    >
                                                                        {inscrit.statut ===
                                                                            'EN_ATTENTE_PAIEMENT' &&
                                                                            inscrit.validable && (
                                                                                <button
                                                                                    type="button"
                                                                                    className="link-action"
                                                                                    onClick={() =>
                                                                                        confirmerInscription(
                                                                                            inscrit.id,
                                                                                        )
                                                                                    }
                                                                                >
                                                                                    Confirmer
                                                                                </button>
                                                                            )}
                                                                        <button
                                                                            type="button"
                                                                            className="link-action"
                                                                            onClick={() =>
                                                                                setDossierOuvert(
                                                                                    inscrit,
                                                                                )
                                                                            }
                                                                        >
                                                                            {inscrit.statut ===
                                                                            'DOSSIER_EN_ETUDE'
                                                                                ? 'Examiner le dossier'
                                                                                : 'Voir le dossier'}
                                                                        </button>
                                                                    </div>
                                                                )}
                                                        </td>
                                                    </tr>
                                                ),
                                            )}
                                        </tbody>
                                    </table>
                                </div>
                            )}
                        </>
                    )}
                </div>
            )}

            {dossierOuvert && roster && (
                <ExamenDossierModal
                    inscrit={dossierOuvert}
                    piecesRequises={roster.piecesRequises}
                    onClose={() => setDossierOuvert(null)}
                    onDecision={async () => {
                        setDossierOuvert(null);
                        await rechargerInscrits();
                    }}
                />
            )}

            {createOpen && (
                <SessionModal
                    mode="create"
                    onClose={() => setCreateOpen(false)}
                />
            )}
            {editingSession && (
                <SessionModal
                    mode="edit"
                    session={editingSession}
                    onClose={() => setEditingSession(null)}
                />
            )}
        </>
    );
}

/**
 * Review of an enrolment dossier: the team opens each piece, then validates
 * the dossier (the registration fee is billed and the apprenant can pay),
 * asks for a completion with a reason, or refuses it.
 */
function ExamenDossierModal({
    inscrit,
    piecesRequises,
    onClose,
    onDecision,
}: {
    inscrit: Inscrit;
    piecesRequises: string[];
    onClose: () => void;
    onDecision: () => Promise<void>;
}) {
    const http = useHttp<{ motif: string }>({ motif: '' });
    const [action, setAction] = useState<'complement' | 'refus' | null>(null);
    const [erreur, setErreur] = useState<string | null>(null);
    const enExamen = inscrit.statut === 'DOSSIER_EN_ETUDE';
    const parLibelle = Object.fromEntries(
        inscrit.pieces.map((piece) => [piece.libelle, piece]),
    );

    const decider = async (decision: 'valider' | 'complement' | 'refus') => {
        setErreur(null);

        const route =
            decision === 'valider'
                ? inscriptionsAdmin.valider(inscrit.id)
                : decision === 'complement'
                  ? inscriptionsAdmin.completer(inscrit.id)
                  : inscriptionsAdmin.refuser(inscrit.id);

        try {
            await http.submit(route);
            await onDecision();
        } catch {
            if (!http.errors.motif) {
                setErreur(
                    decision === 'refus'
                        ? "L'inscription ne peut pas être refusée : un paiement a déjà été reçu, remboursez-le d'abord."
                        : "L'action n'a pas pu être enregistrée : rechargez la liste et réessayez.",
                );
            }
        }
    };

    return (
        <div
            className="modal-backdrop"
            role="dialog"
            aria-modal="true"
            aria-label={`Dossier d'inscription de ${inscrit.nom}`}
            onClick={(event) => {
                if (event.target === event.currentTarget) {
                    onClose();
                }
            }}
        >
            <div className="modal-panel">
                <button
                    type="button"
                    className="modal-close"
                    onClick={onClose}
                    aria-label="Fermer"
                >
                    <X size={16} />
                </button>

                <h2 style={{ fontSize: 19, color: 'var(--dag-navy)' }}>
                    Dossier de {inscrit.nom}
                </h2>
                <p
                    className="muted"
                    style={{ marginTop: 6, marginBottom: 20, fontSize: 13 }}
                >
                    {inscrit.email} ·{' '}
                    <span
                        className={`chip ${STATUT_CHIP[inscrit.statut] ?? 'chip-gray'}`}
                    >
                        {STATUT_LABEL[inscrit.statut] ?? inscrit.statut}
                    </span>
                </p>

                <ul
                    style={{
                        listStyle: 'none',
                        margin: 0,
                        padding: 0,
                        display: 'flex',
                        flexDirection: 'column',
                        gap: 8,
                    }}
                >
                    {piecesRequises.map((libelle) => {
                        const piece = parLibelle[libelle];

                        return (
                            <li
                                key={libelle}
                                style={{
                                    display: 'flex',
                                    justifyContent: 'space-between',
                                    alignItems: 'center',
                                    gap: 12,
                                    padding: '10px 14px',
                                    borderRadius: 10,
                                    border: '1px solid var(--dag-border)',
                                    background: piece
                                        ? 'var(--dag-bg)'
                                        : 'var(--dag-red-bg)',
                                }}
                            >
                                <span style={{ minWidth: 0 }}>
                                    <span
                                        style={{
                                            display: 'block',
                                            fontWeight: 600,
                                            fontSize: 13.5,
                                        }}
                                    >
                                        {libelle}
                                    </span>
                                    <span
                                        className="muted"
                                        style={{ fontSize: 12 }}
                                    >
                                        {piece
                                            ? piece.nomOriginal
                                            : 'Non fournie'}
                                    </span>
                                </span>
                                {piece?.url && (
                                    <a
                                        href={piece.url}
                                        target="_blank"
                                        rel="noreferrer"
                                        className="link-action"
                                        style={{ flex: 'none', fontSize: 13 }}
                                    >
                                        Ouvrir ↗
                                    </a>
                                )}
                            </li>
                        );
                    })}
                </ul>

                {inscrit.motif && (
                    <p className="dossier-motif" style={{ marginTop: 16 }}>
                        <strong>Dernier message envoyé :</strong>{' '}
                        {inscrit.motif}
                    </p>
                )}

                {action !== null && (
                    <div style={{ marginTop: 18 }}>
                        <label htmlFor="motif-decision">
                            {action === 'complement'
                                ? "Ce que l'apprenant doit corriger ou ajouter"
                                : 'Motif du refus (facultatif)'}
                        </label>
                        <textarea
                            id="motif-decision"
                            rows={3}
                            value={http.data.motif}
                            placeholder={
                                action === 'complement'
                                    ? 'Ex. La copie de la CNI est illisible : renvoyez un scan net, recto et verso.'
                                    : ''
                            }
                            onChange={(event) =>
                                http.setData('motif', event.target.value)
                            }
                        />
                        {http.errors.motif && (
                            <p
                                style={{
                                    color: 'var(--dag-red)',
                                    fontSize: 12,
                                    marginTop: 4,
                                }}
                            >
                                {http.errors.motif}
                            </p>
                        )}
                    </div>
                )}

                {erreur && (
                    <p
                        role="alert"
                        style={{
                            color: 'var(--dag-red)',
                            fontSize: 13,
                            marginTop: 12,
                        }}
                    >
                        {erreur}
                    </p>
                )}

                <div
                    style={{
                        display: 'flex',
                        flexWrap: 'wrap',
                        gap: 10,
                        marginTop: 22,
                    }}
                >
                    {action === null ? (
                        <>
                            {enExamen && (
                                <button
                                    type="button"
                                    className="btn-primary"
                                    disabled={
                                        http.processing ||
                                        !inscrit.dossierComplet
                                    }
                                    title={
                                        inscrit.dossierComplet
                                            ? undefined
                                            : 'Il manque des pièces : demandez un complément'
                                    }
                                    onClick={() => decider('valider')}
                                >
                                    Valider le dossier
                                </button>
                            )}
                            {enExamen && (
                                <button
                                    type="button"
                                    className="btn-outline"
                                    onClick={() => setAction('complement')}
                                >
                                    Demander un complément
                                </button>
                            )}
                            <button
                                type="button"
                                className="btn-outline"
                                style={{
                                    color: 'var(--dag-red)',
                                    borderColor: 'var(--dag-red)',
                                }}
                                onClick={() => setAction('refus')}
                            >
                                Refuser l'inscription
                            </button>
                        </>
                    ) : (
                        <>
                            <button
                                type="button"
                                className="btn-primary"
                                disabled={http.processing}
                                onClick={() =>
                                    decider(
                                        action === 'complement'
                                            ? 'complement'
                                            : 'refus',
                                    )
                                }
                            >
                                {action === 'complement'
                                    ? "Envoyer la demande à l'apprenant"
                                    : 'Confirmer le refus'}
                            </button>
                            <button
                                type="button"
                                className="btn-outline"
                                onClick={() => {
                                    setAction(null);
                                    http.clearErrors();
                                }}
                            >
                                Retour
                            </button>
                        </>
                    )}
                </div>

                {enExamen && action === null && (
                    <p
                        className="muted"
                        style={{ fontSize: 12, marginTop: 12, lineHeight: 1.6 }}
                    >
                        En validant, la facture des frais d'inscription est
                        émise : l'apprenant est prévenu et peut payer. Son
                        paiement confirme sa place.
                    </p>
                )}
            </div>
        </div>
    );
}

/**
 * Registration fee of one enrolment: amount, payment state and, for a role
 * allowed to see factures, the link where the payment is confirmed.
 */
function PaiementInscrit({ paiement }: { paiement: Inscrit['paiement'] }) {
    if (paiement === null) {
        return <span className="muted">—</span>;
    }

    return (
        <div style={{ display: 'flex', flexDirection: 'column', gap: 4 }}>
            <span style={{ fontWeight: 600 }}>
                {libelleFraisInscription(paiement.montant)}
            </span>
            <span
                className={`chip ${PAIEMENT_CHIP[paiement.statut] ?? 'chip-gray'}`}
                style={{ alignSelf: 'flex-start' }}
            >
                {PAIEMENT_LABEL[paiement.statut] ?? paiement.statut}
            </span>
            {paiement.url &&
                ['EN_ATTENTE', 'PARTIELLEMENT_PAYEE'].includes(
                    paiement.statut,
                ) && (
                    <Link
                        href={paiement.url}
                        className="link-action"
                        style={{ fontSize: 12 }}
                    >
                        Confirmer le paiement →
                    </Link>
                )}
        </div>
    );
}

function SessionModal({
    mode,
    session,
    onClose,
}: {
    mode: 'create' | 'edit';
    session?: Session;
    onClose: () => void;
}) {
    const form = useForm<{
        formation_nom: string;
        categorie: string;
        description: string;
        programme: string;
        duree: string;
        prerequis: string;
        ville: string;
        places: number;
        prix: string;
        mode: string;
        date_debut: string;
        date_fin: string;
        formateur: string;
        statut: string;
        image: File | null;
    }>({
        formation_nom: '',
        categorie: session?.categorie ?? '',
        description: mode === 'edit' ? (session?.description ?? '') : '',
        programme: session?.programme ?? '',
        duree: session?.duree ?? '',
        prerequis: session?.prerequis ?? '',
        ville: session?.ville ?? '',
        places: session?.places ?? 15,
        prix: session?.prix ? String(Math.round(Number(session.prix))) : '',
        mode: session?.mode ?? 'PRESENTIEL',
        date_debut: session?.dateDebut ?? '',
        date_fin: session?.dateFin ?? '',
        formateur: session?.formateur ?? '',
        statut: session?.statut ?? 'OUVERTE',
        image: null,
    });

    const submit = (event: React.FormEvent) => {
        event.preventDefault();

        if (mode === 'create') {
            form.post(sessionsAdmin.storeRapide().url, {
                onSuccess: onClose,
            });
        } else if (session) {
            form.patch(sessionsAdmin.update(session.id).url, {
                onSuccess: onClose,
            });
        }
    };

    return (
        <div
            className="modal-backdrop"
            role="dialog"
            aria-modal="true"
            aria-label={
                mode === 'create'
                    ? 'Nouvelle session de formation'
                    : 'Modifier la session'
            }
            onClick={(event) => {
                if (event.target === event.currentTarget) {
                    onClose();
                }
            }}
        >
            <div className="modal-panel">
                <button
                    type="button"
                    className="modal-close"
                    onClick={onClose}
                    aria-label="Fermer"
                >
                    <X size={16} />
                </button>

                <h2 style={{ fontSize: 19, color: 'var(--dag-navy)' }}>
                    {mode === 'create'
                        ? 'Nouvelle session de formation'
                        : 'Modifier la session'}
                </h2>
                <p
                    style={{
                        marginTop: 8,
                        marginBottom: 24,
                        fontSize: 13.5,
                        lineHeight: 1.6,
                        color: 'var(--dag-text-muted)',
                    }}
                >
                    {mode === 'create'
                        ? 'La session apparaît immédiatement dans la liste, avec le statut choisi.'
                        : 'Les modifications sont enregistrées immédiatement.'}
                </p>

                <form
                    onSubmit={submit}
                    style={{
                        display: 'flex',
                        flexDirection: 'column',
                        gap: 16,
                    }}
                >
                    {mode === 'create' && (
                        <div>
                            <label htmlFor="formation-nom">
                                Intitulé de la formation
                            </label>
                            <input
                                id="formation-nom"
                                placeholder="Ex. Montage vidéo — Initiation"
                                value={form.data.formation_nom}
                                onChange={(event) =>
                                    form.setData(
                                        'formation_nom',
                                        event.target.value,
                                    )
                                }
                                autoFocus
                            />
                            {form.errors.formation_nom && (
                                <p
                                    style={{
                                        color: 'var(--dag-red)',
                                        fontSize: 12,
                                        marginTop: 4,
                                    }}
                                >
                                    {form.errors.formation_nom}
                                </p>
                            )}
                        </div>
                    )}

                    {(mode === 'create' || mode === 'edit') && (
                        <fieldset
                            style={{
                                border: '1px solid var(--dag-border)',
                                borderRadius: 12,
                                padding: '14px 16px 16px',
                                display: 'flex',
                                flexDirection: 'column',
                                gap: 14,
                            }}
                        >
                            <legend
                                style={{
                                    fontSize: 12.5,
                                    fontWeight: 700,
                                    padding: '0 6px',
                                    color: 'var(--dag-navy)',
                                }}
                            >
                                Contenu affiché sur la page de la formation
                            </legend>
                            <div
                                style={{
                                    display: 'grid',
                                    gridTemplateColumns: '1fr 1fr',
                                    gap: 14,
                                }}
                            >
                                <div>
                                    <label htmlFor="categorie">Catégorie</label>
                                    <input
                                        id="categorie"
                                        placeholder="Ex. Community management"
                                        value={form.data.categorie}
                                        onChange={(event) =>
                                            form.setData(
                                                'categorie',
                                                event.target.value,
                                            )
                                        }
                                    />
                                </div>
                                <div>
                                    <label htmlFor="duree">Durée</label>
                                    <input
                                        id="duree"
                                        placeholder="Ex. 3 semaines"
                                        value={form.data.duree}
                                        onChange={(event) =>
                                            form.setData(
                                                'duree',
                                                event.target.value,
                                            )
                                        }
                                    />
                                </div>
                            </div>
                            <div>
                                <label htmlFor="description">Description</label>
                                <textarea
                                    id="description"
                                    rows={3}
                                    placeholder="Présentation courte de la formation"
                                    value={form.data.description}
                                    onChange={(event) =>
                                        form.setData(
                                            'description',
                                            event.target.value,
                                        )
                                    }
                                />
                            </div>
                            <div>
                                <label htmlFor="programme">Programme</label>
                                <textarea
                                    id="programme"
                                    rows={5}
                                    placeholder={
                                        'Une ligne par étape.\nEx. Semaine 1 — Fondamentaux des réseaux sociaux'
                                    }
                                    value={form.data.programme}
                                    onChange={(event) =>
                                        form.setData(
                                            'programme',
                                            event.target.value,
                                        )
                                    }
                                />
                            </div>
                            <div>
                                <label htmlFor="prerequis">Prérequis</label>
                                <input
                                    id="prerequis"
                                    placeholder="Ex. Aucun — niveau débutant"
                                    value={form.data.prerequis}
                                    onChange={(event) =>
                                        form.setData(
                                            'prerequis',
                                            event.target.value,
                                        )
                                    }
                                />
                            </div>
                            <p className="muted" style={{ fontSize: 11.5 }}>
                                {mode === 'create'
                                    ? 'Facultatif. Si la formation existe déjà, les champs remplis la mettent à jour.'
                                    : 'Ces informations concernent la formation : elles s’appliquent à toutes ses sessions.'}
                            </p>
                        </fieldset>
                    )}

                    <div
                        style={{
                            display: 'grid',
                            gridTemplateColumns: '1fr 1fr',
                            gap: 14,
                        }}
                    >
                        <div>
                            <label htmlFor="ville">Ville</label>
                            <input
                                id="ville"
                                placeholder="Ex. Douala"
                                value={form.data.ville}
                                onChange={(event) =>
                                    form.setData('ville', event.target.value)
                                }
                            />
                            {form.errors.ville && (
                                <p
                                    style={{
                                        color: 'var(--dag-red)',
                                        fontSize: 12,
                                        marginTop: 4,
                                    }}
                                >
                                    {form.errors.ville}
                                </p>
                            )}
                        </div>
                        <div>
                            <label htmlFor="places">Places disponibles</label>
                            <input
                                id="places"
                                type="number"
                                min={1}
                                placeholder="Ex. 15"
                                value={form.data.places}
                                onChange={(event) =>
                                    form.setData(
                                        'places',
                                        Number(event.target.value),
                                    )
                                }
                            />
                        </div>
                    </div>

                    <ChampFraisInscription
                        id="prix"
                        valeur={form.data.prix}
                        erreur={form.errors.prix}
                        onChange={(valeur) => form.setData('prix', valeur)}
                    />

                    <div>
                        <label htmlFor="mode">Mode</label>
                        <select
                            id="mode"
                            value={form.data.mode}
                            onChange={(event) =>
                                form.setData('mode', event.target.value)
                            }
                        >
                            <option value="PRESENTIEL">Présentiel</option>
                            <option value="EN_LIGNE">En ligne</option>
                            <option value="LES_DEUX">Les deux</option>
                        </select>
                    </div>

                    <div
                        style={{
                            display: 'grid',
                            gridTemplateColumns: '1fr 1fr',
                            gap: 14,
                        }}
                    >
                        <div>
                            <label htmlFor="date_debut">Date de début</label>
                            <input
                                id="date_debut"
                                type="date"
                                value={form.data.date_debut}
                                onChange={(event) =>
                                    form.setData(
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
                                value={form.data.date_fin}
                                onChange={(event) =>
                                    form.setData('date_fin', event.target.value)
                                }
                            />
                            {form.errors.date_fin && (
                                <p
                                    style={{
                                        color: 'var(--dag-red)',
                                        fontSize: 12,
                                        marginTop: 4,
                                    }}
                                >
                                    {form.errors.date_fin}
                                </p>
                            )}
                        </div>
                    </div>

                    <div>
                        <label htmlFor="formateur">
                            Formateur / Formatrice
                        </label>
                        <input
                            id="formateur"
                            placeholder="Ex. S. Mballa"
                            value={form.data.formateur}
                            onChange={(event) =>
                                form.setData('formateur', event.target.value)
                            }
                        />
                    </div>

                    <div>
                        <label htmlFor="image">Image de la formation</label>
                        <input
                            id="image"
                            type="file"
                            accept="image/jpeg,image/png,image/webp"
                            onChange={(event) =>
                                form.setData(
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
                            {mode === 'edit' && session?.image
                                ? ' Laissez vide pour conserver l’image actuelle.'
                                : " L'image sélectionnée est affichée en aperçu."}
                        </p>
                        {form.errors.image && (
                            <p
                                style={{
                                    color: 'var(--dag-red)',
                                    fontSize: 12,
                                    marginTop: 4,
                                }}
                            >
                                {form.errors.image}
                            </p>
                        )}
                        {mode === 'edit' && session?.image && (
                            <img
                                src={session.image}
                                alt=""
                                style={{
                                    marginTop: 10,
                                    width: '100%',
                                    height: 100,
                                    objectFit: 'cover',
                                    borderRadius: 'var(--dag-radius-sm)',
                                }}
                            />
                        )}
                    </div>

                    <div>
                        <label htmlFor="statut">Statut</label>
                        <select
                            id="statut"
                            value={form.data.statut}
                            onChange={(event) =>
                                form.setData('statut', event.target.value)
                            }
                        >
                            <option value="BROUILLON">Brouillon</option>
                            <option value="OUVERTE">Ouverte</option>
                            <option value="COMPLETE">Complète</option>
                            <option value="TERMINEE">Terminée</option>
                        </select>
                    </div>

                    <div style={{ display: 'flex', gap: 10 }}>
                        <button
                            type="button"
                            onClick={onClose}
                            className="btn-outline"
                            style={{ flex: 1 }}
                        >
                            Annuler
                        </button>
                        <button
                            type="submit"
                            disabled={form.processing}
                            className="btn-primary"
                            style={{ flex: 1 }}
                        >
                            {form.processing
                                ? 'Enregistrement…'
                                : mode === 'create'
                                  ? 'Créer la session'
                                  : 'Enregistrer'}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    );
}
