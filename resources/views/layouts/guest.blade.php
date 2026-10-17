<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="color-scheme" content="dark">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Hidden Scan') }}</title>

        <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">

        {{-- Fonts --}}
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Nunito:wght@600;700;800;900&display=swap" rel="stylesheet">

        <!-- Assets -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-[var(--bg-base)] text-[var(--text-1)] min-h-screen antialiased flex flex-col justify-center items-center p-4 relative font-sans">
        
        {{-- En-tête / Logo --}}
        <div class="mb-6">
            <a href="{{ route('home') }}" class="flex items-center gap-2.5 group">
                <img src="{{ asset('images/logo.png') }}" alt="Hidden Scan" class="w-8 h-8 rounded-[4px] object-cover">
                <span class="font-heading font-extrabold text-[22px] tracking-tight text-[var(--text-1)]">
                    HIDDEN<span class="text-[var(--accent)]">SCAN</span>
                </span>
            </a>
        </div>

        {{-- Contenu dans boîte épurée (Radius 6px, Border 1px) --}}
        <div class="w-full sm:max-w-md bg-[var(--bg-surface)] border border-[var(--border)] rounded-[6px] p-6 sm:p-8 shadow-sm">
            {{ $slot }}
        </div>
    </body>
</html>
