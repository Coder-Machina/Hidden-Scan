<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Hidden Scan') }}</title>

        <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">

        <!-- Fonts & Assets -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-chalk bg-ink min-h-screen antialiased flex flex-col justify-center items-center p-4 relative overflow-x-hidden">
        
        {{-- En-tête / Logo --}}
        <div class="mb-6">
            <a href="{{ route('home') }}" class="flex items-center gap-3 px-4 py-2.5 bg-panel-hi border border-line/50 rounded-2xl hover:bg-panel-hover transition group shadow-lg">
                <img src="{{ asset('images/logo.png') }}" alt="Hidden Scan" class="h-9 w-9 rounded-xl object-cover group-hover:scale-105 transition-transform duration-300">
                <span class="font-display font-extrabold text-xl tracking-tight text-chalk">
                    Hidden<span class="text-mist">Scan</span>
                </span>
            </a>
        </div>

        {{-- Contenu dans boîte dark --}}
        <div class="w-full sm:max-w-md bg-panel border border-line/50 shadow-2xl overflow-hidden rounded-3xl p-6 sm:p-8">
            {{ $slot }}
        </div>
    </body>
</html>
