import { Check, CircleAlert, Clock, Hand, Info } from 'lucide-react';

export type EtapeProcedure = {
    cle: string;
    libelle: string;
    acteur: string;
    libelleActeur: string;
    etat: 'faite' | 'en_cours' | 'a_venir' | string;
};

export type ProchaineEtape = {
    cle: string;
    acteur: string;
    libelleActeur: string;
    aVous: boolean;
    titre: string;
    texte: string;
    action: string | null;
    information: boolean;
};

export type Parcours = {
    etapes: EtapeProcedure[];
    prochaine: ProchaineEtape | null;
    termine: boolean;
    interrompu: string | null;
};

/**
 * Where a procedure (demande, inscription) stands, the same way for the
 * client and the team: every step with who acts on it, then the next step
 * with what to do. Action buttons for the next step are passed as children.
 */
export default function SuiviProcedure({
    parcours,
    messageTermine = 'Procédure terminée.',
    compact = false,
    children,
}: {
    parcours: Parcours;
    messageTermine?: string;
    compact?: boolean;
    children?: React.ReactNode;
}) {
    const { etapes, prochaine, termine, interrompu } = parcours;
    const indexCourant = etapes.findIndex((etape) => etape.etat === 'en_cours');

    return (
        <div className={`suivi-procedure${compact ? ' compact' : ''}`}>
            <ol
                className="suivi-procedure-etapes"
                aria-label="Étapes de la procédure"
            >
                {etapes.map((etape, index) => (
                    <li
                        key={etape.cle}
                        className={`suivi-procedure-etape ${etape.etat}`}
                        aria-current={
                            etape.etat === 'en_cours' ? 'step' : undefined
                        }
                    >
                        <span
                            className="suivi-procedure-pastille"
                            aria-hidden="true"
                        >
                            {etape.etat === 'faite' ? (
                                <Check size={13} />
                            ) : (
                                index + 1
                            )}
                        </span>
                        <span className="suivi-procedure-texte">
                            <span className="suivi-procedure-libelle">
                                {etape.libelle}
                            </span>
                            {!compact && (
                                <span className="suivi-procedure-acteur">
                                    {etape.libelleActeur}
                                </span>
                            )}
                        </span>
                        <span className="sr-only">
                            {etape.etat === 'faite'
                                ? ' (terminée)'
                                : etape.etat === 'en_cours'
                                  ? ' (en cours)'
                                  : ''}
                        </span>
                    </li>
                ))}
            </ol>

            {interrompu ? (
                <div
                    className="suivi-procedure-prochaine interrompue"
                    role="status"
                >
                    <CircleAlert size={18} />
                    <p>{interrompu}</p>
                </div>
            ) : termine ? (
                <div
                    className="suivi-procedure-prochaine terminee"
                    role="status"
                >
                    <Check size={18} />
                    <p>{messageTermine}</p>
                </div>
            ) : (
                prochaine && (
                    <div
                        className={`suivi-procedure-prochaine ${
                            prochaine.information
                                ? 'information'
                                : prochaine.aVous
                                  ? 'a-vous'
                                  : 'en-attente'
                        }`}
                    >
                        <div className="suivi-procedure-prochaine-corps">
                            <span className="suivi-procedure-badge">
                                {prochaine.information ? (
                                    <>
                                        <Info size={13} /> Information
                                    </>
                                ) : prochaine.aVous ? (
                                    <>
                                        <Hand size={13} /> À vous
                                        {indexCourant !== -1 &&
                                            ` · étape ${indexCourant + 1} sur ${etapes.length}`}
                                    </>
                                ) : (
                                    <>
                                        <Clock size={13} /> En attente :{' '}
                                        {prochaine.libelleActeur}
                                        {indexCourant !== -1 &&
                                            ` · étape ${indexCourant + 1} sur ${etapes.length}`}
                                    </>
                                )}
                            </span>
                            <p className="suivi-procedure-titre">
                                {prochaine.titre}
                            </p>
                            <p className="suivi-procedure-description">
                                {prochaine.texte}
                            </p>
                        </div>
                        {children && (
                            <div className="suivi-procedure-actions">
                                {children}
                            </div>
                        )}
                    </div>
                )
            )}
        </div>
    );
}

/**
 * The next step of an item in a list, in one line: "À vous · Signer le
 * contrat" or "DAG · Valider le contrat".
 */
export function ProchaineEtapeCourte({
    prochaine,
}: {
    prochaine: { titre: string; aVous: boolean; libelleActeur?: string } | null;
}) {
    if (prochaine === null) {
        return <span className="muted">—</span>;
    }

    return (
        <span
            className={`suivi-procedure-courte ${prochaine.aVous ? 'a-vous' : 'en-attente'}`}
        >
            <strong>
                {prochaine.aVous
                    ? 'À vous'
                    : (prochaine.libelleActeur ?? 'DAG')}
            </strong>
            {prochaine.titre}
        </span>
    );
}
