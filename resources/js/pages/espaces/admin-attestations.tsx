import { Head, router } from '@inertiajs/react';
import attestationsAdmin from '@/routes/espace-admin/attestations';

type EnAttente = {
    inscriptionId: string;
    apprenantNom: string;
    formation: string;
};

type Validee = {
    id: string;
    reference: string;
    apprenantNom: string;
    formation: string;
    dateEmission: string;
};

type Props = {
    enAttente: EnAttente[];
    validees: Validee[];
    peutTelecharger: boolean;
};

export default function AdminAttestations({
    enAttente,
    validees,
    peutTelecharger,
}: Props) {
    const valider = (inscriptionId: string) => {
        router.post(
            attestationsAdmin.store().url,
            { inscription_id: inscriptionId },
            { preserveScroll: true },
        );
    };

    return (
        <>
            <Head title="Attestations — Administration" />

            <div
                className="espace-hero"
                style={{
                    backgroundImage:
                        "url('/images/photos/presentation-data-review.jpg')",
                }}
            >
                <div className="espace-hero-overlay" />
                <div className="espace-hero-content">
                    <h1>Attestations</h1>
                    <p>
                        Une attestation n'est jamais délivrée automatiquement :
                        chaque formation terminée doit être validée ici avant
                        que l'apprenant n'y ait accès.
                    </p>
                </div>
            </div>

            <div className="card" style={{ padding: 24, marginBottom: 28 }}>
                <p
                    style={{
                        fontSize: 14.5,
                        fontWeight: 700,
                        marginBottom: 16,
                    }}
                >
                    En attente de validation ({enAttente.length})
                </p>
                {enAttente.length === 0 ? (
                    <p className="data-table-empty">
                        Aucune formation terminée en attente de validation.
                    </p>
                ) : (
                    <div className="data-table-wrap">
                        <table className="data-table">
                            <thead>
                                <tr>
                                    <th>Apprenant</th>
                                    <th>Formation</th>
                                    <th />
                                </tr>
                            </thead>
                            <tbody>
                                {enAttente.map((item) => (
                                    <tr key={item.inscriptionId}>
                                        <td style={{ fontWeight: 600 }}>
                                            {item.apprenantNom}
                                        </td>
                                        <td className="muted">
                                            {item.formation}
                                        </td>
                                        <td style={{ textAlign: 'right' }}>
                                            <button
                                                type="button"
                                                onClick={() =>
                                                    valider(item.inscriptionId)
                                                }
                                                className="btn-primary"
                                                style={{
                                                    padding: '7px 14px',
                                                    fontSize: 12.5,
                                                }}
                                            >
                                                Valider et générer
                                            </button>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </div>

            <div className="card" style={{ padding: 24 }}>
                <p
                    style={{
                        fontSize: 14.5,
                        fontWeight: 700,
                        marginBottom: 16,
                    }}
                >
                    Attestations validées ({validees.length})
                </p>
                {validees.length === 0 ? (
                    <p className="data-table-empty">
                        Aucune attestation validée pour le moment.
                    </p>
                ) : (
                    <div className="data-table-wrap">
                        <table className="data-table">
                            <thead>
                                <tr>
                                    <th>Apprenant</th>
                                    <th>Formation</th>
                                    <th>Référence</th>
                                    <th>Validée le</th>
                                    <th />
                                </tr>
                            </thead>
                            <tbody>
                                {validees.map((attestation) => (
                                    <tr key={attestation.id}>
                                        <td style={{ fontWeight: 600 }}>
                                            {attestation.apprenantNom}
                                        </td>
                                        <td className="muted">
                                            {attestation.formation}
                                        </td>
                                        <td className="muted">
                                            {attestation.reference}
                                        </td>
                                        <td className="muted">
                                            {attestation.dateEmission}
                                        </td>
                                        <td style={{ textAlign: 'right' }}>
                                            {peutTelecharger && (
                                                <a
                                                    href={
                                                        attestationsAdmin.pdf(
                                                            attestation.id,
                                                        ).url
                                                    }
                                                    className="link-action"
                                                >
                                                    Télécharger le PDF
                                                </a>
                                            )}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </div>
        </>
    );
}
