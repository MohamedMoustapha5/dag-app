import { useEffect } from 'react';

/**
 * Révèle progressivement les éléments `.reveal` / `.reveal-stagger` au scroll,
 * comme le faisait script.js dans la maquette statique (IntersectionObserver
 * + classe `is-visible`, cf. resources/css/dag.css).
 */
export function useScrollReveal(deps: unknown[] = []): void {
    useEffect(() => {
        const targets = document.querySelectorAll(
            '.dag .reveal, .dag .reveal-stagger',
        );

        if (targets.length === 0) {
            return;
        }

        if (typeof IntersectionObserver === 'undefined') {
            targets.forEach((target) => target.classList.add('is-visible'));
            return;
        }

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

        targets.forEach((target) => observer.observe(target));

        return () => observer.disconnect();
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, deps);
}
