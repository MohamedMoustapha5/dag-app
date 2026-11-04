import { router, usePage, usePoll } from '@inertiajs/react';
import { Bell, CheckCheck } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';

export type NotificationEspace = {
    id: string;
    titre: string;
    message: string;
    url: string | null;
    lue: boolean;
    date: string | null;
};

export type ClocheDonnees = {
    nonLues: number;
    recentes: NotificationEspace[];
};

type Props = {
    /** URL that marks one notification as read, then opens its page. */
    urlLire: (id: string) => string;
    urlToutesLues: string;
};

/** How often the bell checks for new notifications without a page change. */
const RAFRAICHISSEMENT_MS = 60_000;

function depuis(dateIso: string | null): string {
    if (!dateIso) {
        return '';
    }

    const secondes = Math.max(
        0,
        Math.round((Date.now() - new Date(dateIso).getTime()) / 1000),
    );

    if (secondes < 60) {
        return "À l'instant";
    }
    if (secondes < 3600) {
        return `Il y a ${Math.floor(secondes / 60)} min`;
    }
    if (secondes < 86400) {
        return `Il y a ${Math.floor(secondes / 3600)} h`;
    }
    if (secondes < 7 * 86400) {
        const jours = Math.floor(secondes / 86400);

        return `Il y a ${jours} jour${jours > 1 ? 's' : ''}`;
    }

    return new Intl.DateTimeFormat('fr-FR', {
        day: 'numeric',
        month: 'short',
    }).format(new Date(dateIso));
}

/**
 * Notification bell shared by the client, apprenant and admin espaces: unread
 * badge, list of the latest notifications, mark as read on click, and a
 * silent refresh so a new notification appears without reloading.
 */
