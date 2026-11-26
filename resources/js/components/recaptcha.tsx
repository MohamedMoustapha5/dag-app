import { router, usePage } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import { langueActive } from '@/lib/i18n/langue';

type Grecaptcha = {
    render: (
        conteneur: HTMLElement,
        options: {
            sitekey: string;
            theme: 'light' | 'dark';
            size: 'normal' | 'compact';
            callback: (jeton: string) => void;
            'expired-callback': () => void;
            'error-callback': () => void;
        },
    ) => number;
    reset: (widget: number) => void;
};

declare global {
    interface Window {
        grecaptcha?: Grecaptcha;
        dagRecaptchaPret?: () => void;
    }
}

/** Width of the standard widget; below it, the compact one is used. */
const LARGEUR_WIDGET = 304;

let chargement: Promise<Grecaptcha> | null = null;

/** Loads Google's script once for the whole page, whatever the number of widgets. */
function chargerRecaptcha(): Promise<Grecaptcha> {
    if (window.grecaptcha?.render) {
        return Promise.resolve(window.grecaptcha);
    }

    chargement ??= new Promise<Grecaptcha>((resolve, reject) => {
        window.dagRecaptchaPret = () =>
            resolve(window.grecaptcha as Grecaptcha);

        const script = document.createElement('script');
        script.src = `https://www.google.com/recaptcha/api.js?render=explicit&onload=dagRecaptchaPret&hl=${langueActive()}`;
        script.async = true;
        script.defer = true;
        script.onerror = () => {
            chargement = null;
            script.remove();
            reject(new Error('reCAPTCHA indisponible'));
        };
        document.head.appendChild(script);
    });

    return chargement;
}

type Props = {
    /** Called with the token once the box is ticked, '' when it expires or is reset (useForm). */
    onChange?: (jeton: string) => void;
    error?: string;
};

/**
 * Google reCAPTCHA v2 box ("Je ne suis pas un robot") of a public form. The
 * token travels in a hidden "recaptcha" field (picked up by <Form>) and
 * through onChange (for useForm); the server checks it (VerifierRecaptcha).
 * A token is single-use, so the box is reset after every submission. Renders
 * nothing while reCAPTCHA is off (no keys configured).
 */
export default function Recaptcha({ onChange, error }: Props) {
    const cleDuSite = usePage().props.recaptchaSiteKey;
    const conteneur = useRef<HTMLDivElement>(null);
    const widget = useRef<number | null>(null);
    const rappel = useRef(onChange);
    const [jeton, setJeton] = useState('');
    const [etat, setEtat] = useState<'chargement' | 'pret' | 'erreur'>(
        'chargement',
    );

    useEffect(() => {
        rappel.current = onChange;
    }, [onChange]);

    useEffect(() => {
        if (!cleDuSite || !conteneur.current) {
            return;
        }

        const hote = conteneur.current;
        // A fresh node each time: Google refuses to render twice in one element.
        const cible = document.createElement('div');
        hote.appendChild(cible);
        let actif = true;

        const changer = (valeur: string) => {
            if (actif) {
                setJeton(valeur);
                rappel.current?.(valeur);
            }
        };

        chargerRecaptcha()
            .then((grecaptcha) => {
                if (!actif) {
                    return;
                }

                widget.current = grecaptcha.render(cible, {
                    sitekey: cleDuSite,
                    theme: document.documentElement.classList.contains('dark')
                        ? 'dark'
                        : 'light',
                    size:
                        hote.clientWidth > 0 &&
                        hote.clientWidth < LARGEUR_WIDGET
                            ? 'compact'
                            : 'normal',
                    callback: changer,
                    'expired-callback': () => changer(''),
                    'error-callback': () => changer(''),
                });
                setEtat('pret');
            })
            .catch(() => {
                if (actif) {
                    setEtat('erreur');
                }
            });

        // The token was spent by the submission, whatever its outcome.
        const arreterEcoute = router.on('finish', (event) => {
            if (
                event.detail.visit.method === 'get' ||
                widget.current === null
            ) {
                return;
            }

            window.grecaptcha?.reset(widget.current);
            changer('');
        });

        return () => {
            actif = false;
            arreterEcoute();
            widget.current = null;
            cible.remove();
        };
    }, [cleDuSite]);

    if (!cleDuSite) {
        return null;
    }

    return (
        <div>
            <input type="hidden" name="recaptcha" value={jeton} />
            <div
                ref={conteneur}
                aria-busy={etat === 'chargement'}
                style={{ minHeight: 78, maxWidth: '100%' }}
            >
                {etat === 'chargement' && (
                    <p
                        style={{
                            fontSize: 12.5,
                            color: 'var(--dag-text-muted)',
                            paddingTop: 28,
                        }}
                    >
                        Chargement de la vérification anti-robot…
                    </p>
                )}
            </div>
            {etat === 'erreur' && (
                <p
                    role="alert"
                    style={{
                        color: 'var(--dag-red)',
                        fontSize: 12,
                        marginTop: 4,
                    }}
                >
                    La vérification anti-robot n'a pas pu se charger. Vérifiez
                    votre connexion ou désactivez votre bloqueur de publicités,
                    puis rechargez la page.
                </p>
            )}
            {error && (
                <p
                    role="alert"
                    style={{
                        color: 'var(--dag-red)',
                        fontSize: 12,
                        marginTop: 4,
                    }}
                >
                    {error}
                </p>
            )}
        </div>
    );
}
