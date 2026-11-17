/**
 * Installable application (PWA). The service worker (public/sw.js) only runs
 * on a secure origin: HTTPS, or localhost while developing.
 */
export function enregistrerServiceWorker(): void {
    if (
        typeof window === 'undefined' ||
        !('serviceWorker' in navigator) ||
        !window.isSecureContext
    ) {
        return;
    }

    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js').catch(() => {
            // Not installable this time; the site works the same.
        });
    });
}

/** Whether the page runs as the installed application. */
export function estInstallee(): boolean {
    return (
        window.matchMedia('(display-mode: standalone)').matches ||
        (navigator as Navigator & { standalone?: boolean }).standalone === true
    );
}

/** iPhone / iPad Safari: no install prompt, the user adds it from Share. */
export function estAppleSafari(): boolean {
    const agent = navigator.userAgent;
    const appareilApple =
        /iPad|iPhone|iPod/.test(agent) ||
        (agent.includes('Macintosh') && navigator.maxTouchPoints > 1);

    return (
        appareilApple &&
        /Safari/.test(agent) &&
        !/CriOS|FxiOS|EdgiOS/.test(agent)
    );
}
