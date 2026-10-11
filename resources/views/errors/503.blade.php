<!DOCTYPE html>
<html lang="fr" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance en cours — Hidden Scan</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@600;700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            background-color: #07080c;
            color: #ffffff;
            font-family: 'Plus Jakarta Sans', system-ui, sans-serif;
        }
        .font-display {
            font-family: 'Outfit', system-ui, sans-serif;
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4 relative overflow-hidden">
    {{-- Halos d'ambiance --}}
    <div class="absolute -top-32 -left-32 w-80 h-80 bg-red-600/20 rounded-full blur-[100px] pointer-events-none"></div>
    <div class="absolute -bottom-32 -right-32 w-80 h-80 bg-purple-600/20 rounded-full blur-[100px] pointer-events-none"></div>

    <div class="max-w-lg w-full text-center bg-[#0d1018] border border-white/10 rounded-3xl p-8 sm:p-10 shadow-2xl relative overflow-hidden backdrop-blur-xl">
        {{-- Lueur interne --}}
        <div class="absolute top-0 left-1/2 -translate-x-1/2 w-48 h-1 bg-gradient-to-r from-transparent via-[#ff2a55] to-transparent"></div>

        {{-- Logo Mascot --}}
        <div class="relative inline-block mb-6">
            <div class="w-24 h-24 mx-auto rounded-2xl bg-[#141722] border border-white/10 p-2 shadow-xl shadow-black/60 flex items-center justify-center">
                <img src="{{ asset('images/logo.png') }}" alt="Hidden Scan" class="w-20 h-20 object-contain drop-shadow-[0_0_15px_rgba(255,42,85,0.5)] animate-pulse">
            </div>
            <span class="absolute -bottom-2 -right-2 px-2.5 py-0.5 rounded-full bg-[#ff2a55] text-white text-[10px] font-black uppercase tracking-wider shadow-md">
                Refonte
            </span>
        </div>

        {{-- Titres --}}
        <h1 class="font-display text-2xl sm:text-3xl font-extrabold text-white tracking-tight mb-3">
            Hidden<span class="text-[#ff2a55]">Scan</span> fait peau neuve !
        </h1>
        <p class="text-sm text-[#94a3b8] mb-8 leading-relaxed">
            Nous préparons actuellement la nouvelle version du site avec une <strong>toute nouvelle identité visuelle</strong> et des fonctionnalités exclusives pour une expérience de lecture inégalée.
        </p>

        {{-- Boutons d'action --}}
        <div class="flex flex-col gap-3">
            {{-- Rejoindre Discord --}}
            <a href="https://discord.gg/hiddenscan" target="_blank" rel="noopener noreferrer"
               class="w-full py-3.5 px-5 rounded-2xl bg-[#5865F2] hover:bg-[#4752C4] text-white font-bold text-sm transition-all duration-200 flex items-center justify-center gap-2.5 shadow-lg shadow-[#5865F2]/30 hover:scale-[1.02] active:scale-[0.98]">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M20.317 4.37a19.791 19.791 0 0 0-4.885-1.515a.074.074 0 0 0-.079.037c-.21.375-.444.864-.608 1.25a18.27 18.27 0 0 0-5.487 0a12.64 12.64 0 0 0-.617-1.25a.077.077 0 0 0-.079-.037A19.736 19.736 0 0 0 3.677 4.37a.07.07 0 0 0-.032.027C.533 9.046-.32 13.58.099 18.057a.082.082 0 0 0 .031.057a19.9 19.9 0 0 0 5.993 3.03a.078.078 0 0 0 .084-.028a14.09 14.09 0 0 0 1.226-1.994a.076.076 0 0 0-.041-.106a13.107 13.107 0 0 1-1.872-.892a.077.077 0 0 1-.008-.128a10.2 10.2 0 0 0 .372-.292a.074.074 0 0 1 .077-.01c3.929 1.793 8.18 1.793 12.061 0a.074.074 0 0 1 .078.01c.12.098.246.198.373.292a.077.077 0 0 1-.006.127a12.299 12.299 0 0 1-1.873.894a.077.077 0 0 0-.041.107c.36.698.772 1.362 1.225 1.993a.076.076 0 0 0 .084.028a19.839 19.839 0 0 0 6.002-3.03a.077.077 0 0 0 .032-.054c.5-5.177-.838-9.674-3.549-13.66a.061.061 0 0 0-.031-.028zM8.02 15.33c-1.183 0-2.157-1.085-2.157-2.419c0-1.333.956-2.419 2.157-2.419c1.21 0 2.176 1.096 2.157 2.42c0 1.333-.956 2.418-2.157 2.418zm7.975 0c-1.183 0-2.157-1.085-2.157-2.419c0-1.333.955-2.419 2.157-2.419c1.21 0 2.176 1.096 2.157 2.42c0 1.333-.946 2.418-2.157 2.418z"/>
                </svg>
                <span>Suivre les annonces & chapitres sur Discord</span>
            </a>

            {{-- Actualiser --}}
            <button onclick="window.location.reload()"
                    class="w-full py-3 px-5 rounded-2xl bg-[#141722] hover:bg-[#1c2130] text-[#cbd5e1] hover:text-white font-semibold text-sm transition-all duration-200 border border-white/5 flex items-center justify-center gap-2 cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                <span>Vérifier si le site est de retour</span>
            </button>
        </div>

        <p class="mt-6 text-xs text-[#64748b]">
            Hidden Scan Scantrad — Retour très prochainement
        </p>
    </div>
</body>
</html>
