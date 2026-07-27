const VERSION = @json($version);
const CACHE = `money-shell-${VERSION}`;
const SHELL = @json($shell, JSON_UNESCAPED_SLASHES);
const OFFLINE_URL = '/offline';

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches
            .open(CACHE)
            .then((cache) => cache.addAll(SHELL))
            .then(() => self.skipWaiting()),
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches
            .keys()
            .then((keys) => Promise.all(keys.filter((key) => key !== CACHE).map((key) => caches.delete(key))))
            .then(() => self.clients.claim()),
    );
});

self.addEventListener('fetch', (event) => {
    const request = event.request;

    // Never touch writes: a queued POST is a whole different feature.
    if (request.method !== 'GET') {
        return;
    }

    const url = new URL(request.url);

    if (url.origin !== self.location.origin) {
        return;
    }

    // Navigations always hit the network first. The document is not a fixed app
    // shell here: Blade injects the current page's Vite chunk into <head>, so a
    // cached document would boot the wrong screen.
    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request).catch(async () => {
                const offline = await caches.match(OFFLINE_URL);

                return offline ?? Response.error();
            }),
        );

        return;
    }

    // Build assets carry a content hash in their name, so they are immutable:
    // serve from cache and fill it on the first miss.
    if (url.pathname.startsWith('/build/')) {
        event.respondWith(
            caches.match(request).then(
                (cached) =>
                    cached ??
                    fetch(request).then((response) => {
                        if (response.ok) {
                            const copy = response.clone();

                            caches.open(CACHE).then((cache) => cache.put(request, copy));
                        }

                        return response;
                    }),
            ),
        );
    }
});

// Logout wipes everything: financial data must not survive on a shared device.
self.addEventListener('message', (event) => {
    if (event.data !== 'clear-caches') {
        return;
    }

    event.waitUntil(caches.keys().then((keys) => Promise.all(keys.map((key) => caches.delete(key)))));
});
