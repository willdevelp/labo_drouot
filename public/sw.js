/**
 * Service Worker — Laboratoire Drouot
 *
 * Stratégie :
 *  - Pages HTML (navigation) : "network-first" -> toujours la version la
 *    plus fraîche si le réseau répond, sinon la dernière version visitée
 *    en cache, sinon la page offline.html.
 *  - Fichiers statiques (CSS/JS/fonts/images, y compris CDN) :
 *    "stale-while-revalidate" -> affichage instantané depuis le cache
 *    tout en rafraîchissant en arrière-plan.
 *  - Aucune requête non-GET (POST/PUT/DELETE) n'est interceptée : les
 *    formulaires et actions d'écriture continuent de nécessiter le réseau
 *    tant que la synchro hors ligne (phase 2) n'est pas en place.
 *  - /logout vide le cache des pages pour éviter qu'un écran déjà chargé
 *    (données patients, résultats...) reste consultable hors ligne après
 *    déconnexion sur un poste partagé.
 */

const CACHE_VERSION = 'v1';
const SHELL_CACHE   = `labo-drouot-shell-${CACHE_VERSION}`;
const PAGES_CACHE   = `labo-drouot-pages-${CACHE_VERSION}`;
const RUNTIME_CACHE = `labo-drouot-runtime-${CACHE_VERSION}`;
const CURRENT_CACHES = [SHELL_CACHE, PAGES_CACHE, RUNTIME_CACHE];

const PRECACHE_URLS = [
    '/offline.html',
    '/manifest.json',
    '/icons/icon-192.png',
    '/icons/icon-512.png',
    'https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&display=swap',
    'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css',
    'https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css',
    'https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js',
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        (async () => {
            const cache = await caches.open(SHELL_CACHE);
            // On tente chaque ressource individuellement : si une CDN est
            // injoignable au moment de l'install, ça ne doit pas bloquer
            // le reste du cache.
            await Promise.allSettled(
                PRECACHE_URLS.map(async (url) => {
                    try {
                        const response = await fetch(url, { mode: 'cors' });
                        if (response && (response.ok || response.type === 'opaque')) {
                            await cache.put(url, response);
                        }
                    } catch (err) {
                        // ressource indisponible au moment de l'install, tant pis
                    }
                })
            );
            self.skipWaiting();
        })()
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        (async () => {
            const names = await caches.keys();
            await Promise.all(
                names
                    .filter((name) => name.startsWith('labo-drouot-') && !CURRENT_CACHES.includes(name))
                    .map((name) => caches.delete(name))
            );
            await self.clients.claim();
        })()
    );
});

async function clearSensitivePageCache() {
    await caches.delete(PAGES_CACHE);
}

self.addEventListener('fetch', (event) => {
    const { request } = event;

    // On ne touche qu'aux requêtes GET. Les formulaires (POST) et actions
    // d'écriture doivent continuer de passer directement par le réseau.
    if (request.method !== 'GET') {
        return;
    }

    const url = new URL(request.url);

    // Déconnexion : laisser partir vers le réseau, puis purger le cache
    // des pages pour ne rien laisser de consultable hors ligne ensuite.
    if (url.pathname.endsWith('/logout')) {
        event.respondWith(
            fetch(request).finally(() => clearSensitivePageCache())
        );
        return;
    }

    const isNavigation =
        request.mode === 'navigate' ||
        (request.headers.get('accept') || '').includes('text/html');

    if (isNavigation) {
        event.respondWith(
            (async () => {
                try {
                    const networkResponse = await fetch(request);
                    const cache = await caches.open(PAGES_CACHE);
                    cache.put(request, networkResponse.clone());
                    return networkResponse;
                } catch (err) {
                    const cached = await caches.match(request, { ignoreSearch: false });
                    if (cached) return cached;
                    const offline = await caches.match('/offline.html');
                    return offline || Response.error();
                }
            })()
        );
        return;
    }

    // Fichiers statiques (CSS, JS, fonts, images) — même origine ou CDN.
    event.respondWith(
        (async () => {
            const cached = await caches.match(request);
            const networkFetch = fetch(request)
                .then(async (networkResponse) => {
                    if (networkResponse && (networkResponse.ok || networkResponse.type === 'opaque')) {
                        const cache = await caches.open(RUNTIME_CACHE);
                        cache.put(request, networkResponse.clone());
                    }
                    return networkResponse;
                })
                .catch(() => cached);
            return cached || networkFetch;
        })()
    );
});

self.addEventListener('message', (event) => {
    if (event.data && event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
});

/**
 * Background Sync — rejoue la file d'attente hors ligne (voir
 * public/assets/js/offline-outbox.js) dès que le navigateur détecte le
 * retour du réseau, même si aucun onglet de l'app n'est ouvert.
 * Best-effort : les navigateurs qui ne supportent pas Background Sync
 * (ex. Safari) retombent sur la synchro déclenchée côté page (événement
 * 'online' + polling), déjà en place dans offline-outbox.js.
 */
const OUTBOX_DB_NAME = 'labo-drouot-outbox';
const OUTBOX_STORE   = 'requests';

function openOutboxDB() {
    return new Promise((resolve, reject) => {
        const req = indexedDB.open(OUTBOX_DB_NAME, 1);
        req.onsuccess = () => resolve(req.result);
        req.onerror   = () => reject(req.error);
    });
}

async function getAllOutboxItems(db) {
    return new Promise((resolve, reject) => {
        const tx  = db.transaction(OUTBOX_STORE, 'readonly');
        const req = tx.objectStore(OUTBOX_STORE).getAll();
        req.onsuccess = () => resolve(req.result);
        req.onerror   = () => reject(req.error);
    });
}

async function deleteOutboxItem(db, localId) {
    return new Promise((resolve, reject) => {
        const tx = db.transaction(OUTBOX_STORE, 'readwrite');
        tx.objectStore(OUTBOX_STORE).delete(localId);
        tx.oncomplete = () => resolve();
        tx.onerror    = () => reject(tx.error);
    });
}

async function syncOutboxFromSW() {
    const db    = await openOutboxDB();
    const items = (await getAllOutboxItems(db))
        .filter((i) => !i.manualOnly)
        .sort((a, b) => a.createdAt - b.createdAt);

    for (const item of items) {
        const fd = new FormData();
        item.fields.forEach(([k, v]) => fd.append(k, v));
        try {
            await fetch(item.url, { method: 'POST', body: fd });
            await deleteOutboxItem(db, item.localId);
        } catch (err) {
            // Toujours hors ligne : on arrête, une nouvelle tentative de
            // sync sera programmée automatiquement par le navigateur.
            throw err;
        }
    }
}

self.addEventListener('sync', (event) => {
    if (event.tag === 'sync-outbox') {
        event.waitUntil(syncOutboxFromSW());
    }
});
