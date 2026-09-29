@use('Illuminate\Support\Facades\Storage')
<x-layouts.public title="Hidden Scan — Lecture de mangas en ligne">

    {{-- ═══ Hero Slider ═══ --}}
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
    <section class="relative mb-14 animate-fade-in -mx-4 sm:-mx-6 mt-[-1.5rem] overflow-hidden">
        <div class="swiper hero-swiper h-[55vh] min-h-[420px] max-h-[600px] w-full overflow-hidden">
            <div class="swiper-wrapper">
                @foreach($featured as $manga)
                @php
                    $mangaUrl = route('manga.show', $manga->slug);
                    $firstChapter = $manga->chapters->first();
                @endphp
                <div class="swiper-slide relative overflow-hidden" data-url="{{ $mangaUrl }}">
                    {{-- Background (Clickable direct navigation to manga) --}}
                    <a href="{{ $mangaUrl }}" class="absolute inset-0 z-0 cursor-pointer block" aria-label="{{ $manga->title }}">
                        @if($manga->banner_image)
                            <img src="{{ Storage::url($manga->banner_image) }}" alt="" class="absolute inset-0 w-full h-full object-cover opacity-40">
                        @elseif($manga->cover_image)
                            <img src="{{ Storage::url($manga->cover_image) }}" alt="" class="absolute inset-0 w-full h-full object-cover opacity-30 blur-sm scale-110">
                        @else
                            <div class="absolute inset-0 bg-gradient-to-r from-violet-deep/20 to-ink-deep/90"></div>
                        @endif
                        
                        {{-- Overlays --}}
                        <div class="absolute inset-0 bg-gradient-to-t from-ink via-ink/60 to-transparent"></div>
                        <div class="absolute inset-0 bg-gradient-to-r from-ink/90 via-ink/50 to-transparent"></div>
                    </a>
                    
                    {{-- Content --}}
                    <div class="absolute inset-0 flex items-center pointer-events-none">
                        <div class="max-w-7xl mx-auto px-4 sm:px-8 lg:px-10 w-full flex gap-8 items-center pointer-events-none">
                            <div class="flex-1 max-w-2xl pointer-events-auto">
                                <div class="flex items-start gap-4 mb-3 sm:mb-4">
                                    {{-- Cover Mobile (Permet de voir le manga et son illustration) --}}
                                    @if($manga->cover_image)
                                        <a href="{{ $mangaUrl }}" class="block md:hidden flex-shrink-0 relative group">
                                            <img src="{{ Storage::url($manga->cover_image) }}" alt="{{ $manga->title }}" 
                                                 class="w-20 sm:w-28 aspect-[2/3] object-cover rounded-xl border-2 border-line/70 shadow-2xl shadow-black/90">
                                        </a>
                                    @endif
                                    <div class="flex-1 min-w-0">
                                        <div class="flex flex-wrap items-center gap-2 mb-2 sm:mb-3">
                                            <span class="badge-type badge-{{ $manga->type->value }}">{{ $manga->type->getLabel() }}</span>
                                            <span class="inline-flex items-center gap-1 px-2 py-1 rounded-md bg-amber/20 text-amber text-xs font-bold backdrop-blur-md border border-amber/30">
                                                ★ {{ $manga->average_rating > 0 ? $manga->average_rating : 'N/A' }}
                                            </span>
                                        </div>
                                        <h2 class="font-display font-extrabold text-2xl sm:text-4xl lg:text-6xl tracking-tight text-chalk leading-tight drop-shadow-lg line-clamp-2 sm:line-clamp-none">
                                            <a href="{{ $mangaUrl }}" class="hover:text-violet transition-colors">
                                                {{ $manga->title }}
                                            </a>
                                        </h2>
                                    </div>
                                </div>

                                @if($manga->synopsis)
                                    <p class="text-mist text-xs sm:text-sm md:text-base mb-4 sm:mb-8 line-clamp-2 sm:line-clamp-3 leading-relaxed drop-shadow-md">
                                        {{ $manga->synopsis }}
                                    </p>
                                @endif
                                <div class="flex flex-wrap gap-2.5 sm:gap-3">
                                    <a href="{{ $mangaUrl }}" class="btn-primary !px-5 sm:!px-6 !py-2.5 sm:!py-3 !text-xs sm:!text-base shadow-[0_0_20px_rgba(155,123,255,0.4)]">
                                        Voir l'œuvre
                                    </a>
                                    @if($firstChapter)
                                    <a href="{{ route('chapter.show', [$manga->slug, $firstChapter->slug]) }}" class="btn-secondary !px-4 sm:!px-6 !py-2.5 sm:!py-3 !text-xs sm:!text-base bg-panel-hi/60 backdrop-blur-md">
                                        <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/></svg>
                                        Ch. {{ $firstChapter->number }}
                                    </a>
                                    @endif
                                </div>
                            </div>
                            
                            {{-- Image Cover flottante (Desktop only, Clickable) --}}
                            <div class="hidden md:block flex-shrink-0 relative group perspective-1000 pointer-events-auto">
                                <a href="{{ $mangaUrl }}" class="block cursor-pointer">
                                    @if($manga->cover_image)
                                        <img src="{{ Storage::url($manga->cover_image) }}" alt="{{ $manga->title }}" 
                                             class="w-48 lg:w-64 aspect-[2/3] object-cover rounded-xl border border-line shadow-2xl transition-transform duration-500 ease-out transform rotate-y-[-10deg] group-hover:rotate-y-0 group-hover:scale-105">
                                    @endif
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            
            {{-- Pagination --}}
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
                delay: 2000,
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
    {{-- Fallback si aucun manga en featured --}}
    <section class="relative mb-14 animate-fade-in">
        <div class="relative overflow-hidden rounded-2xl bg-panel border border-line/50 px-6 sm:px-10 py-12 sm:py-16 flex flex-col items-center text-center">
            <div class="absolute inset-0 opacity-30">
                <div class="absolute inset-0 bg-gradient-to-r from-ink via-transparent to-ink"></div>
            </div>
            <h1 class="font-display font-extrabold text-4xl sm:text-5xl lg:text-6xl tracking-tight mb-4 relative z-10">
                Hidden<span class="text-violet">Scan</span>
            </h1>
            <p class="text-mist text-lg sm:text-xl max-w-xl mb-8 relative z-10">
                Lis tes mangas, manhwas et manhuas en ligne. Gratuit, sans inscription.
            </p>
        </div>
    </section>
    @endif

    {{-- ═══ Surprise-moi Quick Discovery Banner ═══ --}}
    <div class="mb-12 p-4 sm:p-5 rounded-2xl bg-gradient-to-r from-red-950/40 via-panel to-amber-950/30 border border-red-500/25 shadow-lg shadow-black/40 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 animate-fade-in-up">
        <div class="flex items-center gap-3.5">
            <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-red-600 to-amber-600 flex items-center justify-center text-2xl shadow-lg shadow-red-600/30 flex-shrink-0 animate-pulse">
                🎲
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <h3 class="font-display font-bold text-base sm:text-lg text-chalk">Envie d'une découverte ?</h3>
                    <span class="text-[10px] px-2 py-0.5 rounded-full bg-red-600/20 border border-red-500/30 text-red-300 font-extrabold uppercase tracking-wide">Surprends-moi</span>
                </div>
                <p class="text-xs sm:text-sm text-mist mt-0.5">Laisse le hasard choisir un manga ciblé selon tes genres préférés et tes habitudes de lecture.</p>
            </div>
        </div>
        <a href="{{ route('manga.random') }}"
           onclick="if(window.HiddenScan && !{{ auth()->check() ? 'true' : 'false' }}){ const favs = window.HiddenScan.getFavorites().map(f => f.slug).join(','); if(favs) this.href = '{{ route('manga.random') }}?favs=' + encodeURIComponent(favs); }"
           class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-gradient-to-r from-red-600 to-red-700 hover:from-red-500 hover:to-red-600 text-white font-bold text-sm shadow-lg shadow-red-600/30 hover:scale-105 active:scale-95 transition-all flex-shrink-0">
            <span>Tenter ma chance 🎲</span>
        </a>
    </div>

    {{-- ═══ Continuer la lecture (Historique JS) ═══ --}}
    <div x-data="readingHistory()" x-init="init()" x-show="history.length > 0" x-cloak class="mb-14 animate-fade-in-up">
        <div class="flex items-center justify-between mb-6">
            <h2 class="section-title">
                <svg class="w-5 h-5 text-amber flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                Continuer la lecture
            </h2>
        </div>
        
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            <template x-for="item in history" :key="item.mangaSlug">
                <a :href="'/chapitre/' + item.mangaSlug + '/' + item.slug" class="group flex bg-panel border border-line/50 rounded-xl overflow-hidden hover:border-violet/50 hover:shadow-lg hover:shadow-violet/10 transition-all h-28">
                    <div class="w-20 sm:w-24 flex-shrink-0 bg-ink relative">
                        <img :src="item.mangaCover" :alt="item.mangaTitle" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" x-on:error="$el.style.display='none'">
                        <div class="absolute inset-0 flex items-center justify-center bg-ink" x-show="!item.mangaCover">
                            <span class="text-[10px] text-mist font-bold text-center px-1" x-text="item.mangaTitle.substring(0, 15)"></span>
                        </div>
                    </div>
                    <div class="p-3 flex flex-col justify-between flex-grow min-w-0">
                        <div>
                            <h3 class="font-semibold text-chalk text-sm truncate group-hover:text-violet transition" x-text="item.mangaTitle"></h3>
                            <p class="text-xs text-mist mt-1 truncate">Chapitre <span x-text="item.chapter"></span> (Page <span x-text="item.page"></span>)</p>
                        </div>
                        <div class="mt-2">
                            <div class="flex justify-between text-[10px] text-mist/70 mb-1">
                                <span>Progression</span>
                                <span x-text="item.percent + '%'"></span>
                            </div>
                            <div class="h-1.5 w-full bg-ink-deep rounded-full overflow-hidden">
                                <div class="h-full bg-violet rounded-full" :style="'width: ' + item.percent + '%'"></div>
                            </div>
                        </div>
                    </div>
                </a>
            </template>
        </div>
    </div>

    <script>
    function readingHistory() {
        return {
            history: [],
            init() {
                try {
                    const data = window.HiddenScan.getProgress();
                    // Convert object to array and sort by updatedAt desc
                    this.history = Object.values(data)
                        .filter(item => item && item.mangaTitle && item.slug)
                        .sort((a, b) => new Date(b.updatedAt) - new Date(a.updatedAt))
                        .slice(0, 4); // Show only top 4
                } catch(e) {
                    console.error("Error loading reading history", e);
                }
            }
        }
    }
    </script>

    {{-- ═══ Dernières sorties ═══ --}}
    @if($latest_updates->count())
    @php
        $mangaDataJson = $latest_updates->map(fn($m) => ['slug' => $m->slug, 'type' => $m->type->value])->values()->toJson();
    @endphp
    <section class="mb-14 animate-fade-in-up" style="animation-delay: 0.1s;" 
             x-data="{ 
                 category: 'all',
                 items: {{ $mangaDataJson }},
                 favSlugs: [],
                 init() {
                     try {
                         const favs = window.HiddenScan.getFavorites();
                         this.favSlugs = favs.map(f => f.slug);
                     } catch(e) {}
                 },
                 isVisible(type, slug) {
                     if (this.category === 'all') return true;
                     if (this.category === 'favorites') return this.favSlugs.includes(slug);
                     return this.category === type;
                 },
                 get count() {
                     if (this.category === 'all') return this.items.length;
                     if (this.category === 'favorites') return this.items.filter(i => this.favSlugs.includes(i.slug)).length;
                     return this.items.filter(i => i.type === this.category).length;
                 },
                 get emptyMessage() {
                     if (this.category === 'manhwa') return 'Aucun manhwa trouvé';
                     if (this.category === 'manhua') return 'Aucun manhua trouvé';
                     if (this.category === 'manga') return 'Aucun manga trouvé';
                     if (this.category === 'favorites') return 'Aucun favori trouvé';
                     return 'Aucune œuvre trouvée';
                 }
             }">
        <div class="flex items-center justify-between mb-5">
            <h2 class="section-title">
                <svg class="w-5 h-5 text-mint flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                Sorties récentes
            </h2>
            <a href="{{ route('manga.index', ['sort' => 'latest']) }}" class="text-sm text-mist hover:text-white transition flex items-center gap-1">
                Tout voir
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </a>
        </div>

        {{-- Category Filter Bar (Seamless & Borderless, Mobile-friendly Edge-to-Edge) --}}
        <div class="flex items-center gap-2 overflow-x-auto pb-2 scrollbar-none mb-7 -mx-4 px-4 sm:mx-0 sm:px-0">
            <button type="button" @click="category = 'all'" 
                    :class="category === 'all' ? 'bg-[#1e2029] text-white font-semibold' : 'text-[#7d849a] hover:text-white hover:bg-[#1e2029]/50'" 
                    class="px-4 py-2 rounded-full text-sm transition-colors duration-200 cursor-pointer select-none whitespace-nowrap flex-shrink-0 flex items-center gap-1.5">
                <span>Tout</span>
                <span class="text-[11px] px-1.5 py-0.2 rounded-full bg-white/10 text-mist" x-text="items.length">{{ $latest_updates->count() }}</span>
            </button>
            <button type="button" @click="category = 'manhwa'" 
                    :class="category === 'manhwa' ? 'bg-[#1e2029] text-white font-semibold' : 'text-[#7d849a] hover:text-white hover:bg-[#1e2029]/50'" 
                    class="px-4 py-2 rounded-full text-sm transition-colors duration-200 cursor-pointer select-none whitespace-nowrap flex-shrink-0 flex items-center gap-1.5">
                <span>Manhwa</span>
                <span class="text-[11px] px-1.5 py-0.2 rounded-full bg-white/10 text-mist" x-text="items.filter(i => i.type === 'manhwa').length">{{ $latest_updates->filter(fn($m) => $m->type?->value === 'manhwa')->count() }}</span>
            </button>
            <button type="button" @click="category = 'manhua'" 
                    :class="category === 'manhua' ? 'bg-[#1e2029] text-white font-semibold' : 'text-[#7d849a] hover:text-white hover:bg-[#1e2029]/50'" 
                    class="px-4 py-2 rounded-full text-sm transition-colors duration-200 cursor-pointer select-none whitespace-nowrap flex-shrink-0 flex items-center gap-1.5">
                <span>Manhua</span>
                <span class="text-[11px] px-1.5 py-0.2 rounded-full bg-white/10 text-mist" x-text="items.filter(i => i.type === 'manhua').length">{{ $latest_updates->filter(fn($m) => $m->type?->value === 'manhua')->count() }}</span>
            </button>
            <button type="button" @click="category = 'manga'" 
                    :class="category === 'manga' ? 'bg-[#1e2029] text-white font-semibold' : 'text-[#7d849a] hover:text-white hover:bg-[#1e2029]/50'" 
                    class="px-4 py-2 rounded-full text-sm transition-colors duration-200 cursor-pointer select-none whitespace-nowrap flex-shrink-0 flex items-center gap-1.5">
                <span>Manga</span>
                <span class="text-[11px] px-1.5 py-0.2 rounded-full bg-white/10 text-mist" x-text="items.filter(i => i.type === 'manga').length">{{ $latest_updates->filter(fn($m) => $m->type?->value === 'manga')->count() }}</span>
            </button>
            <button type="button" @click="category = 'favorites'" 
                    :class="category === 'favorites' ? 'bg-[#1e2029] text-white font-semibold' : 'text-[#7d849a] hover:text-white hover:bg-[#1e2029]/50'" 
                    class="px-4 py-2 rounded-full text-sm transition-colors duration-200 cursor-pointer select-none whitespace-nowrap flex-shrink-0 flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5 text-rose" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clip-rule="evenodd"/></svg>
                <span>Favoris</span>
                <span class="text-[11px] px-1.5 py-0.2 rounded-full bg-rose-500/20 text-rose-300" x-text="items.filter(i => favSlugs.includes(i.slug)).length">0</span>
            </button>
        </div>

        {{-- Manga Grid --}}
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4" x-show="count > 0">
            @foreach($latest_updates as $index => $manga)
                <div x-show="isVisible('{{ $manga->type->value }}', '{{ $manga->slug }}')"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100"
                     class="animate-fade-in stagger-{{ min($index + 1, 6) }}">
                    <x-manga-card :manga="$manga" :show-chapters="true" />
                </div>
            @endforeach
        </div>

        {{-- Message d'état vide explicite si aucun résultat pour le filtre sélectionné --}}
        <div x-show="count === 0" x-cloak class="p-8 text-center bg-panel/70 border border-line/50 rounded-2xl animate-fade-in my-4">
            <div class="w-12 h-12 mx-auto mb-3 rounded-2xl bg-panel-hi flex items-center justify-center text-xl shadow-inner">
                <span x-show="category === 'favorites'">💖</span>
                <span x-show="category !== 'favorites'">📚</span>
            </div>
            <p class="text-sm font-bold text-chalk" x-text="emptyMessage"></p>
            <p class="text-xs text-mist mt-1 max-w-md mx-auto" x-show="category === 'favorites'">
                Vous n'avez pas encore de série en favoris parmi les sorties récentes. Cliquez sur le cœur ❤️ d'un manga pour l'épingler ici !
            </p>
            <p class="text-xs text-mist mt-1 max-w-md mx-auto" x-show="category !== 'favorites'">
                Aucune œuvre de cette catégorie n'a eu de nouveau chapitre récemment publié.
            </p>
            <div class="mt-4 flex items-center justify-center gap-3">
                <button type="button" @click="category = 'all'" class="px-3.5 py-1.5 rounded-lg bg-panel-hi hover:bg-panel-hover text-xs font-semibold text-chalk border border-line transition">
                    Voir toutes les sorties
                </button>
                <a href="{{ route('manga.index') }}" class="px-3.5 py-1.5 rounded-lg bg-violet/20 hover:bg-violet/30 text-xs font-semibold text-violet-glow border border-violet/30 transition">
                    Tout le catalogue →
                </a>
            </div>
        </div>
    </section>
    @endif

    {{-- ═══ Populaires ═══ --}}
    @if($popular->count())
    <section class="mb-14 animate-fade-in-up" style="animation-delay: 0.2s;">
        <div class="flex items-center justify-between mb-6">
            <h2 class="section-title">
                <svg class="w-5 h-5 text-amber flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.879 16.121A3 3 0 1012.015 11L11 14H9c0 .768.293 1.536.879 2.121z"/></svg>
                Populaires
            </h2>
            <a href="{{ route('manga.index', ['sort' => 'popular']) }}" class="text-sm text-violet hover:text-violet-glow transition flex items-center gap-1">
                Tout voir
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </a>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
            @foreach($popular as $index => $manga)
                <div class="animate-fade-in stagger-{{ min($index + 1, 6) }}">
                    <x-manga-card :manga="$manga" :show-rank="$index < 3 ? $index + 1 : null" />
                </div>
            @endforeach
        </div>
    </section>
    @endif

    {{-- ═══ Mise en avant (featured) ═══ --}}
    @if($featured->count())
    <section class="mb-14 animate-fade-in-up" style="animation-delay: 0.3s;">
        <h2 class="section-title mb-6">
            <svg class="w-5 h-5 text-violet flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
            Recommandés
        </h2>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
            @foreach($featured as $index => $manga)
                <div class="animate-fade-in stagger-{{ min($index + 1, 6) }}">
                    <x-manga-card :manga="$manga" />
                </div>
            @endforeach
        </div>
    </section>
    @endif

</x-layouts.public>