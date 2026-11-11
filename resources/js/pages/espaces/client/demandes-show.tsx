import { Head, Link } from '@inertiajs/react';
import { Download } from 'lucide-react';
import { useState } from 'react';
import PayerFactureModal, {
    type MoyenPaiement,
} from '@/components/espaces/payer-facture-modal';
import contratsRoutes from '@/routes/espace-client/contrats';
import facturesRoutes from '@/routes/espace-client/factures';
import demandes from '@/routes/espace-client/demandes';
import { STATUT_CHIP, STATUT_LABEL } from '@/lib/demande-statut';

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
        } | null;
    };
    moyensPaiement: MoyenPaiement[];
    suivi: {
        etapes: string[];
        etapeActuelle: number | null;
        refusee: boolean;
    };
};

export default function DemandesShow({
    demande,
    suivi,
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

            {suivi.refusee ? (
                <div
                    className="card"
                    style={{
                        padding: 24,
                        marginBottom: 32,
                        borderColor: 'var(--dag-red)',
                    }}
                >
                    <p
                        style={{
                            color: 'var(--dag-red)',
                            fontWeight: 600,
                            fontSize: 14,
                        }}
                    >
                        Cette demande a été refusée.
                    </p>
                </div>
            ) : (
                <div
                    className="process-track-card"
                    style={{ marginBottom: 32 }}
                >
                    {suivi.etapes.map((etape, index) => (
                        <div
                            key={etape}
                            style={{ display: 'flex', alignItems: 'center' }}
                        >
                            <div className="process-step">
                                <span
                                    className={`process-step-dot${suivi.etapeActuelle !== null && index <= suivi.etapeActuelle ? ' done' : ''}`}
                                >
                                    {index + 1}
                                </span>
                                <span className="process-step-label">
                                    {STATUT_LABEL[etape] ?? etape}
                                </span>
                            </div>
                            {index < suivi.etapes.length - 1 && (
                                <span className="process-step-arrow">→</span>
                            )}
                        </div>
                    ))}
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
                        <strong>{demande.montantNegocie} FCFA</strong>
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

            {!suivi.refusee && (
                <ProchaineEtape
                    demande={demande}
                    aPayer={aPayer}
                    onPayer={() => setPaiementOuvert(true)}
                />
            )}

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
                                    style={{ display: 'block', marginTop: 12 }}
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
                                {demande.facture.montantPaye} /{' '}
                                {demande.facture.montant} FCFA payés
                            </p>
                            {aPayer &&
                                (demande.facture.attendSignatureContrat ? (
                                    <p
                                        className="muted"
                                        style={{ fontSize: 12, marginTop: 12 }}
                                    >
                                        Le paiement s'ouvre après la signature
                                        du contrat.
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
                                        Payer {demande.facture.solde} FCFA
                                    </button>
                                ))}
                            {demande.facture.statut === 'PAYEE' && (
                                <a
                                    href={
                                        facturesRoutes.pdf(demande.facture.id)
                                            .url
                                    }
                                    className="link-action"
                                    style={{ display: 'block', marginTop: 12 }}
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

/**
 * Tells the client what to do next: the contract sent with the devis is
 * signed first (step 1), then the facture is paid (step 2).
 */
function ProchaineEtape({
    demande,
    aPayer,
    onPayer,
}: {
    demande: Props['demande'];
    aPayer: boolean;
    onPayer: () => void;
}) {
    const contrat = demande.contrat;
    const facture = demande.facture;

    let etape: string | null = null;
    let message: string;
    let action: React.ReactNode = null;

    if (facture === null) {
        return null;
    }

    if (facture.statut === 'PAYEE') {
        message = contrat?.estSigne
            ? "Contrat signé et paiement reçu : l'équipe DAG lance votre prestation. Vous suivez son avancement ici."
            : "Paiement reçu. L'équipe DAG vous tient informé de la suite ici.";
    } else if (facture.attendSignatureContrat) {
        etape = 'Étape 1 sur 2 — Signez votre contrat';
        if (contrat?.envoye) {
            message =
                "Lisez le contrat qui accompagne votre devis, puis signez-le. Le paiement de la facture s'ouvre juste après la signature.";
            action = (
                <Link
                    href={contratsRoutes.show(contrat.id)}
                    className="btn-primary"
                    style={{ padding: '8px 18px', fontSize: 13 }}
                >
                    Lire et signer le contrat
                </Link>
            );
        } else {
            message =
                "Votre contrat est en cours de finalisation par l'équipe DAG. Vous le recevrez ici pour signature, puis vous pourrez régler la facture.";
        }
    } else if (aPayer) {
        etape = 'Étape 2 sur 2 — Réglez votre facture';
        message = facture.paiementEnAttente
            ? 'Votre paiement est en attente de confirmation par le prestataire.'
            : "Votre contrat est signé. Réglez la facture pour que l'équipe DAG lance la prestation.";
        action = facture.paiementEnAttente ? null : (
            <button
                type="button"
                className="btn-primary"
                style={{ padding: '8px 18px', fontSize: 13 }}
                onClick={onPayer}
            >
                Payer {facture.solde} FCFA
            </button>
        );
    } else {
        return null;
    }

    return (
        <div
            className="card"
            style={{
                padding: 18,
                marginBottom: 20,
                background: 'var(--dag-blue-light)',
                borderColor: 'transparent',
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'space-between',
                gap: 16,
                flexWrap: 'wrap',
            }}
        >
            <div style={{ flex: '1 1 280px' }}>
                {etape && (
                    <p
                        style={{
                            fontWeight: 600,
                            fontSize: 14,
                            marginBottom: 4,
                        }}
                    >
                        {etape}
                    </p>
                )}
                <p style={{ fontSize: 13.5, lineHeight: 1.6 }}>{message}</p>
            </div>
            {action}
        </div>
    );
}
