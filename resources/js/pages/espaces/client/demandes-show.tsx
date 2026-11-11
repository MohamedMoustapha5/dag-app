import { Head, Link } from '@inertiajs/react';
import { Download } from 'lucide-react';
import { useState } from 'react';
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
        } | null;
    };
    moyensPaiement: MoyenPaiement[];
    parcours: Parcours;
};

export default function DemandesShow({
    demande,
    parcours,
    moyensPaiement,
}: Props) {
    const [paiementOuvert, setPaiementOuvert] = useState(false);
    const facture = demande.facture;
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
                {parcours.prochaine?.action === 'payer' &&
                    aPayer &&
                    facture &&
                    (facture.paiementEnAttente ? (
                        <span className="muted" style={{ fontSize: 13 }}>
                            Paiement en attente de confirmation
                        </span>
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

            {(demande.contrat || demande.facture) && (
                <div className="grid-3">
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
                                (demande.facture.attendSignatureContrat ? (
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
                                    <p
                                        className="muted"
                                        style={{ fontSize: 12, marginTop: 12 }}
                                    >
                                        Paiement en attente de confirmation
                                    </p>
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
        </>
    );
}
