import { Head, Link, useForm } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import PiecesDossier from '@/components/espaces/pieces-dossier';
import SuiviProcedure, {
    type Parcours,
} from '@/components/espaces/suivi-procedure';
import { STATUT_CHIP, STATUT_LABEL } from '@/lib/demande-statut';
import espaceApprenant from '@/routes/espace-apprenant';
import inscriptionsRoutes from '@/routes/espace-apprenant/inscriptions';
import { libelleFraisInscription } from '@/lib/frais-inscription';

type Inscription = {
    id: string;
    formation: string;
    ville: string | null;
    mode: string | null;
    dateDebut: string | null;
    dateFin: string | null;
    statut: string;
    progression: number;
    semainesAvantDebut: number | null;
    fraisInscription: string | null;
    paiement: string | null;
    motif: string | null;
    piecesRequises: string[];
    piecesDeposees: { libelle: string; nomOriginal: string }[];
    parcours: Parcours;
};

type Props = {
    inscriptions: Inscription[];
};

type Onglet = 'toutes' | 'en-cours' | 'a-venir' | 'terminees';

const ONGLETS: { value: Onglet; label: string }[] = [
    { value: 'toutes', label: 'Toutes' },
    { value: 'en-cours', label: 'En cours' },
    { value: 'a-venir', label: 'À venir' },
    { value: 'terminees', label: 'Terminées' },
];

function classifier(inscription: Inscription): Onglet {
    if (inscription.statut === 'FORMEE') {
        return 'terminees';
    }
    if (inscription.semainesAvantDebut !== null) {
        return 'a-venir';
    }
    return 'en-cours';
}

