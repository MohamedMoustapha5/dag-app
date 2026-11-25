/*
 * Service worker of the DAG application (installable PWA).
 *
 * What it keeps offline is deliberately limited to public static files:
 * the built scripts and styles (their names change at every build) and the
 * images. Pages and account data are never stored, so nothing personal stays
 * on the device and a page is never shown out of date. Without network, a
 * page request shows /offline.html instead of the browser's error.
 */

const VERSION = 'dag-v2';
const PAGE_HORS_LIGNE = '/offline.html';
const FICHIERS_INITIAUX = [
    PAGE_HORS_LIGNE,
    '/images/pwa/icon-arrondi-192.png',
    '/images/pwa/icon-arrondi-512.png',
    '/images/brand/dag-logo-pdf.png',
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches
            .open(VERSION)
            .then((cache) => cache.addAll(FICHIERS_INITIAUX))
            .then(() => self.skipWaiting()),
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches
            .keys()
            .then((noms) =>
                Promise.all(
                    noms
                        .filter((nom) => nom !== VERSION)
                        .map((nom) => caches.delete(nom)),
                ),
            )
            .then(() => self.clients.claim()),
    );
});

function estStatique(url) {
    return (
        url.pathname.startsWith('/build/') ||
        url.pathname.startsWith('/images/') ||
        url.pathname.startsWith('/fonts/') ||
        url.pathname === '/favicon.ico'
    );
}

self.addEventListener('fetch', (event) => {
    const { request } = event;
    const url = new URL(request.url);

    // Only this site's reads: forms, payments and other servers go straight
    // to the network.
    if (request.method !== 'GET' || url.origin !== self.location.origin) {
        return;
    }

    // A page: always the live version; the offline page when unreachable.
    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request).catch(() => caches.match(PAGE_HORS_LIGNE)),
        );

        return;
    }

    // Built files and images: from the device first, refreshed in the
    // background.
    if (estStatique(url)) {
        event.respondWith(
            caches.open(VERSION).then(async (cache) => {
                const enCache = await cache.match(request);
                const reseau = fetch(request)
                    .then((reponse) => {
                        if (reponse.ok) {
                            cache.put(request, reponse.clone());
                        }

                        return reponse;
                    })
                    .catch(() => enCache);

                return enCache ?? reseau;
            }),
        );
    }
});
