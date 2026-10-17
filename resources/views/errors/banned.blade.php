<!DOCTYPE html>
<html lang="fr" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accès Bloqué — Hidden Scan</title>
    @vite(['resources/css/app.css'])
    <style>
        body {
            background-color: #0b0c10;
            color: #e5e7eb;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            padding: 1.5rem;
        }
    </style>
</head>
<body>
    <div class="max-w-lg w-full bg-[#14161f] border border-red-500/30 rounded-xl p-8 text-center shadow-2xl relative overflow-hidden">
        {{-- Aura rouge d'avertissement --}}
        <div class="absolute -top-24 -left-24 w-48 h-48 bg-red-600/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-24 -right-24 w-48 h-48 bg-red-600/10 rounded-full blur-3xl pointer-events-none"></div>

        <div class="w-20 h-20 mx-auto mb-6 rounded-2xl bg-red-500/10 border border-red-500/20 flex items-center justify-center text-red-400 shadow-inner">
            <x-lucide name="ban" class="w-10 h-10 text-red-400" />
        </div>

        <h1 class="text-2xl font-bold text-white mb-2 tracking-tight">Accès Bloqué</h1>
        <p class="text-sm text-red-400 font-semibold mb-6 uppercase tracking-wider">
            @if($banType === 'ip')
                Votre adresse IP a été bannie de la plateforme
            @else
                Votre compte a été banni de Hidden Scan
            @endif
        </p>

        <div class="bg-[#0b0c10] border border-[#232736] rounded-lg p-5 text-left mb-6 space-y-3.5 text-sm">
            <div>
                <span class="text-xs uppercase tracking-wider text-gray-400 block mb-0.5 font-medium">Type de sanction</span>
                @if($bannedUntil)
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded bg-amber-500/10 text-amber-300 font-semibold text-xs border border-amber-500/30">
                        <x-lucide name="clock" class="w-3.5 h-3.5" />
                        <span>Bannissement Temporaire</span>
                    </span>
                @else
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded bg-red-500/10 text-red-400 font-semibold text-xs border border-red-500/30">
                        <x-lucide name="lock" class="w-3.5 h-3.5" />
                        <span>Bannissement Définitif</span>
                    </span>
                @endif
            </div>

            @if($bannedUntil)
                <div>
                    <span class="text-xs uppercase tracking-wider text-gray-400 block mb-0.5 font-medium">Fin du bannissement</span>
                    <span class="text-white font-semibold">
                        {{ $bannedUntil->locale('fr')->translatedFormat('d F Y à H:i') }}
                        <span class="text-xs text-amber-400 ml-1">({{ $bannedUntil->diffForHumans() }})</span>
                    </span>
                </div>
            @endif

            @if($reason)
                <div>
                    <span class="text-xs uppercase tracking-wider text-gray-400 block mb-0.5 font-medium">Motif de la décision</span>
                    <span class="text-gray-200 italic">« {{ $reason }} »</span>
                </div>
            @endif
        </div>

        <p class="text-xs text-gray-400 leading-relaxed mb-6">
            L'accès au site, à vos lectures et à votre espace membre est interrompu conformément aux règles de la communauté Hidden Scan.
            @if($bannedUntil)
                Votre accès sera automatiquement rétabli à la date indiquée.
            @endif
        </p>

        <div class="pt-4 border-t border-[#232736]/60 flex flex-col sm:flex-row gap-3 justify-center text-xs">
            <a href="https://discord.gg" target="_blank" rel="noopener noreferrer" 
               class="px-4 py-2.5 rounded-lg bg-[#5865F2] hover:bg-[#4752c4] text-white font-semibold transition inline-flex items-center justify-center gap-2">
                <span>Rejoindre le support Discord</span>
            </a>
        </div>
    </div>
</body>
</html>
