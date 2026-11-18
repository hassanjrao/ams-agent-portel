<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"  @class(['dark' => ($appearance ?? 'system') == 'dark'])>
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
                background-color: #fbf7ef;
            }

            html.dark {
                background-color: #120d08;
            }
        </style>

        <meta name="theme-color" content="#15100a">
        <meta name="robots" content="noindex">
        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        {{-- Link previews (WhatsApp, Facebook, X): crawlers read these, they don't run the app. --}}
        @php($preview = \App\Support\LinkPreview::for($page['component'] ?? null, url()->current()))
        <meta name="description" content="{{ $preview['description'] }}">
        <meta property="og:type" content="website">
        <meta property="og:site_name" content="AMS Global Travel &amp; Tours">
        <meta property="og:title" content="{{ $preview['title'] }}">
        <meta property="og:description" content="{{ $preview['description'] }}">
        <meta property="og:url" content="{{ $preview['url'] }}">
        <meta property="og:image" content="{{ $preview['image'] }}">
        <meta property="og:image:secure_url" content="{{ $preview['image'] }}">
        <meta property="og:image:type" content="image/jpeg">
        <meta property="og:image:width" content="1200">
        <meta property="og:image:height" content="630">
        <meta property="og:image:alt" content="AMS Global Travel &amp; Tours agent portal: Umrah and group seats at agent fares">
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="{{ $preview['title'] }}">
        <meta name="twitter:description" content="{{ $preview['description'] }}">
        <meta name="twitter:image" content="{{ $preview['image'] }}">

        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.ts', "resources/js/pages/{$page['component']}.vue"])
        <x-inertia::head>
            <title>{{ $preview['title'] }}</title>
        </x-inertia::head>
    </head>
    <body class="font-sans antialiased">
        <x-inertia::app />
    </body>
</html>