export default function ClocheNotifications({ urlLire, urlToutesLues }: Props) {
    const { notifications } = usePage<{
        notifications: ClocheDonnees | null;
    }>().props;
    const [ouvert, setOuvert] = useState(false);
    const conteneur = useRef<HTMLDivElement>(null);

    usePoll(RAFRAICHISSEMENT_MS, { only: ['notifications'] });

    useEffect(() => {
        if (!ouvert) {
            return;
        }

        const fermerAuClicExterieur = (event: MouseEvent) => {
            if (!conteneur.current?.contains(event.target as Node)) {
                setOuvert(false);
            }
        };
        const fermerAEchap = (event: KeyboardEvent) => {
            if (event.key === 'Escape') {
                setOuvert(false);
            }
        };

        document.addEventListener('mousedown', fermerAuClicExterieur);
        document.addEventListener('keydown', fermerAEchap);

        return () => {
            document.removeEventListener('mousedown', fermerAuClicExterieur);
            document.removeEventListener('keydown', fermerAEchap);
        };
    }, [ouvert]);

    if (!notifications) {
        return null;
    }

    const ouvrir = (notification: NotificationEspace) => {
        setOuvert(false);
        router.post(urlLire(notification.id), {}, { preserveScroll: true });
    };

    return (
        <div ref={conteneur} style={{ position: 'relative' }}>
            <button
                type="button"
                className="espace-notif"
                aria-label={
                    notifications.nonLues > 0
                        ? `Notifications : ${notifications.nonLues} non lue${notifications.nonLues > 1 ? 's' : ''}`
                        : 'Notifications'
                }
                aria-expanded={ouvert}
                onClick={() => setOuvert((valeur) => !valeur)}
                style={{ border: 'none', cursor: 'pointer' }}
            >
                <Bell size={18} />
                {notifications.nonLues > 0 && (
                    <span
                        style={{
                            position: 'absolute',
                            top: -3,
                            right: -3,
                            minWidth: 18,
                            height: 18,
                            padding: '0 5px',
                            borderRadius: 999,
                            background: 'var(--dag-red)',
                            color: '#fff',
                            fontSize: 10.5,
                            fontWeight: 700,
                            display: 'flex',
                            alignItems: 'center',
                            justifyContent: 'center',
                            border: '2px solid #fff',
                        }}
                    >
                        {notifications.nonLues > 99
                            ? '99+'
                            : notifications.nonLues}
                    </span>
                )}
            </button>

            {ouvert && (
                <div
                    role="dialog"
                    aria-label="Notifications"
                    className="card"
                    style={{
                        position: 'absolute',
                        top: 46,
                        right: 0,
                        width: 'min(380px, calc(100vw - 32px))',
                        maxHeight: 460,
                        display: 'flex',
                        flexDirection: 'column',
                        zIndex: 60,
                        padding: 0,
                        overflow: 'hidden',
                        boxShadow: '0 12px 32px rgba(15, 23, 42, 0.18)',
                    }}
                >
                    <div
                        style={{
                            display: 'flex',
                            justifyContent: 'space-between',
                            alignItems: 'center',
                            padding: '14px 16px',
                            borderBottom: '1px solid var(--dag-border)',
                        }}
                    >
                        <strong style={{ fontSize: 14.5 }}>Notifications</strong>
                        {notifications.nonLues > 0 && (
                            <button
                                type="button"
                                onClick={() =>
                                    router.post(
                                        urlToutesLues,
                                        {},
                                        { preserveScroll: true },
                                    )
                                }
                                style={{
                                    display: 'inline-flex',
                                    alignItems: 'center',
                                    gap: 6,
                                    border: 'none',
                                    background: 'none',
                                    color: 'var(--dag-blue)',
                                    fontSize: 12.5,
                                    fontWeight: 600,
                                    cursor: 'pointer',
                                }}
                            >
                                <CheckCheck size={14} /> Tout marquer comme lu
                            </button>
                        )}
                    </div>

                    <div style={{ overflowY: 'auto' }}>
                        {notifications.recentes.length === 0 ? (
                            <p
                                className="muted"
                                style={{
                                    padding: 28,
                                    textAlign: 'center',
                                    fontSize: 13.5,
                                }}
                            >
                                Aucune notification pour le moment.
                            </p>
                        ) : (
                            notifications.recentes.map((notification) => (
                                <button
                                    key={notification.id}
                                    type="button"
                                    onClick={() => ouvrir(notification)}
                                    style={{
                                        display: 'flex',
                                        gap: 10,
                                        width: '100%',
                                        textAlign: 'left',
                                        padding: '12px 16px',
                                        border: 'none',
                                        borderBottom:
                                            '1px solid var(--dag-border)',
                                        background: notification.lue
                                            ? 'transparent'
                                            : 'var(--dag-blue-50, #eff6ff)',
                                        cursor: 'pointer',
                                    }}
                                >
                                    <span
                                        aria-hidden
                                        style={{
                                            flex: 'none',
                                            width: 8,
                                            height: 8,
                                            marginTop: 6,
                                            borderRadius: '50%',
                                            background: notification.lue
                                                ? 'transparent'
                                                : 'var(--dag-blue)',
                                        }}
                                    />
                                    <span style={{ minWidth: 0 }}>
                                        <span
                                            style={{
                                                display: 'block',
                                                fontSize: 13.5,
                                                fontWeight: notification.lue
                                                    ? 500
                                                    : 700,
                                                color: 'var(--dag-navy)',
                                            }}
                                        >
                                            {notification.titre}
                                        </span>
                                        <span
                                            className="muted"
                                            style={{
                                                display: 'block',
                                                fontSize: 12.5,
                                                marginTop: 2,
                                            }}
                                        >
                                            {notification.message}
                                        </span>
                                        <span
                                            className="muted"
                                            style={{
                                                display: 'block',
                                                fontSize: 11.5,
                                                marginTop: 4,
                                            }}
                                        >
                                            {depuis(notification.date)}
                                        </span>
                                    </span>
                                </button>
                            ))
                        )}
                    </div>
                </div>
            )}
        </div>
    );
}
