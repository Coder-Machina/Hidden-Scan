<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Hidden Scan' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#14111f] text-[#ece9f7] min-h-screen">

    <nav class="bg-[#1d1930] border-b border-[#3d3660] px-4 py-3">
        <div class="max-w-7xl mx-auto flex items-center justify-between">
            <a href="{{ route('home') }}" class="text-[#9b7bff] font-bold text-xl">
                Hidden Scan
            </a>
            <div class="flex items-center gap-6">
                <a href="{{ route('manga.index') }}" class="text-[#a39fc0] hover:text-[#ece9f7] transition">Catalogue</a>
                <form action="{{ route('manga.index') }}" method="GET">
                    <input
                        type="text"
                        name="q"
                        placeholder="Rechercher..."
                        class="bg-[#14111f] border border-[#3d3660] rounded-lg px-3 py-1.5 text-sm text-[#ece9f7] placeholder-[#a39fc0] focus:outline-none focus:border-[#9b7bff] w-48"
                    >
                </form>
            </div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto px-4 py-8">
        {{ $slot }}
    </main>

    <footer class="border-t border-[#3d3660] mt-16 py-8 text-center text-[#a39fc0] text-sm">
        <p>© {{ date('Y') }} Hidden Scan — Tous droits réservés</p>
    </footer>

</body>
</html>