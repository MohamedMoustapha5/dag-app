import { Head, Link } from '@inertiajs/react';
import { STATUT_CHIP, STATUT_LABEL } from '@/lib/demande-statut';
import demandesAdmin from '@/routes/espace-admin/demandes';

type Demande = {
    id: string;
    reference: string;
    client: string;
    objet: string;
    statut: string;
    montantNegocie: string | null;
    updatedAt: string | null;
};

type Props = {
    demandes: Demande[];
};

export default function AdminDemandes({ demandes }: Props) {
    return (
        <>
            <Head title="Demandes clients — Administration" />

            <div
                className="espace-hero"
                style={{
                    backgroundImage:
                        "url('/images/photos/conference-business-report.jpg')",
                }}
            >
                <div className="espace-hero-overlay" />
                <div className="espace-hero-content">
                    <h1>Demandes clients</h1>
                    <p>
                        Suivez chaque demande de l'étude jusqu'à la livraison.
                    </p>
                </div>
            </div>

            <div className="card" style={{ padding: 24 }}>
                <p
                    style={{
                        fontSize: 14.5,
                        fontWeight: 700,
                        marginBottom: 16,
                    }}
                >
                    Toutes les demandes ({demandes.length})
                </p>
                {demandes.length === 0 ? (
                    <p className="data-table-empty">
                        Aucune demande pour le moment.
                    </p>
                ) : (
                    <div className="data-table-wrap">
                        <table className="data-table">
                            <thead>
                                <tr>
                                    <th>Référence</th>
                                    <th>Client</th>
                                    <th>Objet</th>
                                    <th>Statut</th>
                                    <th>Montant</th>
                                    <th>Mise à jour</th>
                                </tr>
                            </thead>
                            <tbody>
                                {demandes.map((demande) => (
                                    <tr key={demande.id}>
                                        <td>
                                            <Link
                                                href={demandesAdmin.show(
                                                    demande.id,
                                                )}
                                                className="link-action"
                                            >
                                                {demande.reference}
                                            </Link>
                                        </td>
                                        <td style={{ fontWeight: 600 }}>
                                            {demande.client}
                                        </td>
                                        <td className="muted">
                                            {demande.objet}
                                        </td>
                                        <td>
                                            <span
                                                className={`chip ${STATUT_CHIP[demande.statut] ?? 'chip-gray'}`}
                                            >
                                                {STATUT_LABEL[demande.statut] ??
                                                    demande.statut}
                                            </span>
                                        </td>
                                        <td className="muted">
                                            {demande.montantNegocie
                                                ? `${demande.montantNegocie} FCFA`
                                                : '—'}
                                        </td>
                                        <td className="muted">
                                            {demande.updatedAt}
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
