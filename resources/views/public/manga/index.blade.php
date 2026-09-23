@use('Illuminate\Support\Facades\Storage')
<x-layouts.public title="Catalogue — Hidden Scan">

    {{-- ═══ Header ═══ --}}
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between mb-8 gap-4 animate-fade-in">
        <div>
            <h1 class="font-display font-extrabold text-3xl sm:text-4xl tracking-tight">Catalogue</h1>
            <p class="text-mist mt-1">{{ $mangas->total() }} séries disponibles</p>
        </div>
    </div>

    {{-- ═══ Filtres ═══ --}}
    <form method="GET" action="{{ route('manga.index') }}"
          class="bg-panel border border-line/50 rounded-xl p-4 sm:p-6 mb-8 animate-fade-in-up" style="animation-delay: 0.05s;">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
            {{-- Recherche --}}
            <div class="lg:col-span-2">
                <label class="block text-xs font-semibold text-mist mb-1.5 uppercase tracking-wider">Recherche</label>
                <div class="relative">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-mist" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input type="text" name="q" value="{{ request('q') }}" placeholder="Titre, auteur..."
                        class="input-field pl-10">
                </div>
            </div>

            {{-- Genre --}}
            <div>
                <label class="block text-xs font-semibold text-mist mb-1.5 uppercase tracking-wider">Genre</label>
                <select name="genre" class="input-field cursor-pointer">
                    <option value="">Tous les genres</option>
                    @foreach($genres as $genre)
                        <option value="{{ $genre->slug }}" {{ request('genre') == $genre->slug ? 'selected' : '' }}>{{ $genre->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Type --}}
            <div>
                <label class="block text-xs font-semibold text-mist mb-1.5 uppercase tracking-wider">Type</label>
                <select name="type" class="input-field cursor-pointer">
                    <option value="">Tous les types</option>
                    <option value="manga" {{ request('type') == 'manga' ? 'selected' : '' }}>Manga</option>
                    <option value="manhwa" {{ request('type') == 'manhwa' ? 'selected' : '' }}>Manhwa</option>
                    <option value="manhua" {{ request('type') == 'manhua' ? 'selected' : '' }}>Manhua</option>
                </select>
            </div>

            {{-- Statut --}}
            <div>
                <label class="block text-xs font-semibold text-mist mb-1.5 uppercase tracking-wider">Statut</label>
                <select name="status" class="input-field cursor-pointer">
                    <option value="">Tous les statuts</option>
                    <option value="en_cours" {{ request('status') == 'en_cours' ? 'selected' : '' }}>En cours</option>
                    <option value="termine" {{ request('status') == 'termine' ? 'selected' : '' }}>Terminé</option>
                    <option value="pause" {{ request('status') == 'pause' ? 'selected' : '' }}>En pause</option>
                </select>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-3 mt-4 pt-4 border-t border-line/30">
            {{-- Tri --}}
            <div class="flex items-center gap-2">
                <label class="text-xs font-semibold text-mist uppercase tracking-wider">Trier</label>
                <select name="sort" class="input-field !w-auto !py-1.5 text-sm cursor-pointer">
                    <option value="latest" {{ request('sort', 'latest') == 'latest' ? 'selected' : '' }}>Plus récent</option>
                    <option value="popular" {{ request('sort') == 'popular' ? 'selected' : '' }}>Plus populaire</option>
                    <option value="title" {{ request('sort') == 'title' ? 'selected' : '' }}>Titre A-Z</option>
                    <option value="rating" {{ request('sort') == 'rating' ? 'selected' : '' }}>Meilleure note</option>
                </select>
            </div>

            <div class="flex items-center gap-2 ml-auto">
                @if(request()->hasAny(['q','genre','type','status','sort']))
                    <a href="{{ route('manga.index') }}" class="text-sm text-mist hover:text-rose transition flex items-center gap-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        Réinitialiser
                    </a>
                @endif
                <button type="submit" class="btn-primary !py-2 !px-6 !text-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                    Filtrer
                </button>
            </div>
        </div>
    </form>

    {{-- ═══ Grille ═══ --}}
    @if($mangas->count())
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4 mb-10">
            @foreach($mangas as $index => $manga)
            <a href="{{ route('manga.show', $manga->slug) }}" class="manga-card group animate-fade-in" style="animation-delay: {{ min($index * 0.03, 0.3) }}s;">
                <div class="relative overflow-hidden">
                    @if($manga->cover_image)
                        <img src="{{ Storage::url($manga->cover_image) }}" alt="{{ $manga->title }}" class="manga-cover" loading="lazy">
                    @else
                        <div class="cover-placeholder">
                            <span>{{ $manga->title }}</span>
                        </div>
                    @endif
                    <div class="manga-overlay"></div>

                    {{-- Badge type --}}
                    <div class="absolute top-2 left-2">
                        <span class="badge-type badge-{{ $manga->type }}">{{ ucfirst($manga->type) }}</span>
                    </div>

                    {{-- Badge statut --}}
                    <div class="absolute bottom-2 left-2">
                        <span class="badge-status badge-{{ $manga->status }}">
                            {{ ucfirst(str_replace('_', ' ', $manga->status)) }}
                        </span>
                    </div>

                    {{-- Rating --}}
                    @if($manga->average_rating > 0)
                    <div class="absolute top-2 right-2">
                        <span class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded-md bg-ink/70 backdrop-blur-sm text-amber text-[10px] font-bold">
                            ★ {{ $manga->average_rating }}
                        </span>
                    </div>
                    @endif
                </div>
                <div class="p-3">
                    <p class="text-sm font-semibold text-chalk truncate group-hover:text-violet transition">{{ $manga->title }}</p>
                    <div class="flex items-center justify-between mt-1">
                        <span class="text-xs text-mist">{{ $manga->chapters_count ?? $manga->chapters->count() }} ch.</span>
                        <span class="text-[10px] text-mist/60 flex items-center gap-0.5">
                            <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            {{ number_format($manga->views_count) }}
                        </span>
                    </div>
                </div>
            </a>
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

</x-layouts.public>