import { Head, Link, router, useForm } from '@inertiajs/react';
import {
    CheckCircle2,
    Download,
    FileText,
    MessageSquareX,
    X,
} from 'lucide-react';
import { useState } from 'react';
import ReprendrePaiement from '@/components/espaces/reprendre-paiement';
import PayerFactureModal, {
    type MoyenPaiement,
} from '@/components/espaces/payer-facture-modal';
import contratsRoutes from '@/routes/espace-client/contrats';
import facturesRoutes from '@/routes/espace-client/factures';
import demandes from '@/routes/espace-client/demandes';
import SuiviProcedure, {
    type Parcours,
} from '@/components/espaces/suivi-procedure';
import { STATUT_CHIP, STATUT_LABEL } from '@/lib/demande-statut';
import { formatMontant } from '@/lib/montant';

type Props = {
    demande: {
        id: string;
        reference: string;
        objet: string;
        description: string;
        statut: string;
        montantNegocie: string | null;
        messageDevis: string | null;
        createdAt: string;
        estParticulier: boolean;
        exigeContrat: boolean;
        devis: {
            numero: string;
            valideLe: string | null;
            peutValider: boolean;
        } | null;
        refusDevis: {
            le: string;
            motif: string | null;
            abandon: boolean;
        } | null;
        contrat: {
            reference: string;
            statut: string;
            id: string;
            estSigne: boolean;
            envoye: boolean;
        } | null;
        facture: {
            id: string;
            reference: string;
            statut: string;
            montant: string;
            montantPaye: string;
            solde: string;
            paiementEnAttente: boolean;
            attendSignatureContrat: boolean;
            attendValidationContrat: boolean;
            attendValidationDevis: boolean;
        } | null;
    };
    moyensPaiement: MoyenPaiement[];
    motifsRefusDevis: MotifRefus[];
    parcours: Parcours;
};

type MotifRefus = { value: string; label: string };

/** "21 novembre 2026" from an ISO date. */
function dateLongue(iso: string): string {
    return new Date(iso).toLocaleDateString('fr-FR', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });
}

