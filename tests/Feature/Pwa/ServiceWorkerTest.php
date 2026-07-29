<?php

use App\Models\Tenant;
use App\Models\User;
use App\Support\Tenancy\TenantContext;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

test('the service worker is served from the root with a javascript content type', function () {
    $response = get('/sw.js')->assertSuccessful();

    // Root path plus this header is what gives the worker scope over the app.
    expect($response->headers->get('Content-Type'))->toContain('application/javascript');
    expect($response->headers->get('Service-Worker-Allowed'))->toBe('/');
});

test('the service worker is never cached by the browser', function () {
    // A stale worker would keep serving a stale shell forever.
    $cacheControl = get('/sw.js')->assertSuccessful()->headers->get('Cache-Control');

    expect($cacheControl)->toContain('no-cache');
    expect($cacheControl)->toContain('no-store');
});

test('the service worker precaches the build shell and the offline page', function () {
    $body = get('/sw.js')->assertSuccessful()->getContent();

    expect($body)->toContain('/offline');
    expect($body)->toContain('/build/assets/');
    // Fonts are self-hosted, so they belong in the shell.
    expect($body)->toContain('.woff2');
});

test('the shell excludes lazily loaded page chunks', function () {
    $body = (string) get('/sw.js')->assertSuccessful()->getContent();

    preg_match('/const SHELL = (\[.*?\]);/s', $body, $matches);
    $shell = json_decode($matches[1] ?? '[]', true);

    expect($shell)->not->toBeEmpty();

    // apexcharts is ~1.3 MB and only the dashboard needs it: precaching it would
    // quadruple the install size.
    foreach ($shell as $asset) {
        expect($asset)->not->toContain('apexcharts');
    }
});

test('the service worker and the offline page are reachable while signed out', function () {
    get('/sw.js')->assertSuccessful();
    get('/offline')->assertSuccessful();
});

test('the offline page renders without inertia', function () {
    $body = get('/offline')->assertSuccessful()->getContent();

    // It is the fallback for "no network": it must not need props or the SPA
    // bundle to render.
    expect($body)->not->toContain('data-page');
    expect($body)->toContain('Sem conexão');
});

test('every document offers the mark in a light and a dark variant', function (string $url) {
    // A single-tone mark disappears against a tab bar of the same tone, so both
    // the SPA shell and the offline fallback must ship the pair.
    $body = (string) get($url)->assertSuccessful()->getContent();

    expect($body)->toContain('/favicon.ico');
    expect($body)->toContain('/favicon-white.ico');
    expect($body)->toContain('(prefers-color-scheme: dark)');
})->with(['/login', '/offline']);

test('the offline page follows the authenticated user locale', function () {
    $tenant = Tenant::create(['name' => 'Tenant PWA']);
    app(TenantContext::class)->setTenantId($tenant->id);

    $user = User::factory()->create([
        'tenant_id' => $tenant->id,
        'email' => 'pwa-locale@example.com',
        'locale' => 'es',
    ]);

    expect(actingAs($user)->get('/offline')->assertSuccessful()->getContent())
        ->toContain('Sin conexión');
});

test('the manifest is served with the spec content type', function () {
    // Static hosting hands .webmanifest out as application/octet-stream, which
    // browsers warn about; serving it from a route pins the correct type.
    expect(get('/manifest.webmanifest')->assertSuccessful()->headers->get('Content-Type'))
        ->toContain('application/manifest+json');
});

test('the manifest declares an installable icon set', function () {
    $manifest = get('/manifest.webmanifest')->assertSuccessful()->json();

    expect($manifest['display'])->toBe('standalone');
    expect($manifest['start_url'])->toBe('/');

    $sizes = array_column($manifest['icons'], 'sizes');
    $purposes = array_column($manifest['icons'], 'purpose');

    // Android needs 192 and 512; without a maskable one the launcher crops the art.
    expect($sizes)->toContain('192x192');
    expect($sizes)->toContain('512x512');
    expect($purposes)->toContain('maskable');

    foreach ($manifest['icons'] as $icon) {
        expect(public_path(ltrim($icon['src'], '/')))->toBeFile();
    }
});
