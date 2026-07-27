<?php

namespace App\Http\Controllers\Pwa;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;

/**
 * Serves the service worker from the application root.
 *
 * It is rendered rather than shipped as a static file because the shell it
 * precaches is a list of content-hashed Vite assets: rendering lets the worker
 * pick up the current build automatically, and changes its own body on deploy,
 * which is exactly what makes the browser install the new version.
 */
class ServiceWorkerController extends Controller
{
    public function __invoke(): Response
    {
        $shell = $this->shellAssets();

        return response()
            ->view('pwa.service-worker', [
                'version' => substr(hash('xxh128', implode('|', $shell)), 0, 12),
                'shell' => $shell,
            ])
            ->header('Content-Type', 'application/javascript')
            // Served from "/" so the worker's scope covers the whole app.
            ->header('Service-Worker-Allowed', '/')
            // The worker must never be stale: the browser re-checks it on every load.
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate');
    }

    /**
     * The minimum needed to boot the SPA offline: the JS entry, its stylesheets
     * and the self-hosted fonts. Page chunks and charts stay out — they are
     * cached on demand the first time they are used.
     *
     * @return array<int, string>
     */
    private function shellAssets(): array
    {
        $manifestPath = public_path('build/manifest.json');

        if (! is_file($manifestPath)) {
            return ['/offline'];
        }

        /** @var array<string, array<string, mixed>> $manifest */
        $manifest = json_decode((string) file_get_contents($manifestPath), true) ?: [];

        $assets = [];

        foreach ($manifest as $source => $chunk) {
            $isEntry = ($chunk['isEntry'] ?? false) === true;
            $isFont = str_ends_with($source, '.woff2');

            if (! $isEntry && ! $isFont) {
                continue;
            }

            if (isset($chunk['file']) && is_string($chunk['file'])) {
                $assets[] = '/build/'.$chunk['file'];
            }

            foreach ($chunk['css'] ?? [] as $stylesheet) {
                $assets[] = '/build/'.$stylesheet;
            }
        }

        $assets = array_values(array_unique($assets));
        sort($assets);

        return [...$assets, '/offline'];
    }
}
