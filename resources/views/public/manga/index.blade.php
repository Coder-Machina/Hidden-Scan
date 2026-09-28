@use('Illuminate\Support\Facades\Storage')
<x-layouts.public title="Catalogue — Hidden Scan">

    {{-- ═══ Header ═══ --}}
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between mb-8 gap-4 animate-fade-in">
        <div>
            <h1 class="font-display font-extrabold text-3xl sm:text-4xl tracking-tight">Catalogue</h1>
            <p class="text-mist mt-1">{{ $mangas->total() }} séries disponibles</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('manga.random') }}"
               onclick="if(window.HiddenScan && !{{ auth()->check() ? 'true' : 'false' }}){ const favs = window.HiddenScan.getFavorites().map(f => f.slug).join(','); if(favs) this.href = '{{ route('manga.random') }}?favs=' + encodeURIComponent(favs); }"
               class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-gradient-to-r from-red-600/20 via-red-600/10 to-amber-600/20 hover:from-red-600/30 hover:to-amber-600/30 text-chalk text-sm font-semibold border border-red-500/30 hover:border-red-500/50 shadow-lg shadow-red-950/20 hover:scale-105 active:scale-95 transition-all">
                <span class="text-base">🎲</span>
                <span>Surprise-moi</span>
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
                        class="w-full flex items-center justify-between px-4 py-3 bg-panel border border-line/50 rounded-xl text-sm font-semibold text-chalk shadow-sm hover:bg-panel-hi transition">
                    <span class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-violet" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/></svg>
                        Filtres Avancés
                        @if(request()->hasAny(['q','include_genres','exclude_genres','type','status','sort']))
                            <span class="w-2 h-2 rounded-full bg-violet animate-pulse"></span>
                        @endif
                    </span>
                    <span class="text-xs text-mist flex items-center gap-1.5">
                        <span x-text="mobileFiltersOpen ? 'Masquer' : 'Afficher'">Afficher</span>
                        <svg class="w-4 h-4 transition-transform duration-200" :class="mobileFiltersOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </span>
                </button>
            </div>

            <form method="GET" action="{{ route('manga.index') }}" 
                  class="bg-panel border border-line/50 rounded-xl p-5 sticky top-24"
                  :class="mobileFiltersOpen ? 'block' : 'hidden lg:block'">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="font-display font-bold text-lg text-chalk flex items-center gap-2">
                        <svg class="w-5 h-5 text-violet" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/></svg>
                        Filtres Avancés
                    </h2>
                    @if(request()->hasAny(['q','include_genres','exclude_genres','type','status','sort']))
                        <a href="{{ route('manga.index') }}" class="text-xs text-rose hover:text-rose/80 transition" title="Réinitialiser">
                            Effacer
                        </a>
                    @endif
                </div>

                {{-- Recherche --}}
                <div class="mb-5">
                    <label class="block text-xs font-semibold text-mist mb-2 uppercase tracking-wider">Recherche</label>
                    <div class="relative">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-mist" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        <input type="text" name="q" value="{{ request('q') }}" placeholder="Titre, auteur..."
                            class="input-field pl-10 bg-ink-deep/50">
                    </div>
                </div>

                {{-- Type & Statut --}}
                <div class="grid grid-cols-2 gap-3 mb-5">
                    <div>
                        <label class="block text-xs font-semibold text-mist mb-2 uppercase tracking-wider">Type</label>
                        <select name="type" class="input-field cursor-pointer bg-ink-deep/50 text-sm !px-2">
                            <option value="">Tous</option>
                            <option value="manga" {{ request('type') == 'manga' ? 'selected' : '' }}>Manga</option>
                            <option value="manhwa" {{ request('type') == 'manhwa' ? 'selected' : '' }}>Manhwa</option>
                            <option value="manhua" {{ request('type') == 'manhua' ? 'selected' : '' }}>Manhua</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-mist mb-2 uppercase tracking-wider">Statut</label>
                        <select name="status" class="input-field cursor-pointer bg-ink-deep/50 text-sm !px-2">
                            <option value="">Tous</option>
                            <option value="en_cours" {{ request('status') == 'en_cours' ? 'selected' : '' }}>En cours</option>
                            <option value="termine" {{ request('status') == 'termine' ? 'selected' : '' }}>Terminé</option>
                            <option value="pause" {{ request('status') == 'pause' ? 'selected' : '' }}>En pause</option>
                        </select>
                    </div>
                </div>

                {{-- Tri --}}
                <div class="mb-5">
                    <label class="block text-xs font-semibold text-mist mb-2 uppercase tracking-wider">Trier par</label>
                    <select name="sort" class="input-field cursor-pointer bg-ink-deep/50">
                        <option value="latest" {{ request('sort', 'latest') == 'latest' ? 'selected' : '' }}>Plus récent</option>
                        <option value="popular" {{ request('sort') == 'popular' ? 'selected' : '' }}>Plus populaire</option>
                        <option value="rating" {{ request('sort') == 'rating' ? 'selected' : '' }}>Meilleure note</option>
                        <option value="title" {{ request('sort') == 'title' ? 'selected' : '' }}>Ordre alphabétique</option>
                    </select>
                </div>

                {{-- Genres --}}
                <div class="mb-6" x-data="{ open: true }">
                    <button type="button" @click="open = !open" class="flex items-center justify-between w-full text-xs font-semibold text-mist mb-2 uppercase tracking-wider focus:outline-none">
                        Genres
                        <svg class="w-4 h-4 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="open" x-collapse class="space-y-1 max-h-60 overflow-y-auto custom-scrollbar pr-2">
                        @foreach($genres as $genre)
                        <div class="flex items-center justify-between group">
                            <label class="flex items-center gap-2 cursor-pointer flex-grow text-sm text-chalk group-hover:text-violet transition py-1">
                                <input type="checkbox" name="include_genres[]" value="{{ $genre->slug }}" class="rounded bg-ink-deep border-line text-violet focus:ring-violet/50"
                                    {{ in_array($genre->slug, (array)request('include_genres', [])) ? 'checked' : '' }}>
                                {{ $genre->name }}
                            </label>
                            {{-- Checkbox pour exclure (Optionnel, affiché au survol ou si déjà coché pour exclusion) --}}
                            <label class="cursor-pointer" title="Exclure ce genre">
                                <input type="checkbox" name="exclude_genres[]" value="{{ $genre->slug }}" class="rounded-full bg-ink-deep border-line text-rose focus:ring-rose/50"
                                    {{ in_array($genre->slug, (array)request('exclude_genres', [])) ? 'checked' : '' }}>
                            </label>
                        </div>
                        @endforeach
                        <p class="text-[10px] text-mist/70 mt-2 italic">Cochez la case ronde pour exclure un genre.</p>
                    </div>
                </div>

                <button type="submit" class="btn-primary w-full justify-center !py-3 shadow-lg shadow-violet/20 hover:shadow-violet/40">
                    Appliquer les filtres
                </button>
            </form>
        </aside>

        {{-- Contenu Principal --}}
        <div class="flex-grow w-full">
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
        <div class="text-center py-24 animate-fade-in">
            <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-panel-hi flex items-center justify-center">
                <svg class="w-8 h-8 text-mist" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <p class="text-lg text-mist mb-2">Aucune série trouvée</p>
            <a href="{{ route('manga.index') }}" class="text-violet hover:text-violet-glow transition">Voir tout le catalogue →</a>
        </div>
    @endif
        </div>
    </div>

</x-layouts.public>