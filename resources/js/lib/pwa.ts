/**
 * Service worker registration.
 *
 * Kept out of app.ts so the entry stays about booting Inertia, and so the
 * cache-clearing helper has an obvious home for the logout flow to import.
 */

const SERVICE_WORKER_URL = '/sw.js';

function isSupported(): boolean {
    return !import.meta.env.SSR && typeof navigator !== 'undefined' && 'serviceWorker' in navigator;
}

export function registerServiceWorker(): void {
    // Dev serves modules through Vite; a worker caching those would fight HMR.
    if (!isSupported() || import.meta.env.DEV) {
        return;
    }

    window.addEventListener('load', () => {
        navigator.serviceWorker.register(SERVICE_WORKER_URL, { scope: '/' }).catch(() => {
            // A failed registration must never break the app: the site simply
            // runs without offline support.
        });
    });
}

/**
 * Drop every cache entry. Called on logout so cached screens — which contain
 * financial data — do not survive for the next person using the device.
 */
export async function clearServiceWorkerCaches(): Promise<void> {
    if (!isSupported()) {
        return;
    }

    try {
        navigator.serviceWorker.controller?.postMessage('clear-caches');

        if (typeof caches !== 'undefined') {
            const keys = await caches.keys();

            await Promise.all(keys.map((key) => caches.delete(key)));
        }
    } catch {
        // Best effort: never block the user from signing out.
    }
}