export default function DemandesShow({
    demande,
    parcours,
    moyensPaiement,
    motifsRefusDevis,
}: Props) {
    const [paiementOuvert, setPaiementOuvert] = useState(false);
    const [validationOuverte, setValidationOuverte] = useState(false);
    const [refusOuvert, setRefusOuvert] = useState(false);
    const refus = demande.refusDevis;
    const facture = demande.facture;
    const devis = demande.devis;
    const aPayer =
        facture !== null &&
        (facture.statut === 'EN_ATTENTE' ||
            facture.statut === 'PARTIELLEMENT_PAYEE');

    return (
        <>
            <Head title={`Demande ${demande.reference}`} />

            <Link
                href={demandes.index()}
                className="link-action"
                style={{ marginBottom: 16, display: 'inline-block' }}
            >
                ← Toutes mes demandes
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
                <div>
                    <h1 style={{ fontSize: 22, color: 'var(--dag-navy)' }}>
                        {demande.objet}
                    </h1>
                    <span className="muted" style={{ fontSize: 13 }}>
                        {demande.reference}
                    </span>
                </div>
                <span
                    className={`chip ${STATUT_CHIP[demande.statut] ?? 'chip-gray'}`}
                    style={{ fontSize: 13, padding: '8px 16px' }}
                >
                    {STATUT_LABEL[demande.statut] ?? demande.statut}
                </span>
            </div>

            <SuiviProcedure
                parcours={parcours}
                messageTermine="Votre demande est terminée. Merci de votre confiance !"
            >
                {parcours.prochaine?.action === 'signer_contrat' &&
                    demande.contrat && (
                        <Link
                            href={contratsRoutes.show(demande.contrat.id)}
                            className="btn-primary"
                        >
                            Lire et signer le contrat
                        </Link>
                    )}
                {parcours.prochaine?.action === 'valider_devis' &&
                    devis?.peutValider && (
                        <>
                            <a
                                href={demandes.proforma(demande.id).url}
                                className="btn-outline"
                            >
                                Consulter le devis
                            </a>
                            <button
                                type="button"
                                className="btn-outline btn-refus"
                                onClick={() => setRefusOuvert(true)}
                            >
                                Refuser
                            </button>
                            <button
                                type="button"
                                className="btn-primary"
                                onClick={() => setValidationOuverte(true)}
                            >
                                Valider le devis
                            </button>
                        </>
                    )}
                {parcours.prochaine?.action === 'payer' &&
                    aPayer &&
                    facture &&
                    (facture.paiementEnAttente ? (
                        <ReprendrePaiement factureId={facture.id} />
                    ) : (
                        <button
                            type="button"
                            className="btn-primary"
                            onClick={() => setPaiementOuvert(true)}
                        >
                            Payer {formatMontant(facture.solde)}
                        </button>
                    ))}
            </SuiviProcedure>

            {refus && (
                <div
                    className="card devis-refuse"
                    style={{ padding: 20, marginBottom: 24 }}
                >
                    <p
                        style={{
                            fontWeight: 700,
                            display: 'flex',
                            alignItems: 'center',
                            gap: 8,
                        }}
                    >
                        <MessageSquareX size={16} aria-hidden="true" />
                        Devis refusé le {dateLongue(refus.le)}
                    </p>
                    {refus.motif && (
                        <p style={{ fontSize: 13.5, marginTop: 6 }}>
                            Motif : {refus.motif}
                        </p>
                    )}
                    <p className="muted" style={{ fontSize: 13, marginTop: 6 }}>
                        {refus.abandon
                            ? 'Vous ne donnez pas suite : votre demande est close. Vous pouvez en déposer une nouvelle à tout moment.'
                            : 'L’équipe DAG prépare une nouvelle proposition : vous la recevrez ici et par e-mail.'}
                    </p>
                </div>
            )}

            <div className="card" style={{ padding: 24, marginBottom: 24 }}>
                <h2 style={{ fontSize: 15, marginBottom: 8 }}>Description</h2>
                <p
                    className="muted"
                    style={{ fontSize: 13.5, lineHeight: 1.6 }}
                >
                    {demande.description}
                </p>
                {demande.montantNegocie && (
                    <p style={{ marginTop: 12, fontSize: 13.5 }}>
                        Montant négocié :{' '}
                        <strong>{formatMontant(demande.montantNegocie)}</strong>
                    </p>
                )}
                {demande.messageDevis && (
                    <p
                        className="muted"
                        style={{ marginTop: 8, fontSize: 13.5 }}
                    >
                        Message de l'équipe : {demande.messageDevis}
                    </p>
                )}
            </div>

            {(devis || demande.contrat || demande.facture) && (
                <div className="grid-3">
                    {devis && (
                        <div className="card" style={{ padding: 20 }}>
                            <p
                                className="muted"
                                style={{
                                    fontSize: 12.5,
                                    display: 'flex',
                                    alignItems: 'center',
                                    gap: 6,
                                }}
                            >
                                <FileText size={14} aria-hidden="true" />
                                Devis proforma
                            </p>
                            <p style={{ fontWeight: 600, marginBottom: 6 }}>
                                {devis.numero}
                            </p>
                            {devis.valideLe ? (
                                <span className="chip chip-green">
                                    Validé le {dateLongue(devis.valideLe)}
                                </span>
                            ) : (
                                <span className="chip chip-orange">
                                    À valider
                                </span>
                            )}
                            {demande.montantNegocie && (
                                <p style={{ fontSize: 13, marginTop: 8 }}>
                                    {formatMontant(demande.montantNegocie)}
                                </p>
                            )}
                            <div
                                style={{
                                    display: 'flex',
                                    flexWrap: 'wrap',
                                    alignItems: 'center',
                                    gap: 12,
                                    marginTop: 12,
                                }}
                            >
                                {devis.peutValider && (
                                    <button
                                        type="button"
                                        className="btn-primary"
                                        style={{
                                            padding: '8px 18px',
                                            fontSize: 13,
                                        }}
                                        onClick={() =>
                                            setValidationOuverte(true)
                                        }
                                    >
                                        Valider le devis
                                    </button>
                                )}
                                <a
                                    href={demandes.proforma(demande.id).url}
                                    className="link-action"
                                    style={{
                                        display: 'inline-flex',
                                        alignItems: 'center',
                                        gap: 6,
                                    }}
                                >
                                    <Download size={14} /> Télécharger le
                                    devis
                                </a>
                                {devis.peutValider && (
                                    <button
                                        type="button"
                                        className="link-action link-refus"
                                        onClick={() => setRefusOuvert(true)}
                                    >
                                        Refuser le devis
                                    </button>
                                )}
                            </div>
                        </div>
                    )}
                    {demande.contrat && (
                        <div className="card" style={{ padding: 20 }}>
                            <p className="muted" style={{ fontSize: 12.5 }}>
                                Contrat
                            </p>
                            <p style={{ fontWeight: 600, marginBottom: 6 }}>
                                {demande.contrat.reference}
                            </p>
                            <span
                                className={`chip ${STATUT_CHIP[demande.contrat.statut] ?? 'chip-gray'}`}
                            >
                                {STATUT_LABEL[demande.contrat.statut] ??
                                    demande.contrat.statut}
                            </span>
                            {demande.contrat.envoye && (
                                <Link
                                    href={contratsRoutes.show(
                                        demande.contrat.id,
                                    )}
                                    className="btn-primary"
                                    style={{
                                        display: 'inline-block',
                                        marginTop: 12,
                                        padding: '8px 18px',
                                        fontSize: 13,
                                    }}
                                >
                                    Lire et signer
                                </Link>
                            )}
                            {demande.contrat.estSigne && (
                                <a
                                    href={
                                        contratsRoutes.pdf(demande.contrat.id)
                                            .url
                                    }
                                    className="link-action"
                                    style={{
                                        display: 'inline-flex',
                                        alignItems: 'center',
                                        gap: 6,
                                        marginTop: 12,
                                    }}
                                >
                                    <Download size={14} /> Télécharger le PDF
                                </a>
                            )}
                        </div>
                    )}
                    {demande.facture && (
                        <div className="card" style={{ padding: 20 }}>
                            <p className="muted" style={{ fontSize: 12.5 }}>
                                Facture
                            </p>
                            <p style={{ fontWeight: 600, marginBottom: 6 }}>
                                {demande.facture.reference}
                            </p>
                            <span
                                className={`chip ${STATUT_CHIP[demande.facture.statut] ?? 'chip-gray'}`}
                            >
                                {STATUT_LABEL[demande.facture.statut] ??
                                    demande.facture.statut}
                            </span>
                            <p
                                className="muted"
                                style={{ fontSize: 12.5, marginTop: 8 }}
                            >
                                {formatMontant(
                                    demande.facture.montantPaye,
                                ).replace(' FCFA', '')}{' '}
                                / {formatMontant(demande.facture.montant)} payés
                            </p>
                            {aPayer &&
                                (demande.facture.attendValidationDevis ? (
                                    <p
                                        className="muted"
                                        style={{ fontSize: 12, marginTop: 12 }}
                                    >
                                        Le paiement s'ouvre dès que vous
                                        validez le devis.
                                    </p>
                                ) : demande.facture.attendSignatureContrat ? (
                                    <p
                                        className="muted"
                                        style={{ fontSize: 12, marginTop: 12 }}
                                    >
                                        Le paiement s'ouvre après la signature
                                        du contrat et sa validation par DAG.
                                    </p>
                                ) : demande.facture.attendValidationContrat ? (
                                    <p
                                        className="muted"
                                        style={{ fontSize: 12, marginTop: 12 }}
                                    >
                                        Contrat signé, en cours de validation
                                        par DAG : le paiement s'ouvre ensuite.
                                    </p>
                                ) : demande.facture.paiementEnAttente ? (
                                    <div style={{ marginTop: 12 }}>
                                        <ReprendrePaiement
                                            factureId={demande.facture.id}
                                            alignement="start"
                                        />
                                    </div>
                                ) : (
                                    <button
                                        type="button"
                                        className="btn-primary"
                                        style={{
                                            marginTop: 12,
                                            padding: '8px 18px',
                                            fontSize: 13,
                                        }}
                                        onClick={() => setPaiementOuvert(true)}
                                    >
                                        Payer{' '}
                                        {formatMontant(demande.facture.solde)}
                                    </button>
                                ))}
                            {demande.facture.statut === 'PAYEE' && (
                                <a
                                    href={
                                        facturesRoutes.pdf(demande.facture.id)
                                            .url
                                    }
                                    className="link-action"
                                    style={{
                                        display: 'inline-flex',
                                        alignItems: 'center',
                                        gap: 6,
                                        marginTop: 12,
                                    }}
                                >
                                    <Download size={14} /> Télécharger la
                                    facture
                                </a>
                            )}
                        </div>
                    )}
                </div>
            )}

            {paiementOuvert && facture && (
                <PayerFactureModal
                    facture={{
                        id: facture.id,
                        reference: facture.reference,
                        objet: demande.objet,
                        solde: facture.solde,
                    }}
                    moyens={moyensPaiement}
                    onClose={() => setPaiementOuvert(false)}
                />
            )}

            {validationOuverte && devis && (
                <ValiderDevisModal
                    demandeId={demande.id}
                    numero={devis.numero}
                    objet={demande.objet}
                    montant={demande.montantNegocie}
                    particulier={!demande.exigeContrat}
                    onClose={() => setValidationOuverte(false)}
                />
            )}

            {refusOuvert && devis && (
                <RefuserDevisModal
                    demandeId={demande.id}
                    numero={devis.numero}
                    motifs={motifsRefusDevis}
                    onClose={() => setRefusOuvert(false)}
                />
            )}
        </>
    );
}

