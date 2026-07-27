<?php

namespace App\Http\Controllers\Pwa;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * Serves the web app manifest.
 *
 * Rendered rather than served as a static file for two reasons: static hosting
 * hands it out as application/octet-stream (browsers warn, Lighthouse fails it),
 * and the app name here stays in step with config('app.name').
 */
class ManifestController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()
            ->json([
                'name' => config('app.name'),
                'short_name' => config('app.name'),
                'description' => __('pwa.manifest.description'),
                'start_url' => '/',
                'scope' => '/',
                'display' => 'standalone',
                'orientation' => 'portrait',
                'background_color' => '#ffffff',
                'theme_color' => '#ffffff',
                'lang' => str_replace('_', '-', app()->getLocale()),
                'dir' => 'ltr',
                'icons' => [
                    [
                        'src' => '/img/icon-192.png',
                        'sizes' => '192x192',
                        'type' => 'image/png',
                        'purpose' => 'any',
                    ],
                    [
                        'src' => '/img/icon-512.png',
                        'sizes' => '512x512',
                        'type' => 'image/png',
                        'purpose' => 'any',
                    ],
                    // Without a maskable icon the launcher crops the artwork to
                    // its own shape and clips the logo.
                    [
                        'src' => '/img/icon-maskable-512.png',
                        'sizes' => '512x512',
                        'type' => 'image/png',
                        'purpose' => 'maskable',
                    ],
                ],
            ], options: JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
            ->header('Content-Type', 'application/manifest+json');
    }
}
