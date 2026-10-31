import { Head, Link } from '@inertiajs/react';
import { Download } from 'lucide-react';
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
        createdAt: string;
        contrat: {
            reference: string;
            statut: string;
            fichierPdf: string | null;
        } | null;
        facture: {
            reference: string;
            statut: string;
            montant: string;
            montantPaye: string;
        } | null;
    };
    suivi: {
        etapes: string[];
        etapeActuelle: number | null;
        refusee: boolean;
    };
};

export default function DemandesShow({ demande, suivi }: Props) {
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
                            {demande.contrat.fichierPdf && (
                                <a
                                    href={demande.contrat.fichierPdf}
                                    target="_blank"
                                    rel="noreferrer"
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
                        </div>
                    )}
                </div>
            )}
        </>
    );
}
