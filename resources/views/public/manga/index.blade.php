@use('Illuminate\Support\Facades\Storage')
<x-layouts.public title="Catalogue — Hidden Scan">

    {{-- ═══ Header Catalogue ═══ --}}
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between mb-8 gap-4" data-aos="fade-in" data-aos-duration="400">
        <div>
            <h1 class="text-h1">Catalogue</h1>
            <p class="text-caption text-[var(--text-2)] mt-0.5">{{ $mangas->total() }} séries répertoriées</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('manga.random') }}"
               onclick="if(window.HiddenScan && !{{ auth()->check() ? 'true' : 'false' }}){ const favs = window.HiddenScan.getFavorites().map(f => f.slug).join(','); if(favs) this.href = '{{ route('manga.random') }}?favs=' + encodeURIComponent(favs); }"
               class="btn-secondary !text-caption">
                <span>🎲 Surprends-moi</span>
            </a>
        </div>
    </div>

    {{-- ═══ Layout Catalogue (Sidebar 240px + Grille 6 Colonnes) ═══ --}}
    <div class="flex flex-col lg:flex-row gap-8">
        
        {{-- Sidebar Filtres (240px) --}}
        <aside class="w-full lg:w-60 flex-shrink-0" x-data="{ mobileFiltersOpen: false }">
            {{-- Toggle Mobile --}}
            <div class="lg:hidden mb-4">
                <button type="button" @click="mobileFiltersOpen = !mobileFiltersOpen"
                        class="w-full flex items-center justify-between p-3 bg-[var(--bg-surface)] border border-[var(--border)] rounded-[4px] text-caption font-semibold text-[var(--text-1)]">
                    <span>Filtres de recherche</span>
                    <svg class="w-4 h-4 transition-transform" :class="mobileFiltersOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
            </div>

            <form method="GET" action="{{ route('manga.index') }}" 
                  class="bg-[var(--bg-surface)] border border-[var(--border)] rounded-[6px] p-4 space-y-4 sticky top-20 shadow-sm"
                  :class="mobileFiltersOpen ? 'block' : 'hidden lg:block'">
                
                <div class="flex items-center justify-between pb-3 border-b border-[var(--border)]">
                    <span class="text-label text-[var(--text-1)]">Filtres</span>
                    @if(request()->hasAny(['q','include_genres','exclude_genres','type','status','sort','genre']))
                        <a href="{{ route('manga.index') }}" class="text-[11px] text-[var(--accent)] hover:underline font-semibold">
                            Réinitialiser
                        </a>
                    @endif
                </div>

                {{-- Recherche Mots-Clés --}}
                <div>
                    <label class="text-label text-[var(--text-2)] block mb-1.5">Recherche</label>
                    <input type="text" name="q" value="{{ request('q') }}" placeholder="Titre, auteur..." class="input-field !py-1.5 !text-caption">
                </div>

                {{-- Type --}}
                <div>
                    <label class="text-label text-[var(--text-2)] block mb-1.5">Format</label>
                    <select name="type" class="input-field !py-1.5 !text-caption cursor-pointer">
                        <option value="">Tous les formats</option>
                        <option value="manga" {{ request('type') == 'manga' ? 'selected' : '' }}>Manga</option>
                        <option value="manhwa" {{ request('type') == 'manhwa' ? 'selected' : '' }}>Manhwa</option>
                        <option value="manhua" {{ request('type') == 'manhua' ? 'selected' : '' }}>Manhua</option>
                    </select>
                </div>

                {{-- Statut --}}
                <div>
                    <label class="text-label text-[var(--text-2)] block mb-1.5">Statut</label>
                    <select name="status" class="input-field !py-1.5 !text-caption cursor-pointer">
                        <option value="">Tous les statuts</option>
                        <option value="en_cours" {{ request('status') == 'en_cours' ? 'selected' : '' }}>En cours</option>
                        <option value="termine" {{ request('status') == 'termine' ? 'selected' : '' }}>Terminé</option>
                        <option value="pause" {{ request('status') == 'pause' ? 'selected' : '' }}>En pause</option>
                    </select>
                </div>

                {{-- Tri --}}
                <div>
                    <label class="text-label text-[var(--text-2)] block mb-1.5">Trier par</label>
                    <select name="sort" class="input-field !py-1.5 !text-caption cursor-pointer">
                        <option value="latest" {{ request('sort', 'latest') == 'latest' ? 'selected' : '' }}>Dernière sortie</option>
                        <option value="popular" {{ request('sort') == 'popular' ? 'selected' : '' }}>Plus populaire</option>
                        <option value="rating" {{ request('sort') == 'rating' ? 'selected' : '' }}>Meilleure note</option>
                        <option value="title" {{ request('sort') == 'title' ? 'selected' : '' }}>Titre alphabétique</option>
                    </select>
                </div>

                {{-- Genres List --}}
                <div>
                    <label class="text-label text-[var(--text-2)] block mb-1.5">Genres</label>
                    <div class="space-y-1 max-h-48 overflow-y-auto pr-1">
                        @foreach($genres as $genre)
                            <label class="flex items-center gap-2 py-0.5 text-caption text-[var(--text-2)] hover:text-[var(--text-1)] cursor-pointer">
                                <input type="checkbox" name="include_genres[]" value="{{ $genre->slug }}" 
                                       class="rounded-[2px] bg-[var(--bg-base)] border-[var(--border)] text-[var(--accent)] focus:ring-0"
                                       {{ in_array($genre->slug, (array)request('include_genres', [])) || request('genre') === $genre->slug ? 'checked' : '' }}>
                                <span class="truncate">{{ $genre->name }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <button type="submit" class="btn-primary w-full !py-2 !text-caption">
                    Appliquer
                </button>
            </form>
        </aside>

        {{-- Grille des Séries (6 Colonnes Desktop / 3 Tablette / 2 Mobile) --}}
        <div class="flex-1 min-w-0">
            
            {{-- Filtres Actifs --}}
            @if(request()->hasAny(['q', 'include_genres', 'exclude_genres', 'type', 'status', 'genre']))
                <div class="flex flex-wrap items-center gap-2 mb-4 p-2.5 rounded-[4px] bg-[var(--bg-surface)] border border-[var(--border)] text-caption">
                    <span class="text-[var(--text-3)]">Filtres :</span>
                    @if(request('q'))
                        <span class="badge-tag">« {{ request('q') }} »</span>
                    @endif
                    @if(request('type'))
                        <span class="badge-tag">{{ ucfirst(request('type')) }}</span>
                    @endif
                    @if(request('status'))
                        <span class="badge-tag">{{ ucfirst(request('status')) }}</span>
                    @endif
                    @if(request('genre'))
                        <span class="badge-tag">{{ request('genre') }}</span>
                    @endif
                    <a href="{{ route('manga.index') }}" class="text-[var(--accent)] hover:underline ml-auto font-semibold">Effacer</a>
                </div>
            @endif

            @if($mangas->count())
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4 mb-8">
                    @foreach($mangas as $index => $manga)
                        <div data-aos="fade-up" data-aos-duration="400" data-aos-easing="ease-out-cubic" data-aos-delay="{{ ($index % 6) * 50 }}">
                            <x-manga-card :manga="$manga" />
                        </div>
                    @endforeach
                </div>

                <div class="flex justify-center">
                    {{ $mangas->withQueryString()->links() }}
                </div>
            @else
                {{-- Empty State (Composant 10) Minimal icon only, no stock art --}}
                <div class="py-20 text-center bg-[var(--bg-surface)] border border-[var(--border)] rounded-[6px] p-8">
                    <div class="w-12 h-12 mx-auto mb-3 rounded-[4px] bg-[var(--bg-raised)] border border-[var(--border)] flex items-center justify-center text-xl text-[var(--text-3)]">
                        🔍
                    </div>
                    <h3 class="text-h3 font-semibold text-[var(--text-1)] mb-1">Aucune série trouvée</h3>
                    <p class="text-caption text-[var(--text-2)] mb-5 max-w-sm mx-auto">
                        Aucun résultat ne correspond à vos filtres actuels. Essayez de réinitialiser vos critères de recherche.
                    </p>
                    <a href="{{ route('manga.index') }}" class="btn-primary !text-caption">
                        Voir tout le catalogue
                    </a>
                </div>
            @endif

        </div>

    </div>

</x-layouts.public>