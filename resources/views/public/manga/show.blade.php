@use('Illuminate\Support\Facades\Storage')
<x-layouts.public :title="$manga->title . ' — Hidden Scan'">

    {{-- ═══ Bannière ═══ --}}
    <div class="relative -mx-4 sm:-mx-6 mb-8 animate-fade-in">
        @if($manga->banner_image)
            <div class="h-56 sm:h-72 overflow-hidden">
                <img src="{{ Storage::url($manga->banner_image) }}" alt="" class="w-full h-full object-cover opacity-30">
                <div class="absolute inset-0 bg-gradient-to-t from-ink via-ink/80 to-transparent"></div>
            </div>
        @else
            <div class="h-40 sm:h-56 bg-gradient-to-b from-panel to-ink relative">
                <div class="absolute inset-0 opacity-20" style="background-image: radial-gradient(circle, rgba(155,123,255,0.2) 1px, transparent 1px); background-size: 16px 16px;"></div>
                <div class="absolute inset-0 bg-gradient-to-t from-ink to-transparent"></div>
            </div>
        @endif

        {{-- Info overlay --}}
        <div class="absolute bottom-0 left-0 right-0 px-4 sm:px-6 pb-0">
            <div class="max-w-7xl mx-auto flex gap-5 sm:gap-6 items-end">
                {{-- Cover --}}
                <div class="flex-shrink-0 -mb-12 sm:-mb-16">
                    @if($manga->cover_image)
                        <img src="{{ Storage::url($manga->cover_image) }}" alt="{{ $manga->title }}"
                             class="w-28 sm:w-36 rounded-xl border-2 border-line shadow-2xl shadow-ink/80 aspect-[2/3] object-cover">
                    @else
                        <div class="w-28 sm:w-36 rounded-xl border-2 border-line bg-panel-hi aspect-[2/3] flex items-center justify-center">
                            <span class="text-mist text-xs text-center px-3">{{ $manga->title }}</span>
                        </div>
                    @endif
                </div>

                {{-- Titre et infos rapides --}}
                <div class="pb-4 min-w-0">
                    <div class="flex flex-wrap items-center gap-2 mb-2">
                        <span class="badge-type badge-{{ $manga->type->value }}">{{ $manga->type->getLabel() }}</span>
                        <span class="badge-status badge-{{ $manga->status->value }}">{{ $manga->status->getLabel() }}</span>
                    </div>
                    <h1 class="font-display font-extrabold text-2xl sm:text-3xl lg:text-4xl tracking-tight text-chalk leading-tight truncate">{{ $manga->title }}</h1>
                    @if($manga->authors->count() || $manga->artists->count())
                        <p class="text-mist text-sm mt-1">
                            @if($manga->authors->count())
                                {{ $manga->authors->pluck('name')->join(', ') }}
                            @endif
                            @if($manga->artists->count() && $manga->artists->pluck('id')->diff($manga->authors->pluck('id'))->isNotEmpty())
                                · {{ $manga->artists->pluck('name')->diff($manga->authors->pluck('name'))->join(', ') }}
                            @endif
                        </p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Espace pour la cover qui dépasse --}}
    <div class="h-12 sm:h-16"></div>

    {{-- ═══ Surprise Notification Banner ═══ --}}
    @if(session('surprise_info'))
        <div x-data="{ show: true }" x-show="show" x-transition.opacity.duration.300ms class="mb-6 p-4 rounded-2xl bg-gradient-to-r from-red-950/60 via-ink-card to-amber-950/40 border border-red-500/30 backdrop-blur-md shadow-xl shadow-red-950/20 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 animate-fade-in-up">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-red-600/20 border border-red-500/30 flex items-center justify-center text-xl flex-shrink-0 animate-bounce">
                    🎲
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-bold uppercase tracking-wider px-2 py-0.5 rounded-full bg-red-600/30 text-red-300 border border-red-500/40">Surprise !</span>
                        <h4 class="text-sm font-semibold text-chalk">Recommandation personnalisée</h4>
                    </div>
                    <p class="text-xs text-chalk-muted mt-0.5">{{ session('surprise_info') }}</p>
                </div>
            </div>
            <div class="flex items-center gap-2 w-full sm:w-auto justify-end">
                <a href="{{ route('manga.random') }}" 
                   onclick="if(window.HiddenScan && !{{ auth()->check() ? 'true' : 'false' }}){ const favs = window.HiddenScan.getFavorites().map(f => f.slug).join(','); if(favs) this.href = '{{ route('manga.random') }}?favs=' + encodeURIComponent(favs); }"
                   class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-red-600/20 hover:bg-red-600/30 text-chalk text-xs font-semibold transition-all hover:scale-105 active:scale-95 border border-red-500/30 shadow-sm">
                    <span>Relancer une surprise 🎲</span>
                </a>
                <button @click="show = false" class="p-1.5 rounded-lg text-chalk-muted hover:text-chalk hover:bg-chalk/10 transition-colors" title="Fermer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        </div>
    @endif

    {{-- ═══ Actions ═══ --}}
    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 mb-8 animate-fade-in-up" style="animation-delay: 0.1s;"
         x-data="{
            isFav: false,
            init() { this.isFav = window.HiddenScan.isFavorite('{{ $manga->slug }}'); }
         }">
        @if($manga->chapters->count())
            <a id="top-read-btn" href="{{ route('chapter.show', [$manga->slug, $resumeChapter?->slug ?? $manga->chapters->last()->slug]) }}" class="btn-primary justify-center">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span id="top-read-btn-text">{{ count($readChapterIds) > 0 && $resumeChapter ? 'Reprendre (Ch. ' . $resumeChapter->number . ')' : 'Commencer à lire' }}</span>
            </a>
        @endif

        <button
            @click="isFav = window.HiddenScan.toggleFavorite('{{ $manga->slug }}', '{{ addslashes($manga->title) }}', '{{ $manga->cover_image ? Storage::url($manga->cover_image) : '' }}');"
            class="btn-secondary transition-all justify-center"
            :class="isFav ? '!bg-chalk !border-chalk !text-ink shadow-lg shadow-chalk/20' : ''"
        >
            <svg class="w-5 h-5 flex-shrink-0" fill="none" :fill="isFav ? 'currentColor' : 'none'" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
            <span x-text="isFav ? 'Dans vos favoris' : 'Ajouter aux favoris'"></span>
        </button>
    </div>

    {{-- ═══ Réactions à l'œuvre (style Raijin) ═══ --}}
    <div class="mb-8 animate-fade-in-up" style="animation-delay: 0.12s;" x-data="mangaReactions()" x-init="init()">
        <div class="bg-panel border border-line/50 rounded-xl p-4">
            <p class="text-mist text-sm mb-3 font-semibold text-center">Votre réaction</p>
            <div class="flex items-center justify-center gap-2 sm:gap-3 flex-wrap">
                <template x-for="r in reactions" :key="r.emoji">
                    <button @click="react(r.emoji)"
                            class="flex flex-col items-center gap-1 px-2.5 py-1.5 rounded-xl transition-all hover:scale-110"
                            :class="userReaction === r.emoji ? 'bg-chalk/10 ring-2 ring-chalk scale-105' : 'hover:bg-panel-hi'">
                        <span class="text-xl sm:text-2xl" x-text="r.emoji"></span>
                        <span class="text-xs font-bold" :class="userReaction === r.emoji ? 'text-chalk' : 'text-mist'" x-text="r.count"></span>
                        <span class="text-[10px] text-mist hidden sm:block" x-text="r.label"></span>
                    </button>
                </template>
            </div>
        </div>
    </div>

    <script>
    function mangaReactions() {
        return {
            reactions: [
                { emoji: '🔥', label: 'Incroyable', count: 0 },
                { emoji: '😍', label: 'Adoré', count: 0 },
                { emoji: '😢', label: 'Triste', count: 0 },
                { emoji: '😡', label: 'Énervé', count: 0 },
                { emoji: '🤯', label: 'Choqué', count: 0 },
                { emoji: '😂', label: 'Drôle', count: 0 },
                { emoji: '💤', label: 'Ennuyeux', count: 0 },
            ],
            userReaction: null,
            storageKey: 'hs_reactions_manga_{{ $manga->id }}',
            globalKey: 'hs_reactions_counts_manga_{{ $manga->id }}',

            init() {
                this.userReaction = localStorage.getItem(this.storageKey) || null;
                try {
                    const counts = JSON.parse(localStorage.getItem(this.globalKey) || '{}');
                    this.reactions.forEach(r => { r.count = counts[r.emoji] || 0; });
                } catch(e) {}
            },

            react(emoji) {
                const counts = {};
                this.reactions.forEach(r => { counts[r.emoji] = r.count; });

                if (this.userReaction === emoji) {
                    counts[emoji] = Math.max(0, (counts[emoji] || 1) - 1);
                    this.userReaction = null;
                    localStorage.removeItem(this.storageKey);
                } else {
                    if (this.userReaction) {
                        counts[this.userReaction] = Math.max(0, (counts[this.userReaction] || 1) - 1);
                    }
                    counts[emoji] = (counts[emoji] || 0) + 1;
                    this.userReaction = emoji;
                    localStorage.setItem(this.storageKey, emoji);
                }

                this.reactions.forEach(r => { r.count = counts[r.emoji] || 0; });
                localStorage.setItem(this.globalKey, JSON.stringify(counts));
            }
        };
    }
    </script>
    {{-- Halos d'arrière-plan fixes --}}
    <div class="manga-page-halo-red"></div>
    <div class="manga-page-halo-purple"></div>

    <style>
        body {
            background-color: #0e0e14 !important;
        }
        .manga-page-halo-red {
            position: fixed;
            top: -60px;
            left: -60px;
            width: 420px;
            height: 420px;
            background: radial-gradient(circle, rgba(180, 20, 20, 0.15) 0%, transparent 70%);
            pointer-events: none;
            z-index: 0;
        }
        .manga-page-halo-purple {
            position: fixed;
            bottom: -60px;
            right: -60px;
            width: 380px;
            height: 380px;
            background: radial-gradient(circle, rgba(80, 40, 160, 0.12) 0%, transparent 70%);
            pointer-events: none;
            z-index: 0;
        }
        .manga-page-content-wrap {
            position: relative;
            z-index: 1;
        }

        @media (max-width: 639px) {
            .manga-page-halo-red {
                width: 240px;
                height: 240px;
                top: -30px;
                left: -30px;
            }
            .manga-page-halo-purple {
                width: 220px;
                height: 220px;
                bottom: -30px;
                right: -30px;
            }
            .manga-sidebar-card {
                padding: 12px 14px !important;
                margin-bottom: 12px !important;
            }
            .info-row-item {
                padding: 10px 14px !important;
            }
        }

        /* ─── Section Note ─── */
        .manga-sidebar-card {
            background: #16161f;
            border: 1px solid #1e1e2e;
            border-radius: 10px;
            padding: 16px 18px;
            margin-bottom: 16px;
        }
        .section-title-note {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 15px;
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 10px;
        }
        .rating-body-wrap {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        .stars-group {
            display: flex;
            align-items: center;
            gap: 2px;
        }
        .star-btn {
            font-size: 22px;
            line-height: 1;
            background: transparent;
            border: none;
            padding: 0;
            transition: transform 0.15s ease, color 0.15s ease;
        }
        .star-btn:hover {
            transform: scale(1.15);
        }
        .rating-text-group {
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .rating-score-num {
            font-size: 13px;
            font-weight: 700;
            color: #ffffff;
        }
        .rating-votes-num {
            font-size: 12px;
            color: #60608a;
        }

        /* ─── Section Informations ─── */
        .section-title-info {
            font-size: 15px;
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 10px;
        }
        .info-card-table {
            background: #16161f;
            border: 1px solid #1e1e2e;
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 16px;
        }
        .info-row-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 18px;
            border-bottom: 1px solid #13131e;
            transition: background-color 0.15s ease;
        }
        .info-row-item:last-child {
            border-bottom: none;
        }
        .info-row-item:hover {
            background: rgba(255, 255, 255, 0.02);
        }
        .info-key {
            color: #60608a;
            font-size: 13px;
            font-weight: 500;
        }
        .info-value {
            color: #e8e8f4;
            font-size: 13px;
            font-weight: 600;
        }
        .badge-type-custom {
            background: #2a1a4a;
            border: 1px solid #4a2a8a;
            color: #b090ff;
            font-size: 10px;
            font-weight: 800;
            border-radius: 5px;
            padding: 3px 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: inline-block;
        }

        /* ─── Section Genres ─── */
        .section-title-genres {
            font-size: 13px;
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 10px;
        }
        .genres-wrap-list {
            display: flex;
            flex-wrap: wrap;
            gap: 7px;
            margin-bottom: 16px;
        }
        .genre-tag-item {
            background: rgba(220, 38, 38, 0.07);
            border: 1px solid rgba(220, 38, 38, 0.2);
            color: #e08080;
            border-radius: 6px;
            padding: 5px 12px;
            font-size: 12px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
        }
        .genre-tag-item:hover {
            background: rgba(220, 38, 38, 0.2);
            border-color: rgba(220, 38, 38, 0.5);
            color: #ffffff;
            transform: translateY(-1px);
        }

        /* ─── Section Tags ─── */
        .section-title-tags {
            font-size: 13px;
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 10px;
        }
        .tags-wrap-list {
            display: flex;
            flex-wrap: wrap;
            gap: 7px;
        }
        .tag-item-neutral {
            background: #1c1c28;
            border: 1px solid #1e1e2e;
            color: #9090c0;
            border-radius: 6px;
            padding: 5px 12px;
            font-size: 12px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
        }
        .tag-item-neutral:hover {
            background: #1e1e35;
            border-color: #3a3a60;
            color: #c0c0e0;
            transform: translateY(-1px);
        }
    </style>

    <div class="manga-page-content-wrap">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        {{-- ═══ Colonne gauche: Infos ═══ --}}
        <div class="lg:col-span-1 space-y-4 animate-fade-in-up" style="animation-delay: 0.15s;">

            {{-- Section Note --}}
            <div class="manga-sidebar-card" x-data="ratingWidget()" x-init="init()">
                <h2 class="section-title-note">
                    <svg class="w-4 h-4 text-[#f59e0b] flex-shrink-0" viewBox="0 0 24 24" fill="#f59e0b">
                        <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                    </svg>
                    <span>Note</span>
                </h2>
                <div class="rating-body-wrap">
                    <div class="stars-group">
                        @for($i = 1; $i <= 5; $i++)
                        <button
                            type="button"
                            @click="rate({{ $i }})"
                            class="star-btn cursor-pointer"
                            :class="{{ $i }} <= (hoverScore || userScore || Math.round(average) || 5) ? 'text-[#f59e0b]' : 'text-[#2a2a3e]'"
                            @mouseenter="hoverScore = {{ $i }}"
                            @mouseleave="hoverScore = 0"
                            title="Noter {{ $i }}/5"
                        >★</button>
                        @endfor
                    </div>
                    <div class="rating-text-group">
                        <span class="rating-score-num" x-text="scoreDisplay">{{ $manga->average_rating > 0 ? (float)$manga->average_rating . '/5' : '5/5' }}</span>
                        <span class="rating-votes-num" x-text="votesDisplay">({{ $manga->ratings_count ?? 0 }} {{ ($manga->ratings_count ?? 0) <= 1 ? 'vote' : 'votes' }})</span>
                    </div>
                </div>
            </div>

            <script>
            function ratingWidget() {
                return {
                    userScore: 0,
                    hoverScore: 0,
                    average: {{ $manga->average_rating ?? 0 }},
                    count: {{ $manga->ratings_count ?? 0 }},
                    slug: '{{ $manga->slug }}',
                    get scoreDisplay() {
                        if (this.average > 0) return Number(this.average).toFixed(this.average % 1 === 0 ? 0 : 1) + '/5';
                        return '5/5';
                    },
                    get votesDisplay() {
                        if (this.count > 0) return '(' + this.count + (this.count > 1 ? ' votes)' : ' vote)');
                        return '(0 vote)';
                    },
                    init() {
                        const saved = localStorage.getItem('hiddenscan_rating_' + this.slug);
                        if (saved) this.userScore = parseInt(saved);
                    },
                    rate(score) {
                        if (localStorage.getItem('hiddenscan_rating_' + this.slug)) return;
                        fetch("{{ route('manga.rate', $manga->slug) }}", {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                            },
                            body: JSON.stringify({ score })
                        })
                        .then(r => r.json())
                        .then(data => {
                            localStorage.setItem('hiddenscan_rating_' + this.slug, score);
                            this.userScore = score;
                            this.average = data.average;
                            this.count = data.count;
                        });
                    }
                };
            }
            </script>

            {{-- Section Informations --}}
            <div>
                <h2 class="section-title-info">Informations</h2>
                <div class="info-card-table">
                    <div class="info-row-item">
                        <span class="info-key">Statut</span>
                        <span class="info-value">{{ $manga->status->getLabel() }}</span>
                    </div>
                    <div class="info-row-item">
                        <span class="info-key">Type</span>
                        <span class="badge-type-custom">{{ strtoupper($manga->type->value ?? 'MANHWA') }}</span>
                    </div>
                    @if($manga->release_year)
                    <div class="info-row-item">
                        <span class="info-key">Année</span>
                        <span class="info-value">{{ $manga->release_year }}</span>
                    </div>
                    @endif
                    <div class="info-row-item">
                        <span class="info-key">Chapitres</span>
                        <span class="info-value">{{ $manga->chapters->count() }}</span>
                    </div>
                </div>
            </div>

            {{-- Section Genres --}}
            @if($manga->genres->count())
            <div>
                <h2 class="section-title-genres">Genres</h2>
                <div class="genres-wrap-list">
                    @foreach($manga->genres as $genre)
                        <a href="{{ route('manga.index', ['genre' => $genre->slug]) }}"
                           class="genre-tag-item">
                            {{ $genre->name }}
                        </a>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Section Tags --}}
            @if($manga->tags->count())
            <div>
                <h2 class="section-title-tags">Tags</h2>
                <div class="tags-wrap-list">
                    @foreach($manga->tags as $tag)
                        <a href="{{ route('manga.index', ['tag' => $tag->slug]) }}"
                           class="tag-item-neutral hover:!border-red-500/50 hover:!text-white hover:!bg-red-950/20 transition-all cursor-pointer">
                            #{{ $tag->name }}
                        </a>
                    @endforeach
                </div>
            </div>
            @endif
        </div>

        {{-- ═══ Colonne droite: Synopsis + Chapitres ═══ --}}
        <div class="lg:col-span-2 space-y-6 animate-fade-in-up" style="animation-delay: 0.2s;">

            {{-- Synopsis --}}
            @if($manga->synopsis)
            <div class="bg-[#14151e] rounded-2xl p-5 sm:p-6">
                <h2 class="font-display font-bold text-white mb-3">Synopsis</h2>
                <p class="text-[#9da3b4] text-sm leading-relaxed">{{ $manga->synopsis }}</p>
            </div>
            @endif

            {{-- ═══ Composant Liste de Chapitres Thème Sombre ═══ --}}
            <style>
                .chapters-container-card {
                    background: #111118;
                    border: 1px solid #1e1e2e;
                    border-radius: 12px;
                    padding: 20px;
                    width: 100%;
                    max-width: 640px;
                    margin: 0 auto;
                    color: #e8e8f0;
                    font-family: system-ui, -apple-system, sans-serif;
                }
                .chapters-header {
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    margin-bottom: 12px;
                }
                .chapters-title-left {
                    display: flex;
                    align-items: center;
                    gap: 8px;
                    font-size: 15px;
                    font-weight: 700;
                    color: #ffffff;
                }
                .chapters-title-left svg {
                    width: 16px;
                    height: 16px;
                    color: #dc2626;
                }
                .chapters-badge {
                    background: #16161f;
                    border: 1px solid #1e1e2e;
                    border-radius: 20px;
                    padding: 4px 12px;
                    font-size: 12px;
                    font-weight: 600;
                    color: #a0a0c0;
                }
                .chapters-badge .badge-read-count {
                    color: #dc2626;
                    font-weight: 700;
                }
                .chapters-progress-bar-wrap {
                    height: 2px;
                    background: #1a1a28;
                    border-radius: 9999px;
                    overflow: hidden;
                    margin-bottom: 16px;
                }
                .chapters-progress-bar-fill {
                    height: 100%;
                    background: #dc2626;
                    transition: width 0.3s cubic-bezier(0.16, 1, 0.3, 1);
                    width: 0%;
                }
                .btn-resume-chapters {
                    width: 100%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    gap: 8px;
                    background: #16161f;
                    border: 1px solid #1e1e2e;
                    border-radius: 8px;
                    padding: 10px 14px;
                    font-size: 13px;
                    font-weight: 600;
                    color: #a0a0c0;
                    cursor: pointer;
                    text-decoration: none;
                    transition: all 0.2s ease;
                    margin-bottom: 14px;
                }
                .btn-resume-chapters:hover {
                    background: #1c1c2c;
                    border-color: #2e2e48;
                    color: #ffffff;
                }
                .btn-resume-chapters .play-icon {
                    width: 14px;
                    height: 14px;
                    fill: #dc2626;
                    flex-shrink: 0;
                }
                .chapters-list-wrap {
                    display: flex;
                    flex-direction: column;
                    gap: 3px;
                    max-height: 480px;
                    overflow-y: auto;
                    padding-right: 4px;
                }
                .chapters-list-wrap::-webkit-scrollbar {
                    width: 4px;
                }
                .chapters-list-wrap::-webkit-scrollbar-track {
                    background: #111118;
                }
                .chapters-list-wrap::-webkit-scrollbar-thumb {
                    background: #1e1e2e;
                    border-radius: 4px;
                }
                .chapter-row {
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    padding: 10px 12px;
                    border-radius: 8px;
                    cursor: pointer;
                    transition: all 0.2s ease;
                    gap: 12px;
                    user-select: none;
                    background: transparent;
                }
                .chapter-row:hover {
                    background: #16161f;
                }
                .chapter-row.is-read {
                    opacity: 0.5;
                }
                .chapter-left {
                    display: flex;
                    align-items: center;
                    gap: 12px;
                    min-width: 0;
                    flex: 1;
                }
                .chapter-circle-btn {
                    width: 22px;
                    height: 22px;
                    border-radius: 50%;
                    border: 1.5px solid #2e2e48;
                    background: transparent;
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    flex-shrink: 0;
                    cursor: pointer;
                    transition: all 0.2s ease;
                    padding: 0;
                }
                .chapter-circle-btn:hover {
                    border-color: #dc2626;
                }
                .chapter-circle-btn.checked {
                    background: #dc2626;
                    border-color: #dc2626;
                }
                .chapter-circle-btn .check-svg {
                    width: 11px;
                    height: 11px;
                    stroke: #ffffff;
                    opacity: 0;
                    transform: scale(0.5);
                    transition: all 0.2s ease;
                }
                .chapter-circle-btn.checked .check-svg {
                    opacity: 1;
                    transform: scale(1);
                }
                .chapter-info {
                    display: flex;
                    flex-direction: column;
                    gap: 2px;
                    min-width: 0;
                    flex: 1;
                }
                .chapter-name {
                    font-size: 13px;
                    font-weight: 600;
                    color: #e8e8f0;
                    white-space: nowrap;
                    overflow: hidden;
                    text-overflow: ellipsis;
                    transition: color 0.2s ease;
                }
                .chapter-row:hover .chapter-name {
                    color: #ffffff;
                }
                .chapter-subtitle {
                    font-weight: 400;
                    color: #7070a0;
                    font-size: 12px;
                }
                .chapter-date {
                    font-size: 11px;
                    color: #40405a;
                }
                .chapter-right {
                    display: flex;
                    align-items: center;
                    gap: 8px;
                    flex-shrink: 0;
                }
                .chapter-mark-upto-btn {
                    padding: 3px 8px;
                    border-radius: 6px;
                    font-size: 10px;
                    font-weight: 600;
                    color: #7070a0;
                    background: rgba(255,255,255,0.03);
                    border: 1px solid rgba(255,255,255,0.06);
                    transition: all 0.2s ease;
                    cursor: pointer;
                    display: flex;
                    align-items: center;
                    gap: 4px;
                    opacity: 0;
                }
                .chapter-row:hover .chapter-mark-upto-btn {
                    opacity: 1;
                }
                .chapter-mark-upto-btn:hover {
                    color: #ffffff;
                    background: rgba(220, 38, 38, 0.15);
                    border-color: rgba(220, 38, 38, 0.4);
                }
                .chapter-arrow {
                    width: 14px;
                    height: 14px;
                    color: #40405a;
                    transition: all 0.2s ease;
                }
                .chapter-row:hover .chapter-arrow {
                    color: #a0a0c0;
                    transform: translateX(2px);
                }

                @media (max-width: 639px) {
                    .chapters-container-card {
                        padding: 14px 10px !important;
                        border-radius: 10px !important;
                    }
                    .chapters-header {
                        margin-bottom: 10px !important;
                    }
                    .chapters-badge {
                        padding: 3px 8px !important;
                        font-size: 11px !important;
                    }
                    .btn-resume-chapters {
                        padding: 9px 12px !important;
                        font-size: 12px !important;
                        margin-bottom: 10px !important;
                    }
                    .chapter-row {
                        padding: 8px 8px !important;
                        gap: 8px !important;
                    }
                    .chapter-left {
                        gap: 8px !important;
                    }
                    .chapter-name {
                        font-size: 12.5px !important;
                    }
                    .chapter-date {
                        font-size: 10px !important;
                    }
                    .chapter-mark-upto-btn {
                        opacity: 0.85 !important;
                        padding: 2px 6px !important;
                        font-size: 9.5px !important;
                    }
                }
                @media (hover: none) {
                    .chapter-mark-upto-btn {
                        opacity: 0.85 !important;
                    }
                }
            </style>

            <div class="chapters-container-card">
                {{-- Header du bloc --}}
                <div class="chapters-header">
                    <div class="chapters-title-left">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                        </svg>
                        <span>Chapitres</span>
                    </div>
                    <div class="chapters-badge">
                        <span class="badge-read-count" id="badge-read-count">{{ count($readChapterIds) }}</span> / <span id="badge-total-count">{{ $manga->chapters->count() }}</span> lus (<span id="badge-read-pct">{{ $manga->chapters->count() > 0 ? round((count($readChapterIds) / $manga->chapters->count()) * 100) : 0 }}%</span>)
                    </div>
                </div>

                {{-- Barre de progression --}}
                <div class="chapters-progress-bar-wrap">
                    <div class="chapters-progress-bar-fill" id="chapters-progress-fill" style="width: {{ $manga->chapters->count() > 0 ? round((count($readChapterIds) / $manga->chapters->count()) * 100) : 0 }}%;"></div>
                </div>

                @if($manga->chapters->count())
                    {{-- Bouton reprendre --}}
                    <a href="{{ route('chapter.show', [$manga->slug, $resumeChapter?->slug ?? $manga->chapters->last()->slug]) }}" class="btn-resume-chapters" id="btn-resume-chapters">
                        <svg class="play-icon" viewBox="0 0 24 24">
                            <path d="M8 5v14l11-7z"/>
                        </svg>
                        <span id="btn-resume-label">{{ count($readChapterIds) > 0 && $resumeChapter ? 'Reprendre la lecture (Ch. ' . $resumeChapter->number . ')' : 'Commencer la lecture' }}</span>
                    </a>

                    {{-- Chaque ligne de chapitre --}}
                    <div class="chapters-list-wrap" id="chapters-list">
                        @foreach($manga->chapters as $chapter)
                            @php
                                $isRead = in_array($chapter->id, $readChapterIds);
                            @endphp
                            <div class="chapter-row {{ $isRead ? 'is-read' : '' }}"
                                 data-id="{{ $chapter->id }}"
                                 data-number="{{ $chapter->number }}"
                                 data-url="{{ route('chapter.show', [$manga->slug, $chapter->slug]) }}">
                                
                                <div class="chapter-left">
                                    <button type="button" class="chapter-circle-btn {{ $isRead ? 'checked' : '' }}" aria-label="Marquer comme lu" title="{{ $isRead ? 'Marquer comme non lu' : 'Marquer comme lu' }}">
                                        <svg class="check-svg" viewBox="0 0 24 24" stroke-width="3.5">
                                            <polyline points="20 6 9 17 4 12"></polyline>
                                        </svg>
                                    </button>
                                    
                                    <div class="chapter-info">
                                        <span class="chapter-name">
                                            Chapitre {{ $chapter->number }}
                                            @if($chapter->title)
                                                <span class="chapter-subtitle">— {{ $chapter->title }}</span>
                                            @endif
                                        </span>
                                        <span class="chapter-date">{{ $chapter->published_at ? $chapter->published_at->diffForHumans() : 'Récemment' }}</span>
                                    </div>
                                </div>

                                <div class="chapter-right">
                                    <button type="button" class="chapter-mark-upto-btn" title="Marquer lu jusqu'ici">
                                        <svg class="w-3.5 h-3.5 text-[#dc2626]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M18 15l-6-6-6 6"/><path d="M18 9l-6-6-6 6"/>
                                        </svg>
                                        <span>Lu jusqu'ici</span>
                                    </button>
                                    <svg class="chapter-arrow" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-12">
                        <svg class="w-12 h-12 mx-auto text-[#40405a] mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                        <p class="text-[#7070a0] text-sm">Aucun chapitre disponible pour le moment.</p>
                    </div>
                @endif
            </div>

            {{-- ═══ Logique JS Vanilla ═══ --}}
            <script>
            (function() {
                const mangaId = {{ $manga->id }};
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                
                // Objet states: stocke l'état lu/non-lu de chaque chapitre
                const states = {};
                const chapterUrls = {};
                const chapterNumbers = {};

                @foreach($manga->chapters as $chapter)
                    states[{{ $chapter->id }}] = {{ in_array($chapter->id, $readChapterIds) ? 'true' : 'false' }};
                    chapterUrls[{{ $chapter->id }}] = "{{ route('chapter.show', [$manga->slug, $chapter->slug]) }}";
                    chapterNumbers[{{ $chapter->id }}] = {{ $chapter->number }};
                @endforeach

                const badgeReadCount = document.getElementById('badge-read-count');
                const badgeTotalCount = document.getElementById('badge-total-count');
                const badgeReadPct = document.getElementById('badge-read-pct');
                const progressFill = document.getElementById('chapters-progress-fill');
                const resumeBtn = document.getElementById('btn-resume-chapters');
                const resumeLabel = document.getElementById('btn-resume-label');
                const topReadBtn = document.getElementById('top-read-btn');
                const topReadBtnText = document.getElementById('top-read-btn-text');
                const chaptersList = document.getElementById('chapters-list');

                function updateChapterProgress() {
                    const chapterIds = Object.keys(states);
                    const total = chapterIds.length;
                    let readCount = 0;
                    for (const id of chapterIds) {
                        if (states[id]) readCount++;
                    }
                    const pct = total > 0 ? Math.round((readCount / total) * 100) : 0;

                    if (badgeReadCount) badgeReadCount.textContent = readCount;
                    if (badgeTotalCount) badgeTotalCount.textContent = total;
                    if (badgeReadPct) badgeReadPct.textContent = pct + '%';
                    if (progressFill) progressFill.style.width = pct + '%';

                    // Met à jour visuellement chaque ligne
                    document.querySelectorAll('.chapter-row').forEach(row => {
                        const id = row.getAttribute('data-id');
                        const isRead = !!states[id];
                        row.classList.toggle('is-read', isRead);
                        const circle = row.querySelector('.chapter-circle-btn');
                        if (circle) {
                            circle.classList.toggle('checked', isRead);
                            circle.title = isRead ? 'Marquer comme non lu' : 'Marquer comme lu';
                        }
                    });

                    // Redirection vers le premier chapitre dont l'état est "non lu"
                    const sortedIds = chapterIds.sort((a, b) => chapterNumbers[a] - chapterNumbers[b]);
                    const firstUnreadId = sortedIds.find(id => !states[id]);

                    if (firstUnreadId) {
                        const url = chapterUrls[firstUnreadId];
                        const num = chapterNumbers[firstUnreadId];
                        if (resumeBtn) {
                            resumeBtn.href = url;
                            if (resumeLabel) resumeLabel.textContent = readCount > 0 ? `Reprendre la lecture (Ch. ${num})` : 'Commencer la lecture';
                        }
                        if (topReadBtn) {
                            topReadBtn.href = url;
                            if (topReadBtnText) topReadBtnText.textContent = readCount > 0 ? `Reprendre (Ch. ${num})` : 'Commencer à lire';
                        }
                    } else if (sortedIds.length > 0) {
                        // Tous les chapitres sont lus
                        const firstUrl = chapterUrls[sortedIds[0]];
                        if (resumeBtn) {
                            resumeBtn.href = firstUrl;
                            if (resumeLabel) resumeLabel.textContent = 'Relire depuis le début';
                        }
                        if (topReadBtn) {
                            topReadBtn.href = firstUrl;
                            if (topReadBtnText) topReadBtnText.textContent = 'Relire depuis le Ch. 1';
                        }
                    }
                }

                // Initialisation de la progression
                updateChapterProgress();

                // Gestion des clics avec délégation d'événements
                if (chaptersList) {
                    chaptersList.addEventListener('click', function(e) {
                        // 1. Clic sur le cercle : bascule l'état lu/non-lu
                        const circle = e.target.closest('.chapter-circle-btn');
                        if (circle) {
                            e.preventDefault();
                            e.stopPropagation();

                            const row = circle.closest('.chapter-row');
                            if (!row) return;
                            const id = row.getAttribute('data-id');

                            // Mise à jour instantanée (Optimistic UI)
                            states[id] = !states[id];
                            updateChapterProgress();

                            // Synchronisation API
                            fetch('/api/progress/toggle', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': csrfToken,
                                    'Accept': 'application/json',
                                },
                                body: JSON.stringify({ chapter_id: parseInt(id) })
                            })
                            .then(async (res) => {
                                if (!res.ok) throw new Error();
                                return res.json();
                            })
                            .then(data => {
                                if (data && data.success && Array.isArray(data.read_chapter_ids)) {
                                    const readSet = new Set(data.read_chapter_ids);
                                    for (const k in states) {
                                        states[k] = readSet.has(parseInt(k));
                                    }
                                    updateChapterProgress();
                                }
                            })
                            .catch(() => {
                                // Rollback si erreur ou non connecté
                                states[id] = !states[id];
                                updateChapterProgress();
                            });
                            return;
                        }

                        // 2. Bouton "Marquer lu jusqu'ici"
                        const markUpToBtn = e.target.closest('.chapter-mark-upto-btn');
                        if (markUpToBtn) {
                            e.preventDefault();
                            e.stopPropagation();

                            const row = markUpToBtn.closest('.chapter-row');
                            if (!row) return;
                            const id = row.getAttribute('data-id');
                            const targetNum = chapterNumbers[id];

                            const oldStates = { ...states };

                            for (const k in states) {
                                if (chapterNumbers[k] <= targetNum) {
                                    states[k] = true;
                                }
                            }
                            updateChapterProgress();

                            fetch('/api/progress/mark-up-to', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': csrfToken,
                                    'Accept': 'application/json',
                                },
                                body: JSON.stringify({ chapter_id: parseInt(id) })
                            })
                            .then(async (res) => {
                                if (!res.ok) throw new Error();
                                return res.json();
                            })
                            .then(data => {
                                if (data && data.success && Array.isArray(data.read_chapter_ids)) {
                                    const readSet = new Set(data.read_chapter_ids);
                                    for (const k in states) {
                                        states[k] = readSet.has(parseInt(k));
                                    }
                                    updateChapterProgress();
                                }
                            })
                            .catch(() => {
                                Object.assign(states, oldStates);
                                updateChapterProgress();
                            });
                            return;
                        }

                        // 3. Clic sur la ligne entière (hors cercle) : navigue vers le chapitre
                        const row = e.target.closest('.chapter-row');
                        if (row && row.getAttribute('data-url')) {
                            window.location.href = row.getAttribute('data-url');
                        }
                    });
                }

                // Synchronisation fraîche au chargement via l'API
                fetch(`/api/progress/${mangaId}`)
                    .then(r => r.json())
                    .then(data => {
                        if (data && data.success && Array.isArray(data.read_chapter_ids)) {
                            const readSet = new Set(data.read_chapter_ids);
                            for (const k in states) {
                                states[k] = readSet.has(parseInt(k));
                            }
                            updateChapterProgress();
                        }
                    })
                    .catch(() => {});
            })();
            </script>

            {{-- Commentaires --}}
            <div class="bg-[#131318] rounded-xl sm:rounded-2xl p-3.5 sm:p-6 lg:p-8" style="border: 1px solid #252535; max-width: 640px; margin: 24px auto 0 auto;">
                <livewire:public.comment-section
                    type="App\Models\Manga"
                    :id="$manga->id"
                />
            </div>
        </div>
    </div>
    </div>

</x-layouts.public>