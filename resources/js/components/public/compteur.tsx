import { useLayoutEffect, useRef } from 'react';
import type { CSSProperties } from 'react';

type Props = {
    valeur: number;
    suffixe?: string;
    style?: CSSProperties;
};

const DUREE_MS = 900;

/**
 * A key figure that counts up from 0 the first time it scrolls into view.
 * The final value is what gets rendered, so it stays readable without
 * JavaScript and with reduced motion; the count writes straight to the DOM
 * (no re-render per frame).
 */
export default function Compteur({ valeur, suffixe = '', style }: Props) {
    const element = useRef<HTMLElement>(null);

    useLayoutEffect(() => {
        const noeud = element.current;

        if (
            noeud === null ||
            valeur <= 0 ||
            typeof IntersectionObserver === 'undefined' ||
            window.matchMedia('(prefers-reduced-motion: reduce)').matches
        ) {
            return;
        }

        let image = 0;
        noeud.textContent = `0${suffixe}`;

        const observateur = new IntersectionObserver(
            ([entree]) => {
                if (!entree?.isIntersecting) {
                    return;
                }

                observateur.disconnect();
                const debut = performance.now();

                const avancer = (maintenant: number) => {
                    const progression = Math.min(
                        (maintenant - debut) / DUREE_MS,
                        1,
                    );
                    const adouci = 1 - Math.pow(1 - progression, 3);
                    noeud.textContent = `${Math.round(valeur * adouci)}${suffixe}`;

                    if (progression < 1) {
                        image = requestAnimationFrame(avancer);
                    }
                };

                image = requestAnimationFrame(avancer);
            },
            { threshold: 0.6 },
        );

        observateur.observe(noeud);

        return () => {
            observateur.disconnect();
            cancelAnimationFrame(image);
            noeud.textContent = `${valeur}${suffixe}`;
        };
    }, [valeur, suffixe]);

    return (
        <strong
            ref={element}
            style={{ fontVariantNumeric: 'tabular-nums', ...style }}
        >
            {valeur}
            {suffixe}
        </strong>
    );
}
