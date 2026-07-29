<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"  @class(['dark' => ($appearance ?? 'light') == 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        {{--
            "system" is the one choice the server cannot resolve: only the browser
            knows what the device is set to. Inline and first in the head so it
            lands before the first paint — moved any later, the page would flash
            the wrong theme on its way to the right one.
        --}}
        <script>
            if (@json($appearance ?? 'light') === 'system'
                && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                document.documentElement.classList.add('dark');
            }
        </script>

        {{-- The mark flips with the system theme, so it stays legible in either tab bar. --}}
        <link rel="icon" href="/favicon.ico" media="(prefers-color-scheme: light)">
        <link rel="icon" href="/favicon-white.ico" media="(prefers-color-scheme: dark)">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        <link rel="manifest" href="/manifest.webmanifest">
        <meta name="theme-color" content="#ffffff" media="(prefers-color-scheme: light)">
        <meta name="theme-color" content="#09090b" media="(prefers-color-scheme: dark)">
        {{-- iOS ignores the manifest for standalone mode; these are its equivalents. --}}
        <meta name="mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="default">
        <meta name="apple-mobile-web-app-title" content="{{ config('app.name') }}">

        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.ts', "resources/js/pages/{$page['component']}.vue"])
        <x-inertia::head>
            <title>{{ config('app.name', 'Laravel') }}</title>
        </x-inertia::head>
    </head>
    <body class="bg-background text-foreground font-sans antialiased">
        <x-inertia::app />
    </body>
</html>
