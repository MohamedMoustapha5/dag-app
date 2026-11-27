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

/**
 * iPhone / iPad, whatever the browser (Safari, Chrome, Edge…): no install
 * prompt there, the user adds the site from the Share button.
 */
export function estAppareilApple(): boolean {
    const agent = navigator.userAgent;

    return (
        /iPad|iPhone|iPod/.test(agent) ||
        (agent.includes('Macintosh') && navigator.maxTouchPoints > 1)
    );
}

/** Android phone or tablet. */
export function estAndroid(): boolean {
    return /Android/i.test(navigator.userAgent);
}
