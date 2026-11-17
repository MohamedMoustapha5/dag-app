import { Share, X } from 'lucide-react';
import { useEffect, useState } from 'react';
import { estAppleSafari, estInstallee } from '@/lib/pwa';

/** Chrome / Edge / Android event offering to install the site. */
type EvenementInstallation = Event & {
    prompt: () => Promise<void>;
    userChoice: Promise<{ outcome: 'accepted' | 'dismissed' }>;
};

const CLE_MASQUE = 'dag_pwa_masque_le';
const JOURS_AVANT_DE_REPROPOSER = 30;
const DELAI_AFFICHAGE_MS = 4000;

function recemmentEcartee(): boolean {
    try {
        const date = Number(window.localStorage.getItem(CLE_MASQUE));

        return (
            Number.isFinite(date) &&
            Date.now() - date < JOURS_AVANT_DE_REPROPOSER * 86_400_000
        );
    } catch {
        return false;
    }
}

function memoriserEcartee(): void {
    try {
        window.localStorage.setItem(CLE_MASQUE, String(Date.now()));
    } catch {
        // Private browsing: it may show again next visit.
    }
}

/**
 * Discreet card inviting to install DAG on the home screen: the browser's
 * own install dialog where it exists (Android, Chrome, Edge), the Share →
 * "Add to Home Screen" steps on iPhone. Never shown inside the installed
 * application, and not again for 30 days once dismissed.
 */
export default function InstallerApplication() {
    const [evenement, setEvenement] = useState<EvenementInstallation | null>(
        null,
    );
    const [iphone, setIphone] = useState(false);
    const [visible, setVisible] = useState(false);

    useEffect(() => {
        if (estInstallee() || recemmentEcartee()) {
            return;
        }

        let minuterie: number | undefined;
        const afficherPlusTard = () => {
            window.clearTimeout(minuterie);
            minuterie = window.setTimeout(
                () => setVisible(true),
                DELAI_AFFICHAGE_MS,
            );
        };

        const surProposition = (proposition: Event) => {
            proposition.preventDefault();
            setEvenement(proposition as EvenementInstallation);
            afficherPlusTard();
        };
        const surInstallation = () => {
            setVisible(false);
            memoriserEcartee();
        };

        window.addEventListener('beforeinstallprompt', surProposition);
        window.addEventListener('appinstalled', surInstallation);

        if (estAppleSafari()) {
            setIphone(true);
            afficherPlusTard();
        }

        return () => {
            window.clearTimeout(minuterie);
            window.removeEventListener('beforeinstallprompt', surProposition);
            window.removeEventListener('appinstalled', surInstallation);
        };
    }, []);

    if (!visible || (!evenement && !iphone)) {
        return null;
    }

    const fermer = () => {
        setVisible(false);
        memoriserEcartee();
    };

    const installer = async () => {
        if (!evenement) {
            return;
        }

        await evenement.prompt();
        const { outcome } = await evenement.userChoice;
        setEvenement(null);
        setVisible(false);

        if (outcome === 'dismissed') {
            memoriserEcartee();
        }
    };

    return (
        <div
            role="dialog"
            aria-label="Installer l'application DAG"
            className="installer-application"
        >
            <button
                type="button"
                onClick={fermer}
                aria-label="Fermer"
                className="installer-application-fermer"
            >
                <X size={16} />
            </button>

            <div style={{ display: 'flex', gap: 14, alignItems: 'center' }}>
                <img
                    src="/images/pwa/icon-arrondi-192.png"
                    alt=""
                    width={56}
                    height={56}
                    style={{
                        flex: 'none',
                        filter: 'drop-shadow(0 2px 6px rgba(7, 31, 65, 0.16))',
                    }}
                />
                <div style={{ minWidth: 0 }}>
                    <p
                        style={{
                            fontWeight: 700,
                            fontSize: 14.5,
                            color: 'var(--dag-navy)',
                            marginBottom: 2,
                        }}
                    >
                        Installer l'application DAG
                    </p>
                    <p
                        style={{
                            fontSize: 12.5,
                            lineHeight: 1.45,
                            color: 'var(--dag-text-muted)',
                        }}
                    >
                        Votre espace en un geste, depuis l'écran d'accueil.
                    </p>
                </div>
            </div>

            {evenement ? (
                <div className="installer-application-actions">
                    <button
                        type="button"
                        onClick={fermer}
                        className="installer-application-secondaire"
                    >
                        Plus tard
                    </button>
                    <button
                        type="button"
                        onClick={installer}
                        className="installer-application-principal"
                    >
                        Installer
                    </button>
                </div>
            ) : (
                <>
                    <p
                        style={{
                            marginTop: 14,
                            padding: '10px 12px',
                            borderRadius: 10,
                            background: 'var(--dag-blue-light)',
                            fontSize: 12.5,
                            lineHeight: 1.55,
                            color: 'var(--dag-text)',
                        }}
                    >
                        Touchez{' '}
                        <Share
                            size={14}
                            aria-label="Partager"
                            style={{
                                verticalAlign: '-2px',
                                color: 'var(--dag-blue)',
                            }}
                        />{' '}
                        en bas de Safari, puis « Sur l'écran d'accueil ».
                    </p>
                    <div className="installer-application-actions">
                        <button
                            type="button"
                            onClick={fermer}
                            className="installer-application-principal"
                        >
                            J'ai compris
                        </button>
                    </div>
                </>
            )}
        </div>
    );
}
