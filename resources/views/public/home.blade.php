@use('Illuminate\Support\Facades\Storage')
<x-layouts.public title="Hidden Scan — Lecture de mangas & manhwas en ligne">

    {{-- ═══ Hero Slider Cinématique ("The Shadow Cinema") ═══ --}}
    @if($featured->count())
    <style>
        .hero-swiper .swiper-slide {
            pointer-events: none !important;
            opacity: 0 !important;
            visibility: hidden !important;
            z-index: 1 !important;
            transition: opacity 0.4s ease, visibility 0.4s ease;
        }
        .hero-swiper .swiper-slide.swiper-slide-active {
            pointer-events: auto !important;
            opacity: 1 !important;
            visibility: visible !important;
            z-index: 10 !important;
        }
    </style>
    <section class="relative mb-12 animate-fade-in -mx-4 sm:-mx-6 mt-[-1.5rem] overflow-hidden">
        <div class="swiper hero-swiper h-[60vh] min-h-[460px] max-h-[640px] w-full overflow-hidden">
            <div class="swiper-wrapper">
                @foreach($featured as $manga)
                @php
                    $mangaUrl = route('manga.show', $manga->slug);
                    $firstChapter = $manga->chapters->first();
                    $isOfficial = !($manga->is_external ?? false);
                @endphp
                <div class="swiper-slide relative overflow-hidden" data-url="{{ $mangaUrl }}">
                    {{-- Backdrop Wallpaper avec Blur Atmosphérique --}}
                    <div class="absolute inset-0 z-0 select-none pointer-events-none">
                        @if($manga->banner_image)
                            <img src="{{ Storage::url($manga->banner_image) }}" alt="" class="w-full h-full object-cover opacity-35 scale-105 filter blur-[2px]">
                        @elseif($manga->cover_image)
                            <img src="{{ Storage::url($manga->cover_image) }}" alt="" class="w-full h-full object-cover opacity-25 scale-125 filter blur-lg">
                        @else
                            <div class="w-full h-full bg-gradient-to-r from-panel via-ink to-ink-deep"></div>
                        @endif
                        
                        {{-- Gradients de profondeur néon --}}
                        <div class="absolute inset-0 bg-gradient-to-t from-[#07080c] via-[#07080c]/60 to-transparent"></div>
                        <div class="absolute inset-0 bg-gradient-to-r from-[#07080c] via-[#07080c]/70 to-transparent"></div>
                        <div class="absolute -bottom-24 left-1/4 w-96 h-96 bg-crimson/10 rounded-full blur-[120px] pointer-events-none"></div>
                    </div>
                    
                    {{-- Contenu du Hero --}}
                    <div class="absolute inset-0 flex items-center pointer-events-none">
                        <div class="max-w-7xl mx-auto px-4 sm:px-8 lg:px-10 w-full flex gap-10 items-center pointer-events-none">
                            <div class="flex-1 max-w-2xl pointer-events-auto">
                                
                                {{-- Badges & Catégorie --}}
                                <div class="flex flex-wrap items-center gap-2 mb-3 sm:mb-4">
                                    @if($isOfficial)
                                        <span class="badge-official-hs">
                                            <span>👑</span>
                                            <span>PROJET OFFICIEL HIDDEN SCAN</span>
                                        </span>
                                    @else
                                        <span class="badge-type badge-{{ $manga->type->value }}">
                                            {{ $manga->type->getLabel() }}
                                        </span>
                                    @endif

                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-amber-500/20 text-amber-300 text-xs font-black backdrop-blur-md border border-amber-500/40 shadow-sm">
                                        ★ {{ $manga->average_rating > 0 ? number_format($manga->average_rating, 1) : '4.9' }}
                                    </span>

                                    <span class="hidden sm:inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-white/10 text-mist text-xs font-semibold backdrop-blur-md border border-white/10">
                                        🔥 Tendance n°1
                                    </span>
                                </div>

                                {{-- Titre du Manga --}}
                                <h1 class="font-display font-black text-3xl sm:text-5xl lg:text-6xl tracking-tight text-white leading-[1.08] drop-shadow-xl mb-3 sm:mb-4 line-clamp-2 sm:line-clamp-none">
                                    <a href="{{ $mangaUrl }}" class="hover:text-crimson transition-colors">
                                        {{ $manga->title }}
                                    </a>
                                </h1>

                                {{-- Synopsis condensé --}}
                                @if($manga->synopsis)
                                    <p class="text-mist text-xs sm:text-sm md:text-base mb-6 sm:mb-8 line-clamp-2 sm:line-clamp-3 leading-relaxed drop-shadow-md max-w-xl">
                                        {{ $manga->synopsis }}
                                    </p>
                                @endif

                                {{-- Actions Call-to-Action --}}
                                <div class="flex flex-wrap items-center gap-3">
                                    @if($firstChapter)
                                        <a href="{{ route('chapter.show', [$manga->slug, $firstChapter->slug]) }}" 
                                           class="btn-primary !py-3 !px-6 !text-sm sm:!text-base">
                                            <span>⚡ Lire le Ch. {{ $firstChapter->number }}</span>
                                        </a>
                                    @endif
                                    
                                    <a href="{{ $mangaUrl }}" class="btn-secondary !py-3 !px-5 !text-sm sm:!text-base">
                                        <span>📚 Fiche complète</span>
                                    </a>
                                </div>
                            </div>
                            
                            {{-- Couverture 3D Flottante (Desktop) --}}
                            <div class="hidden md:block flex-shrink-0 relative group perspective-1000 pointer-events-auto">
                                <a href="{{ $mangaUrl }}" class="block cursor-pointer relative">
                                    {{-- Lueur d'arrière-plan --}}
                                    <div class="absolute -inset-2 bg-gradient-to-r from-crimson/30 to-violet/30 rounded-2xl blur-xl opacity-75 group-hover:opacity-100 transition duration-500"></div>

                                    @if($manga->cover_image)
                                        <img src="{{ Storage::url($manga->cover_image) }}" alt="{{ $manga->title }}" 
                                             class="relative w-52 lg:w-64 aspect-[2/3] object-cover rounded-2xl border border-white/15 shadow-2xl transition-all duration-500 ease-out group-hover:scale-105 group-hover:border-crimson/60">
                                    @endif
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            
            {{-- Pagination Pilules --}}
            <div class="swiper-pagination !bottom-4 sm:!bottom-6"></div>
        </div>
    </section>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        new Swiper('.hero-swiper', {
            loop: true,
            effect: 'fade',
            fadeEffect: { crossFade: true },
            autoplay: {
                delay: 4500,
                disableOnInteraction: false,
            },
            pagination: {
                el: '.swiper-pagination',
                clickable: true,
            },
            touchStartPreventDefault: false,
            preventClicks: false,
            preventClicksPropagation: false,
            watchSlidesProgress: true,
        });
    });
    </script>
    @else
    {{-- Fallback si aucun manga en avant --}}
    <section class="relative mb-12 animate-fade-in">
        <div class="relative overflow-hidden rounded-3xl bg-panel border border-white/10 px-6 sm:px-10 py-12 sm:py-16 flex flex-col items-center text-center">
            <h1 class="font-display font-black text-4xl sm:text-5xl lg:text-6xl tracking-tight mb-4">
                HIDDEN<span class="text-crimson">SCAN</span>
            </h1>
            <p class="text-mist text-lg max-w-xl">
                Lis tes mangas, manhwas et manhuas en ligne gratuitement, sans pub invasive.
            </p>
        </div>
    </section>
    @endif

    {{-- ═══ Bandeau "Reprendre ma lecture" (Mobile-First & Bureau) ═══ --}}
    <div x-data="readingHistory()" x-init="init()" x-show="history.length > 0" x-cloak class="mb-10 animate-fade-in">
        <div class="bg-gradient-to-r from-panel via-panel-hi to-panel border border-white/10 rounded-2xl p-3.5 sm:p-4 shadow-xl flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 relative overflow-hidden">
            <div class="absolute left-0 top-0 bottom-0 w-1 bg-crimson"></div>
            
            <div class="flex items-center gap-3.5 min-w-0">
                <div class="w-10 h-10 rounded-xl bg-crimson/20 border border-crimson/30 flex items-center justify-center text-crimson flex-shrink-0 text-lg">
                    📖
                </div>
                <div class="min-w-0">
                    <div class="flex items-center gap-2">
                        <span class="text-[10px] font-black uppercase tracking-wider px-2 py-0.5 rounded-full bg-crimson/20 text-crimson border border-crimson/30">
                            Reprise
                        </span>
                        <h3 class="font-display font-bold text-sm sm:text-base text-white truncate" x-text="latest.mangaTitle"></h3>
                    </div>
                    <p class="text-xs text-mist mt-0.5 truncate">
                        Chapitre <span class="font-semibold text-white" x-text="latest.chapter"></span> (Progression : <span x-text="latest.percent + '%'"></span>)
                    </p>
                </div>
            </div>

            <a :href="'/chapitre/' + latest.mangaSlug + '/' + latest.slug" 
               class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-crimson hover:bg-crimson-glow text-white font-bold text-xs sm:text-sm shadow-md shadow-crimson/30 hover:scale-105 active:scale-95 transition-all flex-shrink-0">
                <span>Continuer la lecture ▶</span>
            </a>
        </div>
    </div>

    <script>
    function readingHistory() {
        return {
            history: [],
            get latest() {
                return this.history[0] || {};
            },
            init() {
                try {
                    const data = window.HiddenScan ? window.HiddenScan.getProgress() : {};
                    this.history = Object.values(data)
                        .filter(item => item && item.mangaTitle && item.slug)
                        .sort((a, b) => new Date(b.updatedAt) - new Date(a.updatedAt))
                        .slice(0, 4);
                } catch(e) {}
            }
        }
    }
    </script>

    {{-- ═══ Section Projets Officiels Hidden Scan (Exclusivité Team) ═══ --}}
    @php
        $officialMangas = $latest_updates->where('is_external', false)->take(4);
    @endphp
    @if($officialMangas->count())
    <section class="mb-12 animate-fade-in-up">
        <div class="flex items-center justify-between mb-5">
            <h2 class="section-title">
                <span>Projets Officiels Hidden Scan</span>
            </h2>
            <span class="text-xs font-bold text-crimson px-2.5 py-1 rounded-full bg-crimson/15 border border-crimson/30">
                👑 Nos Créations
            </span>
        </div>
        
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
            @foreach($officialMangas as $manga)
                <x-manga-card :manga="$manga" :show-chapters="true" />
            @endforeach
        </div>
    </section>
    @endif

    {{-- ═══ Dernières Sorties ═══ --}}
    @if($latest_updates->count())
    @php
        $mangaDataJson = $latest_updates->map(fn($m) => ['slug' => $m->slug, 'type' => $m->type->value, 'is_external' => (bool)$m->is_external])->values()->toJson();
    @endphp
    <section class="mb-14 animate-fade-in-up" 
             x-data="{ 
                 category: 'all',
                 items: {{ $mangaDataJson }},
                 favSlugs: [],
                 init() {
                     try {
                         const favs = window.HiddenScan ? window.HiddenScan.getFavorites() : [];
                         this.favSlugs = favs.map(f => f.slug);
                     } catch(e) {}
                 },
                 isVisible(type, slug, isExternal) {
                     if (this.category === 'all') return true;
                     if (this.category === 'official') return !isExternal;
                     if (this.category === 'favorites') return this.favSlugs.includes(slug);
                     return this.category === type;
                 },
                 get count() {
                     if (this.category === 'all') return this.items.length;
                     if (this.category === 'official') return this.items.filter(i => !i.is_external).length;
                     if (this.category === 'favorites') return this.items.filter(i => this.favSlugs.includes(i.slug)).length;
                     return this.items.filter(i => i.type === this.category).length;
                 }
             }">
        
        <div class="flex items-center justify-between mb-5">
            <h2 class="section-title">
                <span>Dernières Sorties</span>
            </h2>
            <a href="{{ route('manga.index', ['sort' => 'latest']) }}" class="text-sm font-semibold text-mist hover:text-crimson transition flex items-center gap-1">
                <span>Tout le catalogue</span>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </a>
        </div>

        {{-- Barre de filtres de catégories par Pills Néon --}}
        <div class="flex items-center gap-2 overflow-x-auto pb-2 scrollbar-none mb-6 -mx-4 px-4 sm:mx-0 sm:px-0">
            <button type="button" @click="category = 'all'" 
                    :class="category === 'all' ? 'bg-crimson text-white font-bold shadow-md shadow-crimson/30' : 'bg-panel text-mist hover:text-white hover:bg-panel-hi border border-white/5'" 
                    class="px-4 py-2 rounded-full text-xs sm:text-sm transition-all duration-200 cursor-pointer select-none whitespace-nowrap flex items-center gap-1.5">
                <span>Tout</span>
                <span class="text-[11px] px-1.5 py-0.2 rounded-full bg-white/20" x-text="items.length">{{ $latest_updates->count() }}</span>
            </button>

            <button type="button" @click="category = 'official'" 
                    :class="category === 'official' ? 'bg-crimson text-white font-bold shadow-md shadow-crimson/30' : 'bg-panel text-mist hover:text-white hover:bg-panel-hi border border-white/5'" 
                    class="px-4 py-2 rounded-full text-xs sm:text-sm transition-all duration-200 cursor-pointer select-none whitespace-nowrap flex items-center gap-1.5">
                <span>👑 Hidden Scan</span>
                <span class="text-[11px] px-1.5 py-0.2 rounded-full bg-white/20" x-text="items.filter(i => !i.is_external).length">0</span>
            </button>

            <button type="button" @click="category = 'manhwa'" 
                    :class="category === 'manhwa' ? 'bg-crimson text-white font-bold shadow-md shadow-crimson/30' : 'bg-panel text-mist hover:text-white hover:bg-panel-hi border border-white/5'" 
                    class="px-4 py-2 rounded-full text-xs sm:text-sm transition-all duration-200 cursor-pointer select-none whitespace-nowrap flex items-center gap-1.5">
                <span>Manhwa</span>
                <span class="text-[11px] px-1.5 py-0.2 rounded-full bg-white/20" x-text="items.filter(i => i.type === 'manhwa').length">0</span>
            </button>

            <button type="button" @click="category = 'manhua'" 
                    :class="category === 'manhua' ? 'bg-crimson text-white font-bold shadow-md shadow-crimson/30' : 'bg-panel text-mist hover:text-white hover:bg-panel-hi border border-white/5'" 
                    class="px-4 py-2 rounded-full text-xs sm:text-sm transition-all duration-200 cursor-pointer select-none whitespace-nowrap flex items-center gap-1.5">
                <span>Manhua</span>
                <span class="text-[11px] px-1.5 py-0.2 rounded-full bg-white/20" x-text="items.filter(i => i.type === 'manhua').length">0</span>
            </button>

            <button type="button" @click="category = 'manga'" 
                    :class="category === 'manga' ? 'bg-crimson text-white font-bold shadow-md shadow-crimson/30' : 'bg-panel text-mist hover:text-white hover:bg-panel-hi border border-white/5'" 
                    class="px-4 py-2 rounded-full text-xs sm:text-sm transition-all duration-200 cursor-pointer select-none whitespace-nowrap flex items-center gap-1.5">
                <span>Manga</span>
                <span class="text-[11px] px-1.5 py-0.2 rounded-full bg-white/20" x-text="items.filter(i => i.type === 'manga').length">0</span>
            </button>

            <button type="button" @click="category = 'favorites'" 
                    :class="category === 'favorites' ? 'bg-crimson text-white font-bold shadow-md shadow-crimson/30' : 'bg-panel text-mist hover:text-white hover:bg-panel-hi border border-white/5'" 
                    class="px-4 py-2 rounded-full text-xs sm:text-sm transition-all duration-200 cursor-pointer select-none whitespace-nowrap flex items-center gap-1.5">
                <span>❤️ Favoris</span>
                <span class="text-[11px] px-1.5 py-0.2 rounded-full bg-white/20" x-text="items.filter(i => favSlugs.includes(i.slug)).length">0</span>
            </button>
        </div>

        {{-- Grille Manga Responsive 2 à 6 colonnes --}}
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4" x-show="count > 0">
            @foreach($latest_updates as $manga)
                <div x-show="isVisible('{{ $manga->type->value }}', '{{ $manga->slug }}', {{ $manga->is_external ? 'true' : 'false' }})"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100">
                    <x-manga-card :manga="$manga" :show-chapters="true" />
                </div>
            @endforeach
        </div>

        {{-- État vide explicite --}}
        <div x-show="count === 0" x-cloak class="p-8 text-center bg-panel border border-white/5 rounded-3xl animate-fade-in my-4">
            <span class="text-3xl mb-2 block">📚</span>
            <p class="text-sm font-bold text-white">Aucune série trouvée pour ce filtre</p>
            <button type="button" @click="category = 'all'" class="mt-3 px-4 py-2 rounded-xl bg-panel-hi hover:bg-panel-hover text-xs font-semibold text-white border border-white/10 transition">
                Voir toutes les sorties
            </button>
        </div>
    </section>
    @endif

    {{-- ═══ Section Populaires & Podium ═══ --}}
    @if($popular->count())
    <section class="mb-14 animate-fade-in-up">
        <div class="flex items-center justify-between mb-6">
            <h2 class="section-title">
                <span>Top du Moment</span>
            </h2>
            <a href="{{ route('manga.index', ['sort' => 'popular']) }}" class="text-sm font-semibold text-mist hover:text-crimson transition flex items-center gap-1">
                <span>Classement complet</span>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </a>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
            @foreach($popular as $index => $manga)
                <x-manga-card :manga="$manga" :show-rank="$index < 3 ? $index + 1 : null" />
            @endforeach
        </div>
    </section>
    @endif

    {{-- ═══ Bannière Découverte "Surprends-moi 🎲" ═══ --}}
    <div class="mb-12 p-5 sm:p-6 rounded-3xl bg-gradient-to-r from-panel via-panel-hi to-panel border border-white/10 shadow-2xl flex flex-col sm:flex-row items-start sm:items-center justify-between gap-5 animate-fade-in relative overflow-hidden">
        <div class="absolute -right-16 -top-16 w-48 h-48 bg-crimson/10 rounded-full blur-3xl pointer-events-none"></div>

        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-crimson to-violet flex items-center justify-center text-2xl shadow-lg shadow-crimson/30 flex-shrink-0 animate-bounce">
                🎲
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <h3 class="font-display font-black text-lg text-white">À court d'idées de lecture ?</h3>
                    <span class="text-[10px] px-2 py-0.5 rounded-full bg-crimson/20 border border-crimson/30 text-crimson font-black uppercase">Hasard</span>
                </div>
                <p class="text-xs sm:text-sm text-mist mt-0.5">
                    Laisse l'algorithme Hidden Scan te dégoter une pépite selon tes genres favoris.
                </p>
            </div>
        </div>
        <a href="{{ route('manga.random') }}"
           onclick="if(window.HiddenScan && !{{ auth()->check() ? 'true' : 'false' }}){ const favs = window.HiddenScan.getFavorites().map(f => f.slug).join(','); if(favs) this.href = '{{ route('manga.random') }}?favs=' + encodeURIComponent(favs); }"
           class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-3 rounded-2xl bg-gradient-to-r from-crimson to-[#dc2626] hover:from-crimson-glow hover:to-crimson text-white font-bold text-sm shadow-xl shadow-crimson/30 hover:scale-105 active:scale-95 transition-all flex-shrink-0">
            <span>Surprends-moi 🎲</span>
        </a>
    </div>

</x-layouts.public>