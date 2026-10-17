@use('Illuminate\Support\Facades\Storage')
<x-layouts.public :container="false" title="Hidden Scan — Plateforme de lecture de mangas et manhwas">

    {{-- ═══ 1. HERO SECTION CINÉMATIQUE PLEINE LARGEUR (ANIVERSE STYLE) ═══ --}}
    @if($featured->count())
    @php
        $firstManga = $featured->first();
    @endphp
    <section class="hero" 
             style="--hero-img: url('{{ $firstManga->banner_url ?? $firstManga->cover_url }}')"
             x-data="{ 
                 activeIndex: 0,
                 slides: [
                     @foreach($featured as $f)
                         '{{ $f->banner_url ?? $f->cover_url }}',
                     @endforeach
                 ]
             }"
             :style="'--hero-img: url(\'' + slides[activeIndex] + '\')'">
        <div class="hero__overlay"></div>

        @foreach($featured as $index => $manga)
        <div class="hero__content" 
             x-show="activeIndex === {{ $index }}"
             x-cloak
             style="{{ $index === 0 ? '' : 'display: none;' }}">
            <div class="hero__meta">
                <span class="hero__type">{{ strtoupper($manga->type?->value ?? (string)$manga->type) }}</span>
                <span class="hero__dot">·</span>
                <span class="hero__status">{{ strtoupper($manga->status?->value ?? (string)$manga->status) }}</span>
            </div>
            <h1 class="hero__title">{{ $manga->title }}</h1>
            <p class="hero__synopsis">{{ Str::limit($manga->synopsis, 160) }}</p>
            <div class="hero__tags">
                @foreach($manga->genres->take(3) as $genre)
                    <span class="hero__tag">{{ $genre->name }}</span>
                @endforeach
            </div>
            <div class="hero__actions">
                <a href="{{ route('manga.read.first', $manga) }}" class="btn btn--primary">
                    ▶ Lire maintenant
                </a>
                <a href="{{ route('manga.show', $manga) }}" class="btn btn--ghost">
                    Fiche détaillée
                </a>
            </div>
        </div>
        @endforeach

        @if($featured->count() > 1)
        <div class="hero__indicators">
            @foreach($featured as $index => $manga)
                <button type="button" 
                        class="hero__dot-nav" 
                        :class="{ 'active': activeIndex === {{ $index }} }" 
                        @click="activeIndex = {{ $index }}"
                        aria-label="Slide {{ $index + 1 }}">
                </button>
            @endforeach
        </div>
        @endif
    </section>
    @endif

    {{-- ═══ CONTENEUR DES SECTIONS PRINCIPALES (1360px BREATHE) ═══ --}}
    <div class="max-w-[1360px] w-full mx-auto px-4 sm:px-6 lg:px-8 space-y-14 pb-16">

    {{-- ═══ 2. BANDEAU DE REPRISE DE LECTURE (Local & Sync) ═══ --}}
    <div x-data="readingHistory()" x-init="init()" x-show="history.length > 0" x-cloak class="mb-12" data-aos="fade-in" data-aos-duration="400">
        <div class="bg-[var(--bg-surface)] border border-[var(--border)] rounded-[6px] p-4 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div class="flex items-center gap-3 min-w-0">
                <div class="w-9 h-9 rounded-[4px] bg-[var(--bg-raised)] border border-[var(--border)] flex items-center justify-center text-base text-[var(--accent)] flex-shrink-0">
                    📖
                </div>
                <div class="min-w-0">
                    <div class="flex items-center gap-2">
                        <span class="text-label text-[var(--accent)] font-bold">Reprendre la lecture</span>
                        <span class="text-[var(--border)]">•</span>
                        <h3 class="font-heading font-semibold text-[15px] text-[var(--text-1)] truncate" x-text="latest.mangaTitle"></h3>
                    </div>
                    <p class="text-caption text-[var(--text-2)] mt-0.5">
                        Chapitre <span class="text-[var(--text-1)] font-medium" x-text="latest.chapter"></span>
                        <template x-if="latest.percent">
                            <span class="text-[var(--text-3)]" x-text="' (' + latest.percent + '%)'"></span>
                        </template>
                    </p>
                </div>
            </div>

            <a :href="'/chapitre/' + latest.mangaSlug + '/' + latest.slug" class="btn-primary !py-2 !px-4 !text-caption flex-shrink-0 w-full sm:w-auto text-center">
                <span>Continuer la lecture</span>
                <span>→</span>
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
                        .slice(0, 3);
                } catch(e) {}
            }
        }
    }
    </script>

    {{-- ═══ 3. TOP TENDANCES & POPULAIRES (Grille 6 Colonnes) ═══ --}}
    @if(isset($popular) && $popular->count())
    <section class="mb-14" data-aos="fade-in" data-aos-duration="600">
        <div class="flex items-center justify-between mb-4">
            <h2 class="section-title">Populaire en ce moment</h2>
            <a href="{{ route('manga.index', ['sort' => 'popular']) }}" class="text-caption text-[var(--text-2)] hover:text-[var(--text-1)] transition flex items-center gap-1 font-semibold">
                <span>Voir tout</span>
                <span>→</span>
            </a>
        </div>

        {{-- Grille 6 Colonnes Desktop / 3 Tablette / 2 Mobile --}}
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
            @foreach($popular->take(6) as $index => $manga)
                <div data-aos="fade-up" data-aos-duration="400" data-aos-easing="ease-out-cubic" data-aos-delay="{{ ($index % 6) * 50 }}">
                    <x-manga-card :manga="$manga" :show-rank="$index + 1" />
                </div>
            @endforeach
        </div>
    </section>

    <div class="section-separator"></div>
    @endif

    {{-- ═══ 4. DERNIÈRES SORTIES (Grille 6 Colonnes & Filtres par Pills) ═══ --}}
    @if($latest_updates->count())
    <section class="mb-16" 
             x-data="{ 
                 selectedTab: 'all',
                 items: {{ $latest_updates->map(fn($m) => ['slug' => $m->slug, 'type' => $m->type->value, 'is_external' => (bool)$m->is_external])->values()->toJson() }},
                 favSlugs: [],
                 init() {
                     try {
                         const favs = window.HiddenScan ? window.HiddenScan.getFavorites() : [];
                         this.favSlugs = favs.map(f => f.slug);
                     } catch(e) {}
                 },
                 isVisible(type, slug, isExternal) {
                     if (this.selectedTab === 'all') return true;
                     if (this.selectedTab === 'official') return !isExternal;
                     if (this.selectedTab === 'favorites') return this.favSlugs.includes(slug);
                     return this.selectedTab === type;
                 }
             }">
        
        <div class="flex items-center justify-between mb-4">
            <h2 class="section-title">Dernières Sorties</h2>
            <a href="{{ route('manga.index', ['sort' => 'latest']) }}" class="text-caption text-[var(--text-2)] hover:text-[var(--text-1)] transition flex items-center gap-1 font-semibold">
                <span>Catalogue complet</span>
                <span>→</span>
            </a>
        </div>

        {{-- Barre de Pills de Genre / Types (Horizontally Scrollable, No Scrollbar) --}}
        <div class="flex items-center gap-2 overflow-x-auto no-scrollbar mb-6 pb-1">
            <button type="button" @click="selectedTab = 'all'" 
                    class="genre-pill" :class="selectedTab === 'all' ? 'active' : ''">
                Tout
            </button>
            <button type="button" @click="selectedTab = 'official'" 
                    class="genre-pill" :class="selectedTab === 'official' ? 'active' : ''">
                Projets Officiels
            </button>
            <button type="button" @click="selectedTab = 'manhwa'" 
                    class="genre-pill" :class="selectedTab === 'manhwa' ? 'active' : ''">
                Manhwa
            </button>
            <button type="button" @click="selectedTab = 'manhua'" 
                    class="genre-pill" :class="selectedTab === 'manhua' ? 'active' : ''">
                Manhua
            </button>
            <button type="button" @click="selectedTab = 'manga'" 
                    class="genre-pill" :class="selectedTab === 'manga' ? 'active' : ''">
                Manga
            </button>
            <button type="button" @click="selectedTab = 'favorites'" 
                    class="genre-pill" :class="selectedTab === 'favorites' ? 'active' : ''">
                Mes Favoris
            </button>
        </div>

        {{-- Grille 6 Colonnes Stricte --}}
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
            @foreach($latest_updates as $index => $manga)
                <div x-show="isVisible('{{ $manga->type->value }}', '{{ $manga->slug }}', {{ $manga->is_external ? 'true' : 'false' }})"
                     data-aos="fade-up" 
                     data-aos-duration="400" 
                     data-aos-easing="ease-out-cubic" 
                     data-aos-delay="{{ ($index % 6) * 50 }}">
                    <x-manga-card :manga="$manga" :show-chapters="true" />
                </div>
            @endforeach
        </div>
    </section>

    <div class="section-separator"></div>
    @endif

    {{-- ═══ 5. PLANNING DE LA SEMAINE (Composant Hebdomadaire Propre) ═══ --}}
    @if(isset($weeklySchedule) && count($weeklySchedule))
    <section class="mb-14" x-data="{ activeDay: '{{ $todayKey ?? 'lundi' }}' }" data-aos="fade-in" data-aos-duration="600">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="section-title">Planning des Sorties</h2>
                <p class="text-caption text-[var(--text-2)] mt-0.5">Le programme prévisionnel de publication de nos équipes</p>
            </div>
            <a href="{{ route('planning') }}" class="text-caption text-[var(--text-2)] hover:text-[var(--text-1)] transition flex items-center gap-1 font-semibold">
                <span>Semaine complète</span>
                <span>→</span>
            </a>
        </div>

        {{-- Sélecteur de jour scrollable --}}
        <div class="flex items-center gap-2 overflow-x-auto no-scrollbar mb-4 pb-1">
            @foreach($weeklySchedule as $dayKey => $dayData)
                <button type="button"
                        @click="activeDay = '{{ $dayKey }}'"
                        class="genre-pill"
                        :class="activeDay === '{{ $dayKey }}' ? 'active' : ''">
                    <span>{{ $dayData['label'] }}</span>
                    @if(!empty($dayData['is_today']))
                        <span class="text-[9px] px-1 py-0.2 rounded-[2px] bg-white/20 font-bold uppercase">Auj.</span>
                    @endif
                </button>
            @endforeach
        </div>

        {{-- Grille des slots du jour actif --}}
        @foreach($weeklySchedule as $dayKey => $dayData)
            <div x-show="activeDay === '{{ $dayKey }}'" x-cloak class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                @forelse(($dayData['items'] ?? []) as $slot)
                    @php
                        $manga = $slot['manga'] ?? null;
                        $latestCh = $slot['latest_chapter'] ?? null;
                    @endphp
                    @if($manga)
                        <div class="p-3 bg-[var(--bg-surface)] border border-[var(--border)] rounded-[6px] hover:border-[var(--text-3)] transition flex items-center gap-3">
                            <a href="{{ route('manga.show', $manga->slug) }}" class="w-12 h-16 rounded-[4px] bg-[var(--bg-raised)] overflow-hidden flex-shrink-0 block">
                                @if($manga->cover_image)
                                    <img src="{{ Storage::url($manga->cover_image) }}" alt="{{ $manga->title }}" class="w-full h-full object-cover">
                                @endif
                            </a>
                            <div class="flex-1 min-w-0">
                                <div class="text-[11px] text-[var(--text-3)] uppercase font-semibold mb-0.5">{{ $slot['time'] ?? '18h00' }}</div>
                                <h3 class="font-heading font-semibold text-[14px] text-[var(--text-1)] truncate">
                                    <a href="{{ route('manga.show', $manga->slug) }}" class="hover:text-[var(--accent)] transition">
                                        {{ $manga->title }}
                                    </a>
                                </h3>
                                @if($latestCh)
                                    <div class="text-caption text-[var(--text-2)] mt-1">
                                        <a href="{{ route('chapter.show', [$manga->slug, $latestCh->slug]) }}" class="hover:text-[var(--accent)] transition">
                                            Ch. {{ $latestCh->number }} disponible
                                        </a>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif
                @empty
                    <div class="col-span-full py-8 text-center bg-[var(--bg-surface)] border border-[var(--border)] rounded-[6px] text-caption text-[var(--text-3)]">
                        Aucune sortie prévue pour ce jour.
                    </div>
                @endforelse
            </div>
        @endforeach
    </section>
    @endif

    {{-- ═══ 6. DISCORD COMMUNAUTÉ ═══ --}}
    <section class="mb-14" data-aos="fade-in" data-aos-duration="600">
        <div class="bg-[var(--bg-surface)] border border-[var(--border)] rounded-[6px] p-6 sm:p-8 flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
            <div class="max-w-xl">
                <div class="inline-flex items-center gap-2 px-2.5 py-1 rounded-[4px] bg-[var(--bg-raised)] border border-[var(--border)] text-label text-[var(--accent)] mb-3">
                    <span>COMMUNAUTÉ OFFICIELLE</span>
                </div>
                <h2 class="font-heading font-semibold text-[24px] text-[var(--text-1)] mb-2">Rejoins la communauté</h2>
                <p class="text-body text-[var(--text-2)] text-[14px] leading-relaxed">
                    Échange avec les traducteurs, participe aux votes des prochaines séries, sois notifié en direct de la sortie de tes chapitres et rejoins les rangs de notre dojo martial.
                </p>
            </div>
            <div class="flex-shrink-0 w-full md:w-auto">
                <a href="https://discord.gg/sMUeFSks4" target="_blank" rel="noopener noreferrer" class="btn-primary inline-flex items-center justify-center gap-2 w-full md:w-auto text-sm py-2.5 px-6">
                    <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24">
                        <path d="M20.317 4.37a19.791 19.791 0 0 0-4.885-1.515.074.074 0 0 0-.079.037c-.21.375-.444.864-.608 1.25a18.27 18.27 0 0 0-5.487 0 12.64 12.64 0 0 0-.617-1.25.077.077 0 0 0-.079-.037A19.736 19.736 0 0 0 3.677 4.37a.07.07 0 0 0-.032.027C.533 9.046-.32 13.58.099 18.057a.082.082 0 0 0 .031.057 19.9 19.9 0 0 0 5.993 3.03.078.078 0 0 0 .084-.028c.462-.63.874-1.295 1.226-1.994.021-.041.001-.09-.041-.106a13.107 13.107 0 0 1-1.872-.892.077.077 0 0 1-.008-.128 10.2 10.2 0 0 0 .372-.292.074.074 0 0 1 .077-.01c3.929 1.793 8.18 1.793 12.061 0a.074.074 0 0 1 .078.01c.12.098.246.198.373.292a.077.077 0 0 1-.006.127 12.299 12.299 0 0 1-1.873.894.077.077 0 0 0-.041.107c.36.698.772 1.362 1.225 1.993a.076.076 0 0 0 .084.028 19.839 19.839 0 0 0 6.002-3.03.077.077 0 0 0 .032-.054c.5-5.177-.838-9.674-3.549-13.66a.061.061 0 0 0-.031-.028zM8.02 15.33c-1.183 0-2.157-1.085-2.157-2.419 0-1.333.956-2.419 2.157-2.419 1.21 0 2.176 1.096 2.157 2.42 0 1.333-.956 2.418-2.157 2.418zm7.975 0c-1.183 0-2.157-1.085-2.157-2.419 0-1.333.955-2.419 2.157-2.419 1.21 0 2.176 1.096 2.157 2.42 0 1.333-.946 2.418-2.157 2.418z"/>
                    </svg>
                    <span>Rejoindre notre Discord</span>
                </a>
            </div>
        </div>
    </section>

    </div> {{-- Fin du conteneur 1360px --}}

</x-layouts.public>