/**
 * Declining the devis: a reason, an optional comment, and whether the client
 * wants a revised proposal or does not wish to go on.
 */
function RefuserDevisModal({
    demandeId,
    numero,
    motifs,
    onClose,
}: {
    demandeId: string;
    numero: string;
    motifs: MotifRefus[];
    onClose: () => void;
}) {
    const form = useForm({
        motif: motifs[0]?.value ?? '',
        commentaire: '',
        suite: 'nouveau_devis',
    });

    const envoyer = (event: React.FormEvent) => {
        event.preventDefault();
        form.post(demandes.devis.refuser(demandeId).url, {
            preserveScroll: true,
            onSuccess: onClose,
        });
    };

    const erreur = (message?: string) =>
        message ? (
            <p style={{ color: 'var(--dag-red)', fontSize: 12, marginTop: 4 }}>
                {message}
            </p>
        ) : null;

    return (
        <div
            className="modal-backdrop"
            role="dialog"
            aria-modal="true"
            aria-label="Refuser le devis"
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
                    Refuser le devis
                </h2>
                <p
                    style={{
                        marginTop: 8,
                        fontSize: 13.5,
                        lineHeight: 1.6,
                        color: 'var(--dag-text-muted)',
                    }}
                >
                    Dites-nous ce qui ne vous convient pas dans le devis{' '}
                    <strong>{numero}</strong> : l’équipe DAG en tient compte.
                </p>

                <form
                    onSubmit={envoyer}
                    style={{
                        display: 'flex',
                        flexDirection: 'column',
                        gap: 18,
                        marginTop: 20,
                    }}
                >
                    <fieldset className="choix-liste">
                        <legend>Raison du refus</legend>
                        {motifs.map((motif) => (
                            <label key={motif.value} className="choix">
                                <input
                                    type="radio"
                                    name="motif"
                                    value={motif.value}
                                    checked={form.data.motif === motif.value}
                                    onChange={() =>
                                        form.setData('motif', motif.value)
                                    }
                                />
                                <span>{motif.label}</span>
                            </label>
                        ))}
                        {erreur(form.errors.motif)}
                    </fieldset>

                    <div>
                        <label htmlFor="refus-commentaire">
                            Précisions{' '}
                            {form.data.motif === 'AUTRE'
                                ? '(obligatoire)'
                                : '(facultatif)'}
                        </label>
                        <textarea
                            id="refus-commentaire"
                            rows={3}
                            maxLength={1000}
                            value={form.data.commentaire}
                            placeholder="Par exemple : budget visé, délai souhaité…"
                            onChange={(event) =>
                                form.setData('commentaire', event.target.value)
                            }
                        />
                        {erreur(form.errors.commentaire)}
                    </div>

                    <fieldset className="choix-liste">
                        <legend>Et ensuite ?</legend>
                        <label className="choix">
                            <input
                                type="radio"
                                name="suite"
                                value="nouveau_devis"
                                checked={form.data.suite === 'nouveau_devis'}
                                onChange={() =>
                                    form.setData('suite', 'nouveau_devis')
                                }
                            />
                            <span>
                                <strong>
                                    Je souhaite une nouvelle proposition
                                </strong>
                                <small>
                                    DAG revoit le devis et vous l’envoie ici.
                                </small>
                            </span>
                        </label>
                        <label className="choix">
                            <input
                                type="radio"
                                name="suite"
                                value="abandon"
                                checked={form.data.suite === 'abandon'}
                                onChange={() => form.setData('suite', 'abandon')}
                            />
                            <span>
                                <strong>Je ne donne pas suite</strong>
                                <small>Votre demande sera close.</small>
                            </span>
                        </label>
                        {erreur(form.errors.suite)}
                    </fieldset>

                    <div
                        style={{
                            display: 'flex',
                            justifyContent: 'flex-end',
                            gap: 12,
                        }}
                    >
                        <button
                            type="button"
                            className="btn-outline"
                            onClick={onClose}
                        >
                            Annuler
                        </button>
                        <button
                            type="submit"
                            className="btn-primary"
                            disabled={form.processing}
                        >
                            {form.processing
                                ? 'Envoi…'
                                : 'Confirmer le refus'}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    );
}

