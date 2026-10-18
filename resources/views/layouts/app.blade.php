<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="color-scheme" content="dark">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <meta property="og:title" content="{{ config('app.name', 'Hidden Scan') }}">
        <meta property="og:type" content="website">
        <meta property="og:image" content="{{ $ogImage ?? (View::hasSection('og_image') ? View::yieldContent('og_image') : asset('images/og-default.jpg')) }}">
        <meta property="og:image:width" content="1200">
        <meta property="og:image:height" content="630">
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:image" content="{{ $ogImage ?? (View::hasSection('og_image') ? View::yieldContent('og_image') : asset('images/og-default.jpg')) }}">

        <title>{{ config('app.name', 'Hidden Scan') }}</title>

        <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Nunito:wght@600;700;800;900&display=swap" rel="stylesheet">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-[var(--bg-base)] text-[var(--text-1)] min-h-screen antialiased font-sans">
        <div class="min-h-screen bg-[var(--bg-base)]">

            <!-- Page Heading -->
            @isset($header)
                <header class="bg-[var(--bg-surface)] border-b border-[var(--border)]">
                    <div class="max-w-[1280px] mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            @isset($slot)
                <main class="max-w-[1280px] mx-auto px-4 sm:px-6 lg:px-8 py-8">
                    {{ $slot }}
                </main>
            @else
                <main class="w-full">
                    @yield('content')
                </main>
            @endisset
        </div>
    </body>
</html>
