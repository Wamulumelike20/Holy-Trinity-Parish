const CACHE_NAME = 'htp-kabwe-v1';
const OFFLINE_URL = '/holy-trinity/offline.html';

// Assets to cache immediately on install
const PRECACHE_ASSETS = [
    '/holy-trinity/index.php',
    '/holy-trinity/assets/css/style.css',
    '/holy-trinity/assets/js/main.js',
    '/holy-trinity/manifest.json',
    OFFLINE_URL,
    '/holy-trinity/assets/icons/icon-192x192.png',
    '/holy-trinity/assets/icons/icon-512x512.png',
    'https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700&family=Inter:wght@300;400;500;600;700&display=swap',
    'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css'
];

// Install: cache core assets
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then((cache) => {
                console.log('[SW] Pre-caching core assets');
                return cache.addAll(PRECACHE_ASSETS);
            })
            .then(() => self.skipWaiting())
    );
});

// Activate: clean up old caches
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames
                    .filter((name) => name !== CACHE_NAME)
                    .map((name) => {
                        console.log('[SW] Deleting old cache:', name);
                        return caches.delete(name);
                    })
            );
        }).then(() => self.clients.claim())
    );
});

// Fetch: network-first for pages, cache-first for assets
self.addEventListener('fetch', (event) => {
    const { request } = event;
    const url = new URL(request.url);

    // Skip non-GET requests
    if (request.method !== 'GET') return;

    // Skip chrome-extension and other non-http requests
    if (!url.protocol.startsWith('http')) return;

    // For navigation requests (HTML pages): network-first
    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request)
                .then((response) => {
                    // Cache successful page responses
                    if (response.ok) {
                        const clone = response.clone();
                        caches.open(CACHE_NAME).then((cache) => {
                            cache.put(request, clone);
                        });
                    }
                    return response;
                })
                .catch(() => {
                    // Offline: try cache, then offline page
                    return caches.match(request)
                        .then((cached) => cached || caches.match(OFFLINE_URL));
                })
        );
        return;
    }

    // For CSS, JS, fonts, images: cache-first
    if (
        url.pathname.match(/\.(css|js|woff2?|ttf|eot|svg|png|jpg|jpeg|gif|ico|webp)$/) ||
        url.hostname === 'fonts.googleapis.com' ||
        url.hostname === 'fonts.gstatic.com' ||
        url.hostname === 'cdnjs.cloudflare.com'
    ) {
        event.respondWith(
            caches.match(request).then((cached) => {
                if (cached) return cached;
                return fetch(request).then((response) => {
                    if (response.ok) {
                        const clone = response.clone();
                        caches.open(CACHE_NAME).then((cache) => {
                            cache.put(request, clone);
                        });
                    }
                    return response;
                }).catch(() => {
                    // Return nothing for failed asset requests
                    return new Response('', { status: 408, statusText: 'Offline' });
                });
            })
        );
        return;
    }

    // For API/data requests: network-first with cache fallback
    event.respondWith(
        fetch(request)
            .then((response) => {
                if (response.ok) {
                    const clone = response.clone();
                    caches.open(CACHE_NAME).then((cache) => {
                        cache.put(request, clone);
                    });
                }
                return response;
            })
            .catch(() => caches.match(request))
    );
});

// Background sync for offline form submissions
self.addEventListener('sync', (event) => {
    if (event.tag === 'sync-forms') {
        event.waitUntil(syncOfflineForms());
    }
});

async function syncOfflineForms() {
    // Retrieve queued form data from IndexedDB and submit
    try {
        const db = await openDB();
        const tx = db.transaction('offline-forms', 'readonly');
        const store = tx.objectStore('offline-forms');
        const forms = await getAllFromStore(store);

        for (const form of forms) {
            try {
                await fetch(form.url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: form.data
                });
                // Remove from queue on success
                const deleteTx = db.transaction('offline-forms', 'readwrite');
                deleteTx.objectStore('offline-forms').delete(form.id);
            } catch (e) {
                console.log('[SW] Sync failed for form:', form.id);
            }
        }
    } catch (e) {
        console.log('[SW] Sync error:', e);
    }
}

function openDB() {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open('htp-offline', 1);
        request.onupgradeneeded = (e) => {
            e.target.result.createObjectStore('offline-forms', { keyPath: 'id', autoIncrement: true });
        };
        request.onsuccess = (e) => resolve(e.target.result);
        request.onerror = (e) => reject(e.target.error);
    });
}

function getAllFromStore(store) {
    return new Promise((resolve, reject) => {
        const request = store.getAll();
        request.onsuccess = () => resolve(request.result);
        request.onerror = () => reject(request.error);
    });
}

// Push notifications
self.addEventListener('push', (event) => {
    const data = event.data ? event.data.json() : {};
    const title = data.title || 'Holy Trinity Parish';
    const options = {
        body: data.body || 'You have a new notification',
        icon: '/holy-trinity/assets/icons/icon-192x192.png',
        badge: '/holy-trinity/assets/icons/icon-72x72.png',
        vibrate: [100, 50, 100],
        data: {
            url: data.url || '/holy-trinity/index.php'
        },
        actions: [
            { action: 'open', title: 'Open' },
            { action: 'dismiss', title: 'Dismiss' }
        ]
    };
    event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    if (event.action === 'dismiss') return;
    const url = event.notification.data?.url || '/holy-trinity/index.php';
    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true })
            .then((clientList) => {
                for (const client of clientList) {
                    if (client.url.includes('/holy-trinity/') && 'focus' in client) {
                        client.navigate(url);
                        return client.focus();
                    }
                }
                return clients.openWindow(url);
            })
    );
});
