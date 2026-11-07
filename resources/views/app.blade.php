<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <script>
            (function() {
                try {
                    var saved = localStorage.getItem('theme');
                    if (saved === 'light' || saved === 'dark') {
                        document.documentElement.setAttribute('data-theme', saved);
                    }
                } catch (e) {
                    // silent
                }
            })();
        </script>

        {{-- Базовый фон до загрузки CSS: тёмный слой под всем UI (без белых вспышек при прокрутке) --}}
        <style>
            html {
                background-color: #030712;
            }

            html.dark {
                background-color: #030712;
            }

            body {
                background-color: #030712;
            }
        </style>

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

        @vite(['resources/js/app.ts', "resources/js/pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
