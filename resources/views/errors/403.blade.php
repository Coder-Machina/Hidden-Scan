<!DOCTYPE html>
<html lang="fr" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 — Accès Refusé | Hidden Scan</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            background-color: #0c0d12;
            color: #ffffff;
            font-family: system-ui, -apple-system, sans-serif;
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full text-center bg-[#13141d] border border-[#222234] rounded-3xl p-8 shadow-2xl relative overflow-hidden">
        {{-- Halo d'arrière-plan --}}
        <div class="absolute -top-24 -left-24 w-48 h-48 bg-red-600/15 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-24 -right-24 w-48 h-48 bg-purple-600/15 rounded-full blur-3xl pointer-events-none"></div>

        {{-- Badge Code 403 --}}
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-red-600/15 border border-red-500/30 text-red-500 mb-6 shadow-inner">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m0 0v2m0-2h2m-2 0H10m11-3.5a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </div>

        <h1 class="text-3xl font-extrabold text-white tracking-tight mb-2">Accès Refusé (403)</h1>
        <p class="text-sm text-[#94a3b8] mb-6 leading-relaxed">
            @if(auth()->check())
                Vous êtes connecté en tant que <strong class="text-white">{{ auth()->user()->name }}</strong>. Ce compte ne dispose pas des autorisations nécessaires pour accéder à cette zone.
            @else
                Cette section est strictement réservée à l'équipe Staff de Hidden Scan.
            @endif
        </p>

        <div class="flex flex-col gap-3">
            @if(auth()->check())
                <form method="POST" action="{{ route('logout', ['redirect' => '/admin/login']) }}">
                    @csrf
                    <button type="submit" class="w-full py-3 px-4 rounded-xl bg-[#dc2626] hover:bg-[#b91c1c] text-white font-bold text-sm transition shadow-lg shadow-red-950/50 flex items-center justify-center gap-2 cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                        <span>Se déconnecter et aller à la connexion Admin</span>
                    </button>
                </form>
            @else
                <a href="/admin/login" class="w-full py-3 px-4 rounded-xl bg-[#dc2626] hover:bg-[#b91c1c] text-white font-bold text-sm transition shadow-lg shadow-red-950/50 flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>
                    <span>Connexion Staff & Administration</span>
                </a>
            @endif

            <a href="/" class="w-full py-3 px-4 rounded-xl bg-[#1c1d29] hover:bg-[#252837] text-[#cbd5e1] font-semibold text-sm transition border border-[#2b2e42] flex items-center justify-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                <span>Retourner à l'accueil</span>
            </a>
        </div>
    </div>
</body>
</html>