export default function ApprenantInscriptionsIndex({ inscriptions }: Props) {
    const [onglet, setOnglet] = useState<Onglet>('toutes');

    const filtered = useMemo(
        () =>
            onglet === 'toutes'
                ? inscriptions
                : inscriptions.filter(
                      (inscription) => classifier(inscription) === onglet,
                  ),
        [inscriptions, onglet],
    );

    return (
        <>
            <Head title="Mes inscriptions" />

            <div
                className="espace-hero"
                style={{
                    backgroundImage: "url('/images/photos/lms-dashboard.jpg')",
                }}
            >
                <div className="espace-hero-overlay" />
                <div
                    style={{
                        position: 'relative',
                        zIndex: 1,
                        display: 'flex',
                        justifyContent: 'space-between',
                        alignItems: 'center',
                        gap: 16,
                        flexWrap: 'wrap',
                        width: '100%',
                    }}
                >
                    <div className="espace-hero-content">
                        <h1>Mes inscriptions</h1>
                        <p>L'historique de vos sessions de formation DAG.</p>
                    </div>
                    <Link
                        href={espaceApprenant.formations()}
                        className="btn-primary"
                    >
                        + Parcourir les formations
                    </Link>
                </div>
            </div>

            <div className="account-tabs" style={{ marginBottom: 24 }}>
                {ONGLETS.map((item) => (
                    <button
                        key={item.value}
                        type="button"
                        className={`account-tab${onglet === item.value ? ' active' : ''}`}
                        onClick={() => setOnglet(item.value)}
                    >
                        {item.label}
                    </button>
                ))}
            </div>

            {filtered.length === 0 ? (
                <p className="data-table-empty">
                    Aucune inscription dans cette catégorie.
                </p>
            ) : (
                <div
                    style={{
                        display: 'flex',
                        flexDirection: 'column',
                        gap: 16,
                    }}
                >
                    {filtered.map((inscription) => {
                        const classe = classifier(inscription);

                        return (
                            <div
                                key={inscription.id}
                                className="card"
                                style={{ padding: 24 }}
                            >
                                <div
                                    style={{
                                        display: 'flex',
                                        justifyContent: 'space-between',
                                        alignItems: 'flex-start',
                                        gap: 16,
                                        flexWrap: 'wrap',
                                    }}
                                >
                                    <div>
                                        <div
                                            style={{
                                                display: 'flex',
                                                alignItems: 'center',
                                                gap: 10,
                                            }}
                                        >
                                            <h3
                                                style={{
                                                    fontSize: 16,
                                                    color: 'var(--dag-navy)',
                                                }}
                                            >
                                                {inscription.formation}
                                            </h3>
                                            <span
                                                className={`chip ${STATUT_CHIP[inscription.statut] ?? 'chip-gray'}`}
                                            >
                                                {STATUT_LABEL[
                                                    inscription.statut
                                                ] ?? inscription.statut}
                                            </span>
                                        </div>
                                        <p
                                            className="muted"
                                            style={{
                                                marginTop: 6,
                                                fontSize: 12.5,
                                            }}
                                        >
                                            Session du {inscription.dateDebut}
                                            {inscription.dateFin
                                                ? ` au ${inscription.dateFin}`
                                                : ''}
                                            {inscription.ville
                                                ? ` · ${inscription.ville}`
                                                : ''}
                                            {inscription.mode
                                                ? ` · ${inscription.mode}`
                                                : ''}
                                        </p>
                                        {inscription.fraisInscription !==
                                            null && (
                                            <EtatFrais
                                                inscription={inscription}
                                            />
                                        )}
                                    </div>

                                    {classe === 'terminees' && (
                                        <span
                                            className="btn-outline"
                                            style={{
                                                opacity: 0.5,
                                                cursor: 'not-allowed',
                                            }}
                                            title="Aperçu PDF bientôt disponible"
                                        >
                                            Voir l'attestation
                                        </span>
                                    )}
                                    {classe === 'a-venir' && (
                                        <span
                                            className="muted"
                                            style={{
                                                fontSize: 13,
                                                fontWeight: 600,
                                            }}
                                        >
                                            Débute dans{' '}
                                            {inscription.semainesAvantDebut}{' '}
                                            semaine
                                            {(inscription.semainesAvantDebut ??
                                                0) > 1
                                                ? 's'
                                                : ''}
                                        </span>
                                    )}
                                    {classe === 'en-cours' &&
                                        inscription.statut === 'VALIDEE' && (
                                            <Link
                                                href={espaceApprenant.progression.show(
                                                    inscription.id,
                                                )}
                                                className="btn-outline"
                                            >
                                                Voir ma progression
                                            </Link>
                                        )}
                                </div>

                                {classe === 'en-cours' &&
                                    inscription.statut === 'VALIDEE' && (
                                        <div style={{ marginTop: 16 }}>
                                            <div className="espace-formule-progress">
                                                <div
                                                    className="espace-formule-progress-bar"
                                                    style={{
                                                        width: `${inscription.progression}%`,
                                                    }}
                                                />
                                            </div>
                                            <p
                                                className="muted"
                                                style={{
                                                    marginTop: 6,
                                                    fontSize: 12,
                                                    textAlign: 'right',
                                                }}
                                            >
                                                {inscription.progression}%
                                            </p>
                                        </div>
                                    )}

                                <div style={{ marginTop: 16 }}>
                                    <SuiviProcedure
                                        parcours={inscription.parcours}
                                        messageTermine="Formation suivie : bravo !"
                                        compact
                                    >
                                        {inscription.parcours.prochaine
                                            ?.action === 'payer' && (
                                            <Link
                                                href={espaceApprenant.factures()}
                                                className="btn-primary"
                                            >
                                                Régler les frais
                                            </Link>
                                        )}
                                    </SuiviProcedure>
                                </div>

                                <EtatDossier inscription={inscription} />

                                {classe === 'terminees' && (
                                    <div
                                        className="espace-formule-progress"
                                        style={{ marginTop: 16 }}
                                    >
                                        <div
                                            className="espace-formule-progress-bar"
                                            style={{ width: '100%' }}
                                        />
                                    </div>
                                )}
                            </div>
                        );
                    })}
                </div>
            )}
        </>
    );
}

const ETAT_FRAIS: Record<string, { label: string; chip: string }> = {
    EN_ATTENTE: { label: 'À régler', chip: 'chip-orange' },
    PARTIELLEMENT_PAYEE: { label: 'Partiellement réglés', chip: 'chip-blue' },
    PAYEE: { label: 'Réglés', chip: 'chip-green' },
    EN_ECHEC: { label: 'Annulés', chip: 'chip-gray' },
};

