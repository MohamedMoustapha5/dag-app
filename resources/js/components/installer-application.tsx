import { EllipsisVertical, Plus, Share, SquarePlus, X } from 'lucide-react';
import type { ReactNode } from 'react';
import { useEffect, useState } from 'react';
import { estAndroid, estAppareilApple, estInstallee } from '@/lib/pwa';

/** Chrome / Edge / Android event offering to install the site. */
type EvenementInstallation = Event & {
    prompt: () => Promise<void>;
    userChoice: Promise<{ outcome: 'accepted' | 'dismissed' }>;
};

/** Where the steps are shown: no install dialog on that device. */
type Guide = 'apple' | 'android';

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

/** The button the user has to find, drawn as it looks on their screen. */
function Repere({ children }: { children: ReactNode }) {
    return (
        <span
            aria-hidden="true"
            style={{
                display: 'inline-grid',
                placeItems: 'center',
                width: 26,
                height: 26,
                borderRadius: 7,
                background: '#fff',
                border: '1px solid var(--dag-border)',
                color: 'var(--dag-blue)',
                verticalAlign: 'middle',
                margin: '0 3px',
            }}
        >
            {children}
        </span>
    );
}

function Etapes({ guide }: { guide: Guide }) {
    const etapes: ReactNode[] =
        guide === 'apple'
            ? [
                  <>
                      Touchez le bouton{' '}
                      <Repere>
                          <Share size={14} />
                      </Repere>{' '}
                      <strong>Partager</strong>, en bas de l'écran dans Safari
                      ou dans la barre d'adresse dans Chrome.
                  </>,
                  <>
                      Faites défiler puis touchez{' '}
                      <Repere>
                          <SquarePlus size={14} />
                      </Repere>{' '}
                      <strong>Sur l'écran d'accueil</strong>.
                  </>,
                  <>
                      Touchez <strong>Ajouter</strong>, en haut à droite :
                      l'icône DAG apparaît sur votre écran d'accueil.
                  </>,
              ]
            : [
                  <>
                      Touchez le menu{' '}
                      <Repere>
                          <EllipsisVertical size={14} />
                      </Repere>{' '}
                      du navigateur, en haut à droite.
                  </>,
                  <>
                      Touchez{' '}
                      <Repere>
                          <Plus size={14} />
                      </Repere>{' '}
                      <strong>Installer l'application</strong> ou{' '}
                      <strong>Ajouter à l'écran d'accueil</strong>.
                  </>,
                  <>
                      Confirmez avec <strong>Installer</strong> : l'icône DAG
                      apparaît sur votre écran d'accueil.
                  </>,
              ];

    return (
        <ol className="installer-application-etapes">
            {etapes.map((etape, index) => (
                <li key={index}>
                    <span
                        className="installer-application-numero"
                        aria-hidden="true"
                    >
                        {index + 1}
                    </span>
                    <span>{etape}</span>
                </li>
            ))}
        </ol>
    );
}

/**
 * Discreet card inviting to install DAG on the home screen, with the same
 * two buttons everywhere. "Installer" opens the browser's own install dialog
 * where it exists (Chrome, Edge, most Android phones); elsewhere (iPhone,
 * some Android browsers) it shows the steps to follow. Never shown inside
 * the installed application, and not again for 30 days once dismissed.
 */
export default function InstallerApplication() {
    const [evenement, setEvenement] = useState<EvenementInstallation | null>(
        null,
    );
    const [guide, setGuide] = useState<Guide | null>(null);
    const [etapesOuvertes, setEtapesOuvertes] = useState(false);
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

        if (estAppareilApple()) {
            setGuide('apple');
            afficherPlusTard();
        } else if (estAndroid()) {
            // Most Android browsers offer the install dialog: the steps are
            // only the fallback for those that never do (shown after the delay).
            setGuide('android');
            afficherPlusTard();
        }

        return () => {
            window.clearTimeout(minuterie);
            window.removeEventListener('beforeinstallprompt', surProposition);
            window.removeEventListener('appinstalled', surInstallation);
        };
    }, []);

    if (!visible || (!evenement && !guide)) {
        return null;
    }

    const fermer = () => {
        setVisible(false);
        memoriserEcartee();
    };

    const installer = async () => {
        if (!evenement) {
            setEtapesOuvertes(true);

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
                <div style={{ minWidth: 0, paddingRight: 18 }}>
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
                        {etapesOuvertes
                            ? 'En 3 étapes, depuis votre navigateur :'
                            : "Votre espace en un geste, depuis l'écran d'accueil."}
                    </p>
                </div>
            </div>

            {etapesOuvertes && guide ? (
                <>
                    <Etapes guide={guide} />
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
            ) : (
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
            )}
        </div>
    );
}
