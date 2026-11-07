import { useEffect } from 'react';

/** Long enough for the last card of a cascade to finish its entrance. */
const DUREE_CASCADE_MS = 900;

/**
 * Révèle progressivement les éléments `.reveal` / `.reveal-stagger` au scroll,
 * comme le faisait script.js dans la maquette statique (IntersectionObserver
 * + classe `is-visible`, cf. resources/css/dag.css).
 *
 * Les grilles `.anim-cascade` font entrer leurs cartes l'une après l'autre,
 * une seule fois : `is-pending` les masque tant que la grille n'est pas à
 * l'écran, `is-done` coupe l'animation ensuite, pour que les cartes
 * ajoutées par un filtre ou une recherche s'affichent sans attendre.
 */
export function useScrollReveal(deps: unknown[] = []): void {
    useEffect(() => {
        const sections = document.querySelectorAll(
            '.dag .reveal, .dag .reveal-stagger',
        );
        const cascades = document.querySelectorAll('.dag .anim-cascade');

        if (typeof IntersectionObserver === 'undefined') {
            sections.forEach((target) => target.classList.add('is-visible'));
            return;
        }

        const minuteries: number[] = [];

        const observer = new IntersectionObserver(
            (entries) => {
                for (const entry of entries) {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-visible');
                        observer.unobserve(entry.target);
                    }
                }
            },
            { threshold: 0.15, rootMargin: '0px 0px -40px 0px' },
        );

        const observateurCascades = new IntersectionObserver(
            (entries) => {
                for (const entry of entries) {
                    if (!entry.isIntersecting) {
                        continue;
                    }

                    const grille = entry.target;
                    observateurCascades.unobserve(grille);
                    grille.classList.remove('is-pending');
                    grille.classList.add('is-visible');
                    minuteries.push(
                        window.setTimeout(
                            () => grille.classList.add('is-done'),
                            DUREE_CASCADE_MS,
                        ),
                    );
                }
            },
            { rootMargin: '0px 0px -60px 0px' },
        );

        sections.forEach((target) => observer.observe(target));
        cascades.forEach((grille) => {
            grille.classList.add('is-pending');
            observateurCascades.observe(grille);
        });

        return () => {
            observer.disconnect();
            observateurCascades.disconnect();
            minuteries.forEach((minuterie) => window.clearTimeout(minuterie));
            cascades.forEach((grille) => grille.classList.remove('is-pending'));
        };
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, deps);
}