/**
 * Registration fee of an enrolment and what the apprenant has to do next:
 * pay it, or wait for DAG to validate the seat once it is paid.
 */
function EtatFrais({ inscription }: { inscription: Inscription }) {
    const etat = ETAT_FRAIS[inscription.paiement ?? ''] ?? null;
    const aRegler =
        inscription.paiement === 'EN_ATTENTE' ||
        inscription.paiement === 'PARTIELLEMENT_PAYEE';

    return (
        <div
            style={{
                marginTop: 10,
                display: 'flex',
                alignItems: 'center',
                gap: 10,
                flexWrap: 'wrap',
                fontSize: 13,
            }}
        >
            <span>
                Frais d'inscription :{' '}
                <strong>
                    {libelleFraisInscription(inscription.fraisInscription)}
                </strong>
            </span>
            {etat && <span className={`chip ${etat.chip}`}>{etat.label}</span>}
            {aRegler && inscription.statut === 'EN_ATTENTE_PAIEMENT' && (
                <Link href={espaceApprenant.factures()} className="link-action">
                    Payer maintenant →
                </Link>
            )}
            {inscription.paiement === 'PAYEE' &&
                inscription.statut === 'EN_ATTENTE_PAIEMENT' && (
                    <span className="muted">
                        Paiement reçu : votre place va être confirmée.
                    </span>
                )}
            {inscription.paiement === null &&
                STATUTS_DOSSIER.includes(inscription.statut) && (
                    <span className="muted">
                        À régler une fois votre dossier validé.
                    </span>
                )}
        </div>
    );
}

const STATUTS_DOSSIER = ['DOSSIER_EN_ETUDE', 'DOSSIER_A_COMPLETER'];

/**
 * Where the enrolment dossier stands: under review, to be completed (with
 * the team's reason and the form to send the corrected pieces), or the
 * reason of a refusal.
 */
function EtatDossier({ inscription }: { inscription: Inscription }) {
    const deposees = Object.fromEntries(
        inscription.piecesDeposees.map((piece) => [
            piece.libelle,
            piece.nomOriginal,
        ]),
    );
    const form = useForm<{ pieces: (File | null)[] }>({
        pieces: inscription.piecesRequises.map(() => null),
    });

    if (inscription.statut === 'ABANDONNEE' && inscription.motif) {
        return (
            <p className="dossier-motif" style={{ marginTop: 14 }}>
                <strong>Motif du refus :</strong> {inscription.motif}
            </p>
        );
    }

    if (inscription.statut !== 'DOSSIER_A_COMPLETER') {
        return null;
    }

    const choisirPiece = (index: number, fichier: File | null) => {
        form.setData(
            'pieces',
            form.data.pieces.map((actuel, position) =>
                position === index ? fichier : actuel,
            ),
        );
    };

    const envoyer = (event: React.FormEvent) => {
        event.preventDefault();
        form.post(inscriptionsRoutes.completer(inscription.id).url, {
            forceFormData: true,
            preserveScroll: true,
        });
    };

    return (
        <form
            onSubmit={envoyer}
            style={{
                marginTop: 16,
                display: 'flex',
                flexDirection: 'column',
                gap: 12,
            }}
        >
            {inscription.motif && (
                <p className="dossier-motif">
                    <strong>À corriger :</strong> {inscription.motif}
                </p>
            )}
            <PiecesDossier
                pieces={inscription.piecesRequises}
                fichiers={form.data.pieces}
                onChange={choisirPiece}
                erreurs={form.errors as Record<string, string>}
                deposees={deposees}
            />
            {(form.errors as Record<string, string>).pieces && (
                <p
                    role="alert"
                    style={{ color: 'var(--dag-red)', fontSize: 12.5 }}
                >
                    {(form.errors as Record<string, string>).pieces}
                </p>
            )}
            <button
                type="submit"
                className="btn-primary"
                style={{ alignSelf: 'flex-start' }}
                disabled={form.processing || !form.data.pieces.some(Boolean)}
            >
                {form.processing ? 'Envoi…' : 'Envoyer les pièces corrigées'}
            </button>
        </form>
    );
}