/**
 * Confirmation before the client accepts the devis: what they agree to,
 * and that the payment opens right after.
 */
function ValiderDevisModal({
    demandeId,
    numero,
    objet,
    montant,
    particulier,
    onClose,
}: {
    demandeId: string;
    numero: string;
    objet: string;
    montant: string | null;
    particulier: boolean;
    onClose: () => void;
}) {
    const [envoi, setEnvoi] = useState(false);

    const valider = () => {
        router.post(
            demandes.devis.valider(demandeId).url,
            {},
            {
                preserveScroll: true,
                onStart: () => setEnvoi(true),
                onFinish: () => setEnvoi(false),
                onSuccess: onClose,
            },
        );
    };

    return (
        <div
            className="modal-backdrop"
            role="dialog"
            aria-modal="true"
            aria-label="Valider le devis"
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

                <h2
                    style={{
                        fontSize: 19,
                        color: 'var(--dag-navy)',
                        display: 'flex',
                        alignItems: 'center',
                        gap: 8,
                    }}
                >
                    <CheckCircle2
                        size={20}
                        color="var(--dag-green)"
                        aria-hidden="true"
                    />
                    Valider le devis
                </h2>
                <p
                    style={{
                        marginTop: 10,
                        fontSize: 13.5,
                        lineHeight: 1.6,
                        color: 'var(--dag-text-muted)',
                    }}
                >
                    Vous acceptez le devis <strong>{numero}</strong> pour «{' '}
                    {objet} »
                    {montant ? (
                        <>
                            , d'un montant de{' '}
                            <strong>{formatMontant(montant)}</strong>
                        </>
                    ) : null}

                    {particulier
                        ? ". Le paiement s'ouvre aussitôt, et la prestation démarre dès réception de votre règlement."
                        : '. Votre contrat vous est aussitôt envoyé pour signature ; après sa validation par DAG, vous réglez la facture.'}
                </p>

                <div
                    style={{
                        display: 'flex',
                        justifyContent: 'flex-end',
                        flexWrap: 'wrap',
                        gap: 12,
                        marginTop: 24,
                    }}
                >
                    <a
                        href={demandes.proforma(demandeId).url}
                        className="btn-outline"
                    >
                        Relire le devis
                    </a>
                    <button
                        type="button"
                        className="btn-primary"
                        disabled={envoi}
                        onClick={valider}
                    >
                        {envoi ? 'Validation…' : 'Je valide le devis'}
                    </button>
                </div>
            </div>
        </div>
    );
}
