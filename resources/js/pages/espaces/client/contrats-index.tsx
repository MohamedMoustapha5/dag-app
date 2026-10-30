import { Head } from '@inertiajs/react';
import { FileSignature, Shield } from 'lucide-react';
import EspaceClientLayout, {
    type EspaceClientInfo,
} from '@/layouts/espace-client-layout';
import { STATUT_CHIP, STATUT_LABEL } from '@/lib/demande-statut';

type Contrat = {
    id: string;
    reference: string;
    objet: string;
    statut: string;
    fichierPdf: string | null;
};

type Props = {
    client: EspaceClientInfo;
    contrats: Contrat[];
};

export default function ContratsIndex({ client, contrats }: Props) {
    return (
        <EspaceClientLayout client={client}>
            <Head title="Contrats" />

            <div
                className="espace-hero"
                style={{
                    backgroundImage:
                        "url('/images/photos/handshake-dashboard.jpg')",
                }}
            >
                <div className="espace-hero-overlay" />
                <div className="espace-hero-content">
                    <h1>Contrats</h1>
                    <p>
                        Importez vous-même la photo de votre signature pour
                        activer un contrat.
                    </p>
                </div>
            </div>

            {contrats.length === 0 ? (
                <div
                    className="card"
                    style={{ padding: 40, textAlign: 'center' }}
                >
                    <p className="muted" style={{ fontSize: 13.5 }}>
                        Aucun contrat pour le moment.
                    </p>
                </div>
            ) : (
                <div
                    style={{
                        display: 'flex',
                        flexDirection: 'column',
                        gap: 12,
                    }}
                >
                    {contrats.map((contrat) => {
                        const estAbonnement =
                            contrat.objet.startsWith('Abonnement');
                        const estSigne =
                            contrat.statut === 'SIGNE' ||
                            contrat.statut === 'ACTIF';

                        return (
                            <div
                                key={contrat.id}
                                className="card"
                                style={{
                                    padding: 20,
                                    display: 'flex',
                                    alignItems: 'center',
                                    justifyContent: 'space-between',
                                    gap: 16,
                                    flexWrap: 'wrap',
                                }}
                            >
                                <div
                                    style={{
                                        display: 'flex',
                                        alignItems: 'center',
                                        gap: 14,
                                    }}
                                >
                                    <span
                                        style={{
                                            width: 40,
                                            height: 40,
                                            borderRadius: 10,
                                            display: 'flex',
                                            alignItems: 'center',
                                            justifyContent: 'center',
                                            background: estSigne
                                                ? 'var(--dag-green-bg)'
                                                : 'var(--dag-orange-bg)',
                                            color: estSigne
                                                ? 'var(--dag-green)'
                                                : 'var(--dag-orange-ink)',
                                            flex: 'none',
                                        }}
                                    >
                                        {estAbonnement ? (
                                            <Shield size={18} />
                                        ) : (
                                            <FileSignature size={18} />
                                        )}
                                    </span>
                                    <div>
                                        <p
                                            style={{
                                                fontWeight: 600,
                                                fontSize: 14.5,
                                            }}
                                        >
                                            {contrat.objet}
                                        </p>
                                        <span
                                            className="muted"
                                            style={{ fontSize: 12.5 }}
                                        >
                                            Réf. {contrat.reference} ·{' '}
                                            {STATUT_LABEL[contrat.statut] ??
                                                contrat.statut}
                                        </span>
                                    </div>
                                </div>

                                <div
                                    style={{
                                        display: 'flex',
                                        alignItems: 'center',
                                        gap: 14,
                                    }}
                                >
                                    <span
                                        className={`chip ${STATUT_CHIP[contrat.statut] ?? 'chip-gray'}`}
                                    >
                                        {STATUT_LABEL[contrat.statut] ??
                                            contrat.statut}
                                    </span>
                                    {contrat.statut === 'ENVOYE' ? (
                                        <button
                                            type="button"
                                            className="btn-primary"
                                            disabled
                                            title="Disponible prochainement"
                                        >
                                            Importer ma signature
                                        </button>
                                    ) : contrat.fichierPdf ? (
                                        <a
                                            href={contrat.fichierPdf}
                                            target="_blank"
                                            rel="noreferrer"
                                            className="link-action"
                                        >
                                            Voir
                                        </a>
                                    ) : (
                                        <span
                                            className="link-action"
                                            style={{
                                                opacity: 0.5,
                                                cursor: 'not-allowed',
                                            }}
                                            title="Document bientôt disponible"
                                        >
                                            Voir
                                        </span>
                                    )}
                                </div>
                            </div>
                        );
                    })}
                </div>
            )}
        </EspaceClientLayout>
    );
}
