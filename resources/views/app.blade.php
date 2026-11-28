<!DOCTYPE html>
<html lang="{{ request()->cookie('dag_lang') === 'en' ? 'en' : 'fr' }}" @class(['dark' => ($appearance ?? 'system') == 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        {{-- Inline script to detect system dark mode preference and apply it immediately --}}
        <script>
            (function() {
                const appearance = '{{ $appearance ?? "system" }}';

                if (appearance === 'system') {
                    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

                    if (prefersDark) {
                        document.documentElement.classList.add('dark');
                    }
                }
            })();
        </script>

        {{-- Inline style to set the HTML background color based on our theme in app.css --}}
        <style>
            html {
                background-color: oklch(1 0 0);
            }

            html.dark {
                background-color: oklch(0.145 0 0);
            }
        </style>

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon-96x96.png" type="image/png" sizes="96x96">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        {{-- Installable application (PWA): public/manifest.json and public/sw.js --}}
        <link rel="manifest" href="/manifest.json">
        <meta name="theme-color" content="#071f41">
        <meta name="mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-title" content="DAG">
        <meta name="apple-mobile-web-app-status-bar-style" content="default">

        @fonts

        @viteReactRefresh
        @vite(['resources/css/app.css', 'resources/js/app.tsx', "resources/js/pages/{$page['component']}.tsx"])
        @php($referencement = \App\Support\Referencement::pour($page))
        <x-inertia::head>
            <title>{{ $referencement['titre'] }}</title>
        </x-inertia::head>

        {{-- Search engines and link previews (App\Support\Referencement) --}}
        @if ($referencement['indexable'])
            <meta name="description" content="{{ $referencement['description'] }}">
            <link rel="canonical" href="{{ $referencement['canonique'] }}">
            <meta property="og:type" content="website">
            <meta property="og:site_name" content="{{ config('entreprise.nom') }}">
            <meta property="og:locale" content="fr_FR">
            <meta property="og:title" content="{{ $referencement['titre'] }}">
            <meta property="og:description" content="{{ $referencement['description'] }}">
            <meta property="og:url" content="{{ $referencement['canonique'] }}">
            <meta property="og:image" content="{{ $referencement['image'] }}">
            <meta property="og:image:width" content="1200">
            <meta property="og:image:height" content="630">
            <meta property="og:image:alt" content="{{ config('entreprise.nom') }}, agence digitale à Douala, Cameroun">
            <meta name="twitter:card" content="summary_large_image">
            @if ($page['component'] === 'public/home' && config('services.google_search_console'))
                <meta name="google-site-verification" content="{{ config('services.google_search_console') }}">
            @endif
            @foreach ($referencement['donneesStructurees'] as $donnees)
                <script type="application/ld+json">{!! json_encode($donnees, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
            @endforeach
        @else
            <meta name="robots" content="noindex, nofollow">
        @endif
    </head>
    <body class="font-sans antialiased">
        <x-inertia::app />
    </body>
</html>
