<!DOCTYPE html>
<html lang="fr" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="dark">
    <meta name="description" content="{{ $description ?? 'Hidden Scan — Lis tes mangas, manhwas et manhuas en ligne gratuitement et sans inscription.' }}">
    <meta name="theme-color" content="#14111f">
    <meta property="og:title" content="{{ $title ?? 'Hidden Scan' }}">
    <meta property="og:description" content="{{ $description ?? 'Plateforme de lecture de mangas, manhwas et manhuas. Accès libre, sans compte.' }}">
    <meta property="og:type" content="website">
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    <title>{{ $title ?? 'Hidden Scan' }}</title>

    <script>
    // Système de données locales Hidden Scan
    window.HiddenScan = {
        _getData() {
            try { return JSON.parse(localStorage.getItem('hiddenscan') || '{}'); } catch(e) { return {}; }
        },
        _save(data) {
            try { localStorage.setItem('hiddenscan', JSON.stringify(data)); } catch(e) {}
        },
        toggleFavorite(slug, title, cover) {
            const data = this._getData();
            if (!data.favorites) data.favorites = {};
            if (data.favorites[slug]) {
                delete data.favorites[slug];
            } else {
                data.favorites[slug] = { slug, title, cover, addedAt: new Date().toISOString() };
            }
            this._save(data);
            return !!data.favorites[slug];
        },
        isFavorite(slug) {
            const data = this._getData();
            return !!(data.favorites && data.favorites[slug]);
        },
        getFavorites() {
            const data = this._getData();
            return Object.values(data.favorites || {});
        },
        getHistory() {
            const data = this._getData();
            return data.history || [];
        },
        getProgress() {
            const data = this._getData();
            return data.progress || {};
        },
        getProgressForManga(slug) {
            const progress = this.getProgress();
            return progress[slug] || null;
        },
        exportData() {
            const data = localStorage.getItem('hiddenscan') || '{}';
            const blob = new Blob([data], { type: 'application/json' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'hiddenscan-bibliotheque.json';
            a.click();
            URL.revokeObjectURL(url);
        },
        importData(file) {
            return new Promise((resolve, reject) => {
                const reader = new FileReader();
                reader.onload = (e) => {
                    try {
                        const data = JSON.parse(e.target.result);
                        localStorage.setItem('hiddenscan', JSON.stringify(data));
                        resolve(data);
                    } catch(err) { reject(err); }
                };
                reader.onerror = reject;
                reader.readAsText(file);
            });
        }
    };
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-ink text-chalk min-h-screen font-sans antialiased flex flex-col">

    {{-- ═══ Navbar ═══ --}}
    <nav class="fixed top-0 left-0 right-0 z-50 glass border-b border-line/50" x-data="{ mobileOpen: false, searchOpen: false }">
        <div class="max-w-7xl mx-auto px-4 sm:px-6">
            <div class="flex items-center justify-between h-16">

                {{-- Logo --}}
                <a href="{{ route('home') }}" class="flex items-center gap-3 group">
                    <img src="{{ asset('images/logo.png') }}" alt="Hidden Scan" class="h-9 w-9 rounded-lg transition-transform group-hover:scale-110">
                    <span class="font-display font-extrabold text-xl tracking-tight text-chalk hidden sm:block">
                        Hidden<span class="text-violet">Scan</span>
                    </span>
                </a>

                {{-- Navigation desktop --}}
                <div class="hidden md:flex items-center gap-1">
                    <a href="{{ route('home') }}"
                       class="px-4 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('home') ? 'text-violet bg-violet/10' : 'text-mist hover:text-chalk hover:bg-panel-hi/50' }}">
                        Accueil
                    </a>
                    <a href="{{ route('manga.index') }}"
                       class="px-4 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('manga.*') ? 'text-violet bg-violet/10' : 'text-mist hover:text-chalk hover:bg-panel-hi/50' }}">
                        Catalogue
                    </a>
                    <a href="{{ route('library') }}"
                       class="px-4 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('library') ? 'text-violet bg-violet/10' : 'text-mist hover:text-chalk hover:bg-panel-hi/50' }}">
                        <span class="flex items-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                            Bibliothèque
                        </span>
                    </a>
                </div>

                {{-- Barre de recherche desktop --}}
                <div class="hidden md:flex items-center gap-3">
                    <form action="{{ route('manga.index') }}" method="GET" class="relative">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-mist" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        <input
                            type="text"
                            name="q"
                            placeholder="Rechercher une série..."
                            class="input-field pl-10 pr-4 py-2 w-56 lg:w-72 text-sm !rounded-full !bg-ink-deep/50 !border-line/60 focus:!w-80 transition-all duration-300"
                        >
                    </form>
                </div>

                {{-- Boutons mobile --}}
                <div class="flex md:hidden items-center gap-2">
                    <button @click="searchOpen = !searchOpen" class="p-2 rounded-lg text-mist hover:text-chalk hover:bg-panel-hi transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </button>
                    <button @click="mobileOpen = !mobileOpen" class="p-2 rounded-lg text-mist hover:text-chalk hover:bg-panel-hi transition">
                        <svg x-show="!mobileOpen" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                        <svg x-show="mobileOpen" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </div>

            {{-- Recherche mobile --}}
            <div x-show="searchOpen" x-cloak x-transition class="md:hidden pb-3">
                <form action="{{ route('manga.index') }}" method="GET">
                    <input type="text" name="q" placeholder="Rechercher une série..."
                        class="input-field w-full text-sm !rounded-full" autofocus>
                </form>
            </div>
        </div>

        {{-- Menu mobile --}}
        <div x-show="mobileOpen" x-cloak x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-2"
             class="md:hidden border-t border-line/50 bg-ink-deep/95 backdrop-blur-lg">
            <div class="px-4 py-3 space-y-1">
                <a href="{{ route('home') }}" class="block px-4 py-3 rounded-lg text-sm font-medium {{ request()->routeIs('home') ? 'text-violet bg-violet/10' : 'text-mist hover:text-chalk hover:bg-panel-hi/50' }} transition">
                    Accueil
                </a>
                <a href="{{ route('manga.index') }}" class="block px-4 py-3 rounded-lg text-sm font-medium {{ request()->routeIs('manga.*') ? 'text-violet bg-violet/10' : 'text-mist hover:text-chalk hover:bg-panel-hi/50' }} transition">
                    Catalogue
                </a>
                <a href="{{ route('library') }}" class="block px-4 py-3 rounded-lg text-sm font-medium {{ request()->routeIs('library') ? 'text-violet bg-violet/10' : 'text-mist hover:text-chalk hover:bg-panel-hi/50' }} transition">
                    ♥ Bibliothèque
                </a>
            </div>
        </div>
    </nav>

    {{-- ═══ Contenu principal ═══ --}}
    <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 pt-20 pb-12">
        {{ $slot }}
    </main>

    {{-- ═══ Footer ═══ --}}
    <footer class="border-t border-line/50 bg-ink-deep/50 mt-auto">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 py-12">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8 mb-8">
                {{-- Marque --}}
                <div class="sm:col-span-2 lg:col-span-1">
                    <a href="{{ route('home') }}" class="flex items-center gap-3 mb-4">
                        <img src="{{ asset('images/logo.png') }}" alt="Hidden Scan" class="h-8 w-8 rounded-lg">
                        <span class="font-display font-extrabold text-lg text-chalk">Hidden<span class="text-violet">Scan</span></span>
                    </a>
                    <p class="text-mist text-sm leading-relaxed">Plateforme de lecture de mangas, manhwas et manhuas. Accès libre, sans compte.</p>
                </div>

                {{-- Navigation --}}
                <div>
                    <h4 class="font-display font-bold text-sm text-chalk mb-3 uppercase tracking-wider">Navigation</h4>
                    <ul class="space-y-2">
                        <li><a href="{{ route('home') }}" class="text-sm text-mist hover:text-violet transition">Accueil</a></li>
                        <li><a href="{{ route('manga.index') }}" class="text-sm text-mist hover:text-violet transition">Catalogue</a></li>
                        <li><a href="{{ route('library') }}" class="text-sm text-mist hover:text-violet transition">Bibliothèque</a></li>
                    </ul>
                </div>

                {{-- Légal --}}
                <div>
                    <h4 class="font-display font-bold text-sm text-chalk mb-3 uppercase tracking-wider">Légal</h4>
                    <ul class="space-y-2">
                        <li><a href="{{ route('pages.mentions') }}" class="text-sm text-mist hover:text-violet transition">Mentions légales</a></li>
                        <li><a href="{{ route('pages.cgu') }}" class="text-sm text-mist hover:text-violet transition">CGU</a></li>
                        <li><a href="{{ route('pages.confidentialite') }}" class="text-sm text-mist hover:text-violet transition">Confidentialité</a></li>
                        <li><a href="{{ route('pages.dmca') }}" class="text-sm text-mist hover:text-violet transition">DMCA</a></li>
                    </ul>
                </div>

                {{-- Communauté --}}
                <div>
                    <h4 class="font-display font-bold text-sm text-chalk mb-3 uppercase tracking-wider">Communauté</h4>
                    <ul class="space-y-2">
                        <li>
                            <a href="#" class="text-sm text-mist hover:text-violet transition inline-flex items-center gap-2">
                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M20.317 4.37a19.791 19.791 0 00-4.885-1.515.074.074 0 00-.079.037c-.21.375-.444.864-.608 1.25a18.27 18.27 0 00-5.487 0 12.64 12.64 0 00-.617-1.25.077.077 0 00-.079-.037A19.736 19.736 0 003.677 4.37a.07.07 0 00-.032.027C.533 9.046-.32 13.58.099 18.057a.082.082 0 00.031.057 19.9 19.9 0 005.993 3.03.078.078 0 00.084-.028c.462-.63.874-1.295 1.226-1.994a.076.076 0 00-.041-.106 13.107 13.107 0 01-1.872-.892.077.077 0 01-.008-.128 10.2 10.2 0 00.372-.292.074.074 0 01.077-.01c3.928 1.793 8.18 1.793 12.062 0a.074.074 0 01.078.01c.12.098.246.198.373.292a.077.077 0 01-.006.127 12.299 12.299 0 01-1.873.892.077.077 0 00-.041.107c.36.698.772 1.362 1.225 1.993a.076.076 0 00.084.028 19.839 19.839 0 006.002-3.03.077.077 0 00.032-.054c.5-5.177-.838-9.674-3.549-13.66a.061.061 0 00-.031-.03z"/></svg>
                                Discord
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            {{-- Copyright --}}
            <div class="pt-6 border-t border-line/30 flex flex-col sm:flex-row items-center justify-between gap-3">
                <p class="text-mist text-xs">© {{ date('Y') }} Hidden Scan — Tous droits réservés</p>
                <p class="text-mist/60 text-xs">Fait avec ♥ pour la communauté</p>
            </div>
        </div>
    </footer>

    {{-- Bouton retour en haut --}}
    <button
        x-data="{ show: false }"
        x-init="window.addEventListener('scroll', () => show = window.scrollY > 500)"
        x-show="show"
        x-cloak
        x-transition
        @click="window.scrollTo({ top: 0, behavior: 'smooth' })"
        class="fixed bottom-6 right-6 z-40 p-3 rounded-full bg-violet text-white shadow-lg shadow-violet/25 hover:bg-violet-deep transition-all hover:scale-110 cursor-pointer"
    >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
    </button>

</body>
</html>