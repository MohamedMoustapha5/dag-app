import { Head, router } from '@inertiajs/react';
import { STATUT_CHIP, STATUT_LABEL } from '@/lib/demande-statut';
import clientsAdmin from '@/routes/espace-admin/clients';

type Client = {
    id: string;
    nom: string;
    email: string;
    type: string;
    typeLabel: string;
    ville: string | null;
    telephone: string | null;
    statut: string;
    demandesCount: number;
    inscritLe: string | null;
};

type Props = {
    clients: Client[];
    actions: {
        supprimer: boolean;
    };
};

export default function AdminClients({ clients, actions }: Props) {
    const supprimer = (client: Client) => {
        if (
            !confirm(
                `Supprimer le compte de « ${client.nom} » ? Cette action est définitive. Ses demandes, contrats et factures existants seront conservés mais ne seront plus rattachés à un compte client.`,
            )
        ) {
            return;
        }
        router.delete(clientsAdmin.destroy(client.id).url);
    };

    return (
        <>
            <Head title="Comptes clients — Administration" />

            <div
                className="espace-hero"
                style={{
                    backgroundImage:
                        "url('/images/photos/team-meeting-laptop.jpg')",
                }}
            >
                <div className="espace-hero-overlay" />
                <div className="espace-hero-content">
                    <h1>Comptes clients</h1>
                    <p>
                        {clients.length} compte{clients.length > 1 ? 's' : ''}{' '}
                        client{clients.length > 1 ? 's' : ''} enregistré
                        {clients.length > 1 ? 's' : ''}.
                    </p>
                </div>
            </div>

            <div className="card" style={{ padding: 24 }}>
                {clients.length === 0 ? (
                    <p className="muted" style={{ fontSize: 13 }}>
                        Aucun compte client pour le moment.
                    </p>
                ) : (
                    <div className="data-table-wrap">
                        <table className="data-table">
                            <thead>
                                <tr>
                                    <th>Client</th>
                                    <th>Type</th>
                                    <th>Ville</th>
                                    <th>Statut</th>
                                    <th>Demandes</th>
                                    <th>Inscrit le</th>
                                    <th />
                                </tr>
                            </thead>
                            <tbody>
                                {clients.map((client) => (
                                    <tr key={client.id}>
                                        <td>
                                            <p style={{ fontWeight: 600 }}>
                                                {client.nom}
                                            </p>
                                            <p
                                                className="muted"
                                                style={{ fontSize: 12.5 }}
                                            >
                                                {client.email}
                                                {client.telephone
                                                    ? ` · ${client.telephone}`
                                                    : ''}
                                            </p>
                                        </td>
                                        <td className="muted">
                                            {client.typeLabel}
                                        </td>
                                        <td className="muted">
                                            {client.ville ?? '—'}
                                        </td>
                                        <td>
                                            <span
                                                className={`chip ${STATUT_CHIP[client.statut] ?? 'chip-gray'}`}
                                            >
                                                {STATUT_LABEL[client.statut] ??
                                                    client.statut}
                                            </span>
                                        </td>
                                        <td className="muted">
                                            {client.demandesCount}
                                        </td>
                                        <td className="muted">
                                            {client.inscritLe ?? '—'}
                                        </td>
                                        <td style={{ textAlign: 'right' }}>
                                            {actions.supprimer && (
                                                <button
                                                    type="button"
                                                    onClick={() =>
                                                        supprimer(client)
                                                    }
                                                    className="link-action"
                                                    style={{
                                                        color: 'var(--dag-red)',
                                                    }}
                                                >
                                                    Supprimer
                                                </button>
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
