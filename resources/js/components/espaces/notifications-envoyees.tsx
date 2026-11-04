import { router } from '@inertiajs/react';
import { Search } from 'lucide-react';
import { useState } from 'react';
import { statistiques } from '@/routes/espace-admin';

export type NotificationsData = {
    entrees: {
        id: string;
        destinataire: string | null;
        canal: string;
        evenement: string;
        titre: string | null;
        statut: string;
        date: string | null;
    }[];
    page: number;
    dernierePage: number;
    total: number;
    precedente: string | null;
    suivante: string | null;
    canauxActifs: { email: boolean; sms: boolean; whatsapp: boolean };
    filtres: { q: string; statut: string };
};

const CANAL_LABEL: Record<string, string> = {
    EMAIL: 'E-mail',
    SMS: 'SMS',
    WHATSAPP: 'WhatsApp',
};

const STATUT: Record<string, { label: string; chip: string }> = {
    ENVOYE: { label: 'Envoyée', chip: 'chip-green' },
    ECHEC: { label: 'Échec', chip: 'chip-red' },
    EN_ATTENTE: { label: 'En attente', chip: 'chip-orange' },
};

export default function NotificationsEnvoyees({
    notifications,
}: {
    notifications: NotificationsData;
}) {
    const [recherche, setRecherche] = useState(notifications.filtres.q);

    const filtrer = (q: string, statut: string) => {
        router.get(
            statistiques().url,
            {
                onglet: 'notifications',
                ...(q !== '' ? { q } : {}),
                ...(statut !== '' ? { statut } : {}),
            },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    };

    const canaux = [
        ['E-mail', notifications.canauxActifs.email],
        ['SMS', notifications.canauxActifs.sms],
        ['WhatsApp', notifications.canauxActifs.whatsapp],
    ] as const;

    return (
        <div className="card" style={{ padding: 24 }}>
            <div
                style={{
                    display: 'flex',
                    justifyContent: 'space-between',
                    alignItems: 'center',
                    gap: 16,
                    flexWrap: 'wrap',
                    marginBottom: 14,
                }}
            >
                <div>
                    <p style={{ fontSize: 15, fontWeight: 700 }}>
                        Notifications envoyées
                    </p>
                    <p
                        className="muted"
                        style={{ fontSize: 12.5, marginTop: 2 }}
                    >
                        {notifications.total} notification
                        {notifications.total > 1 ? 's' : ''}, tous canaux
                        confondus.
                    </p>
                </div>

                <form
                    onSubmit={(event) => {
                        event.preventDefault();
                        filtrer(recherche, notifications.filtres.statut);
                    }}
                    style={{
                        display: 'flex',
                        gap: 10,
                        flexWrap: 'wrap',
                        alignItems: 'center',
                    }}
                >
                    <select
                        aria-label="Filtrer par statut"
                        style={{ width: 170 }}
                        value={notifications.filtres.statut}
                        onChange={(event) =>
                            filtrer(recherche, event.target.value)
                        }
                    >
                        <option value="">Tous les statuts</option>
                        <option value="ENVOYE">Envoyées</option>
                        <option value="ECHEC">En échec</option>
                    </select>
                    <div style={{ position: 'relative', width: 230 }}>
                        <Search
                            size={15}
                            style={{
                                position: 'absolute',
                                left: 11,
                                top: '50%',
                                transform: 'translateY(-50%)',
                                color: 'var(--dag-text-muted)',
                            }}
                        />
                        <input
                            type="search"
                            placeholder="Rechercher"
                            aria-label="Rechercher une notification"
                            value={recherche}
                            onChange={(event) =>
                                setRecherche(event.target.value)
                            }
                            style={{ paddingLeft: 34, width: '100%' }}
                        />
                    </div>
                </form>
            </div>

            <div
                style={{
                    display: 'flex',
                    gap: 8,
                    flexWrap: 'wrap',
                    marginBottom: 16,
                }}
            >
                {canaux.map(([label, actif]) => (
                    <span
                        key={label}
                        className={`chip ${actif ? 'chip-green' : 'chip-gray'}`}
                    >
                        {label} : {actif ? 'actif' : 'à venir'}
                    </span>
                ))}
            </div>

            {notifications.entrees.length === 0 ? (
                <p className="data-table-empty">
                    Aucune notification ne correspond.
                </p>
            ) : (
                <div className="data-table-wrap">
                    <table className="data-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Destinataire</th>
                                <th>Notification</th>
                                <th>Canal</th>
                                <th>Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            {notifications.entrees.map((entree) => (
                                <tr key={entree.id}>
                                    <td className="muted">{entree.date}</td>
                                    <td>{entree.destinataire ?? '—'}</td>
                                    <td>
                                        <p style={{ fontWeight: 600 }}>
                                            {entree.titre}
                                        </p>
                                        <p
                                            className="muted"
                                            style={{ fontSize: 12 }}
                                        >
                                            {entree.evenement}
                                        </p>
                                    </td>
                                    <td className="muted">
                                        {CANAL_LABEL[entree.canal] ??
                                            entree.canal}
                                    </td>
                                    <td>
                                        <span
                                            className={`chip ${STATUT[entree.statut]?.chip ?? 'chip-gray'}`}
                                        >
                                            {STATUT[entree.statut]?.label ??
                                                entree.statut}
                                        </span>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            )}

            {notifications.dernierePage > 1 && (
                <div
                    style={{
                        display: 'flex',
                        justifyContent: 'space-between',
                        alignItems: 'center',
                        marginTop: 16,
                        paddingTop: 16,
                        borderTop: '1px solid var(--dag-border)',
                    }}
                >
                    <button
                        type="button"
                        className="btn-outline"
                        style={{ padding: '7px 16px', fontSize: 13 }}
                        disabled={notifications.precedente === null}
                        onClick={() =>
                            notifications.precedente &&
                            router.visit(notifications.precedente, {
                                preserveScroll: true,
                            })
                        }
                    >
                        Précédent
                    </button>
                    <span className="muted" style={{ fontSize: 12.5 }}>
                        Page {notifications.page} sur{' '}
                        {notifications.dernierePage}
                    </span>
                    <button
                        type="button"
                        className="btn-outline"
                        style={{ padding: '7px 16px', fontSize: 13 }}
                        disabled={notifications.suivante === null}
                        onClick={() =>
                            notifications.suivante &&
                            router.visit(notifications.suivante, {
                                preserveScroll: true,
                            })
                        }
                    >
                        Suivant
                    </button>
                </div>
            )}
        </div>
    );
}
