@use('Illuminate\Support\Facades\Storage')
<x-layouts.public title="Catalogue — Hidden Scan">

    {{-- ═══ Header ═══ --}}
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between mb-8 gap-4 animate-fade-in">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="w-2 h-4 rounded-full bg-crimson inline-block"></span>
                <span class="text-xs font-bold uppercase tracking-wider text-crimson">EXPLORATION</span>
            </div>
            <h1 class="font-display font-black text-3xl sm:text-4xl tracking-tight text-chalk">Catalogue</h1>
            <p class="text-mist text-sm mt-1">{{ $mangas->total() }} séries prêtes à être dévorées</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('manga.random') }}"
               onclick="if(window.HiddenScan && !{{ auth()->check() ? 'true' : 'false' }}){ const favs = window.HiddenScan.getFavorites().map(f => f.slug).join(','); if(favs) this.href = '{{ route('manga.random') }}?favs=' + encodeURIComponent(favs); }"
               class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-gradient-to-r from-crimson/20 via-panel to-purple/20 hover:from-crimson/30 hover:to-purple/30 text-chalk text-sm font-semibold border border-crimson/40 hover:border-crimson/60 shadow-lg shadow-crimson/15 hover:scale-105 active:scale-95 transition-all">
                <span class="text-base">🎲</span>
                <span>Surprends-moi</span>
                <span class="text-[10px] px-1.5 py-0.5 rounded bg-amber-500/20 text-amber-300 font-extrabold uppercase tracking-wider border border-amber-500/30">Ciblé</span>
            </a>
        </div>
    </div>

    {{-- ═══ Layout Catalogue ═══ --}}
    <div class="flex flex-col lg:flex-row gap-8">
        
        {{-- Sidebar Filtres Avancés --}}
        <aside class="w-full lg:w-72 flex-shrink-0 animate-fade-in-up" style="animation-delay: 0.05s;" x-data="{ mobileFiltersOpen: false }">
            {{-- Bouton Toggle Mobile --}}
            <div class="lg:hidden mb-4">
                <button type="button" @click="mobileFiltersOpen = !mobileFiltersOpen"
                        class="w-full flex items-center justify-between px-4 py-3 bg-[#0D1018] border border-white/10 rounded-xl text-sm font-semibold text-chalk shadow-sm hover:bg-white/5 transition">
                    <span class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-crimson" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/></svg>
                        Filtres Avancés
                        @if(request()->hasAny(['q','include_genres','exclude_genres','type','status','sort']))
                            <span class="w-2 h-2 rounded-full bg-crimson animate-pulse"></span>
                        @endif
                    </span>
                    <span class="text-xs text-mist flex items-center gap-1.5">
                        <span x-text="mobileFiltersOpen ? 'Masquer' : 'Afficher'">Afficher</span>
                        <svg class="w-4 h-4 transition-transform duration-200" :class="mobileFiltersOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </span>
                </button>
            </div>

            <form method="GET" action="{{ route('manga.index') }}" 
                  class="bg-[#0D1018] border border-white/[0.08] rounded-2xl p-5 sticky top-24 shadow-xl"
                  :class="mobileFiltersOpen ? 'block' : 'hidden lg:block'">
                <div class="flex items-center justify-between mb-6 pb-4 border-b border-white/[0.06]">
                    <h2 class="font-display font-extrabold text-base text-chalk flex items-center gap-2 tracking-wide uppercase">
                        <svg class="w-4 h-4 text-crimson" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/></svg>
                        Filtres Avancés
                    </h2>
                    @if(request()->hasAny(['q','include_genres','exclude_genres','type','status','sort']))
                        <a href="{{ route('manga.index') }}" class="text-xs text-crimson hover:underline font-semibold" title="Réinitialiser">
                            Effacer
                        </a>
                    @endif
                </div>

                {{-- Recherche --}}
                <div class="mb-5">
                    <label class="block text-xs font-bold text-mist mb-2 uppercase tracking-wider">Recherche</label>
                    <div class="relative">
                        <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-mist" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        <input type="text" name="q" value="{{ request('q') }}" placeholder="Titre, auteur..."
                            class="input-field pl-10 bg-[#07080C] border-white/10 focus:border-crimson focus:ring-crimson/30 rounded-xl text-sm">
                    </div>
                </div>

                {{-- Type & Statut --}}
                <div class="grid grid-cols-2 gap-3 mb-5">
                    <div>
                        <label class="block text-xs font-bold text-mist mb-2 uppercase tracking-wider">Type</label>
                        <select name="type" class="input-field cursor-pointer bg-[#07080C] border-white/10 text-sm !px-2 rounded-xl focus:border-crimson">
                            <option value="">Tous</option>
                            <option value="manga" {{ request('type') == 'manga' ? 'selected' : '' }}>Manga</option>
                            <option value="manhwa" {{ request('type') == 'manhwa' ? 'selected' : '' }}>Manhwa</option>
                            <option value="manhua" {{ request('type') == 'manhua' ? 'selected' : '' }}>Manhua</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-mist mb-2 uppercase tracking-wider">Statut</label>
                        <select name="status" class="input-field cursor-pointer bg-[#07080C] border-white/10 text-sm !px-2 rounded-xl focus:border-crimson">
                            <option value="">Tous</option>
                            <option value="en_cours" {{ request('status') == 'en_cours' ? 'selected' : '' }}>En cours</option>
                            <option value="termine" {{ request('status') == 'termine' ? 'selected' : '' }}>Terminé</option>
                            <option value="pause" {{ request('status') == 'pause' ? 'selected' : '' }}>En pause</option>
                        </select>
                    </div>
                </div>

                {{-- Tri --}}
                <div class="mb-5">
                    <label class="block text-xs font-bold text-mist mb-2 uppercase tracking-wider">Trier par</label>
                    <select name="sort" class="input-field cursor-pointer bg-[#07080C] border-white/10 rounded-xl focus:border-crimson">
                        <option value="latest" {{ request('sort', 'latest') == 'latest' ? 'selected' : '' }}>Plus récent</option>
                        <option value="popular" {{ request('sort') == 'popular' ? 'selected' : '' }}>Plus populaire</option>
                        <option value="rating" {{ request('sort') == 'rating' ? 'selected' : '' }}>Meilleure note</option>
                        <option value="title" {{ request('sort') == 'title' ? 'selected' : '' }}>Ordre alphabétique</option>
                    </select>
                </div>

                {{-- Genres --}}
                <div class="mb-6" x-data="{ open: true }">
                    <button type="button" @click="open = !open" class="flex items-center justify-between w-full text-xs font-bold text-mist mb-2.5 uppercase tracking-wider focus:outline-none">
                        <span>Genres</span>
                        <svg class="w-4 h-4 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="open" x-collapse class="space-y-1 max-h-56 overflow-y-auto custom-scrollbar pr-2">
                        @foreach($genres as $genre)
                        <div class="flex items-center justify-between group py-1 px-1 rounded-lg hover:bg-white/[0.02]">
                            <label class="flex items-center gap-2.5 cursor-pointer flex-grow text-xs font-medium text-chalk group-hover:text-crimson transition">
                                <input type="checkbox" name="include_genres[]" value="{{ $genre->slug }}" class="rounded bg-[#07080C] border-white/20 text-crimson focus:ring-crimson/30"
                                    {{ in_array($genre->slug, (array)request('include_genres', [])) || request('genre') === $genre->slug ? 'checked' : '' }}>
                                <span>{{ $genre->name }}</span>
                            </label>
                            <label class="cursor-pointer" title="Exclure ce genre">
                                <input type="checkbox" name="exclude_genres[]" value="{{ $genre->slug }}" class="rounded-full bg-[#07080C] border-white/20 text-crimson focus:ring-crimson/30"
                                    {{ in_array($genre->slug, (array)request('exclude_genres', [])) ? 'checked' : '' }}>
                            </label>
                        </div>
                        @endforeach
                    </div>
                </div>

                <button type="submit" class="btn-primary w-full justify-center !py-3">
                    Appliquer les filtres
                </button>
            </form>
        </aside>

        {{-- Contenu Principal --}}
        <div class="flex-grow w-full">
            {{-- Badges des filtres actifs --}}
            @if(request()->hasAny(['q', 'include_genres', 'exclude_genres', 'type', 'status', 'genre']))
            <div class="flex flex-wrap items-center gap-2 mb-6 p-3.5 rounded-2xl bg-[#0D1018] border border-white/10 animate-fade-in shadow-md">
                <span class="text-xs text-mist font-semibold">Filtres actifs :</span>
                @if(request('genre'))
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-crimson/15 border border-crimson/35 text-xs text-chalk font-semibold">
                        Genre: {{ request('genre') }}
                        <a href="{{ route('manga.index', request()->except('genre')) }}" class="hover:text-crimson font-bold ml-1">✕</a>
                    </span>
                @endif
                @if(request('type'))
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white/5 border border-white/10 text-xs text-chalk font-semibold">
                        Type: {{ ucfirst(request('type')) }}
                        <a href="{{ route('manga.index', request()->except('type')) }}" class="hover:text-crimson font-bold ml-1">✕</a>
                    </span>
                @endif
                @if(request('status'))
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white/5 border border-white/10 text-xs text-chalk font-semibold">
                        Statut: {{ ucfirst(request('status')) }}
                        <a href="{{ route('manga.index', request()->except('status')) }}" class="hover:text-crimson font-bold ml-1">✕</a>
                    </span>
                @endif
                @if(request('q'))
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white/5 border border-white/10 text-xs text-chalk font-semibold">
                        « {{ request('q') }} »
                        <a href="{{ route('manga.index', request()->except('q')) }}" class="hover:text-crimson font-bold ml-1">✕</a>
                    </span>
                @endif
                <a href="{{ route('manga.index') }}" class="text-xs text-crimson hover:underline ml-auto font-bold">Tout effacer</a>
            </div>
            @endif

            {{-- ═══ Grille ═══ --}}
    @if($mangas->count())
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4 mb-10">
            @foreach($mangas as $index => $manga)
                <div class="animate-fade-in" style="animation-delay: {{ min($index * 0.03, 0.3) }}s;">
                    <x-manga-card :manga="$manga" />
                </div>
            @endforeach
        </div>

        {{-- Pagination --}}
        <div class="flex justify-center">
            {{ $mangas->withQueryString()->links() }}
        </div>
    @else
        <div class="text-center py-24 bg-[#0D1018] border border-white/[0.08] rounded-2xl p-8 animate-fade-in">
            <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-white/5 border border-white/10 flex items-center justify-center">
                <svg class="w-8 h-8 text-mist" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <p class="text-lg font-bold text-chalk mb-2">Aucune série trouvée</p>
            <p class="text-xs text-mist mb-6 max-w-sm mx-auto">Essayez d'ajuster vos critères de recherche ou de réinitialiser vos filtres.</p>
            <a href="{{ route('manga.index') }}" class="btn-primary !px-6 !py-2.5">Voir tout le catalogue</a>
        </div>
    @endif
        </div>
    </div>

</x-layouts.public>