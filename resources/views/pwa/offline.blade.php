{{--
    Deliberately a plain Blade page with inline styles: it is the fallback shown
    when the network is gone, so it must not depend on Inertia, on props or on any
    asset that might not be in the cache yet.
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#ffffff">
        <title>{{ __('pwa.offline.title') }} · {{ config('app.name') }}</title>
        <link rel="icon" href="/favicon.ico" media="(prefers-color-scheme: light)">
        <link rel="icon" href="/favicon-white.ico" media="(prefers-color-scheme: dark)">
        <style>
            :root { color-scheme: light dark; }
            * { box-sizing: border-box; }
            body {
                margin: 0;
                min-height: 100dvh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 1.5rem;
                background: #fafafa;
                color: #18181b;
                font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", sans-serif;
                text-align: center;
            }
            main { max-width: 26rem; }
            img { width: 64px; height: 64px; margin-bottom: 1.5rem; }
            h1 { margin: 0 0 .5rem; font-size: 1.25rem; font-weight: 600; }
            p { margin: 0 0 1.5rem; font-size: .875rem; line-height: 1.6; color: #52525b; }
            button {
                appearance: none;
                border: 0;
                border-radius: .5rem;
                padding: .625rem 1.25rem;
                font: inherit;
                font-weight: 500;
                color: #fafafa;
                background: #18181b;
                cursor: pointer;
            }
            button:hover { background: #27272a; }
            @media (prefers-color-scheme: dark) {
                body { background: #09090b; color: #fafafa; }
                p { color: #a1a1aa; }
                button { background: #fafafa; color: #18181b; }
                button:hover { background: #e4e4e7; }
            }
        </style>
    </head>
    <body>
        <main>
            <picture>
                <source srcset="/favicon-white.ico" media="(prefers-color-scheme: dark)">
                <img src="/favicon.ico" alt="">
            </picture>
            <h1>{{ __('pwa.offline.title') }}</h1>
            <p>{{ __('pwa.offline.description') }}</p>
            <button type="button" onclick="window.location.reload()">
                {{ __('pwa.offline.retry') }}
            </button>
        </main>
    </body>
</html>
