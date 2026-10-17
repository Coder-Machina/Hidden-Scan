@use('Illuminate\Support\Facades\Storage')
<x-layouts.public :title="$manga->title . ' — Hidden Scan'">

    {{-- ═══ EN-TÊTE MANGA : Cover Gauche / Métadonnées Droite (Composant 6) ═══ --}}
    <div class="mb-10" data-aos="fade-in" data-aos-duration="400">
        <div class="flex flex-col md:flex-row items-start gap-8">
            
            {{-- Couverture 3:4 Gauche (Strict 6px radius, 1px border) --}}
            <div class="w-48 sm:w-60 md:w-64 aspect-[3/4] flex-shrink-0 rounded-[6px] overflow-hidden border border-[var(--border)] bg-[var(--bg-surface)] shadow-sm">
                @if($manga->cover_image)
                    <img src="{{ Storage::url($manga->cover_image) }}" alt="{{ $manga->title }}" class="w-full h-full object-cover">
                @else
                    <div class="w-full h-full flex flex-col items-center justify-center p-4 text-center text-[var(--text-3)]">
                        <x-lucide name="book-open" class="w-10 h-10 mb-1 text-[var(--text-3)]" />
                        <span class="text-caption">{{ $manga->title }}</span>
                    </div>
                @endif
            </div>

            {{-- Métadonnées Droite --}}
            <div class="flex-1 min-w-0 flex flex-col gap-4">
                
                {{-- Badges de statut & Type --}}
                <div class="flex flex-wrap items-center gap-2">
                    @if(!($manga->is_external ?? false))
                        <span class="badge-official-hs">Projet Officiel</span>
                    @endif
                    <span class="badge-tag">{{ strtoupper($manga->type->value ?? 'MANGA') }}</span>
                    <span class="badge-tag">{{ $manga->status->getLabel() }}</span>
                    @if($manga->average_rating > 0)
                        <span class="badge-tag text-[var(--accent)] font-bold inline-flex items-center gap-1">
                            <x-lucide name="star" class="w-3.5 h-3.5 fill-current" />
                            <span>{{ number_format($manga->average_rating, 1) }}</span>
                        </span>
                    @endif
                </div>

                {{-- Titre H1 (32px / Nunito 600) --}}
                <h1 class="text-h1">
                    {{ $manga->title }}
                </h1>

                {{-- Auteurs & Artistes --}}
                @if($manga->authors->count() || $manga->artists->count())
                    <p class="text-caption text-[var(--text-2)]">
                        Par 
                        @if($manga->authors->count())
                            <span class="text-[var(--text-1)] font-medium">{{ $manga->authors->pluck('name')->join(', ') }}</span>
                        @endif
                        @if($manga->artists->count() && $manga->artists->pluck('id')->diff($manga->authors->pluck('id'))->isNotEmpty())
                            · Dessin par <span class="text-[var(--text-1)] font-medium">{{ $manga->artists->pluck('name')->diff($manga->authors->pluck('name'))->join(', ') }}</span>
                        @endif
                    </p>
                @endif

                {{-- Synopsis avec Expansion Propre --}}
                @if($manga->synopsis)
                    <div x-data="{ expanded: false }" class="text-body text-[var(--text-2)]">
                        <p :class="expanded ? '' : 'line-clamp-3'" class="leading-relaxed">
                            {{ $manga->synopsis }}
                        </p>
                        @if(strlen($manga->synopsis) > 220)
                            <button type="button" @click="expanded = !expanded" class="text-caption text-[var(--accent)] hover:underline mt-1 font-semibold cursor-pointer">
                                <span x-text="expanded ? 'Réduire' : 'Lire la suite'"></span>
                            </button>
                        @endif
                    </div>
                @endif

                {{-- Genres Pills --}}
                @if($manga->genres->count())
                    <div class="flex flex-wrap items-center gap-2 pt-1">
                        @foreach($manga->genres as $genre)
                            <a href="{{ route('manga.index', ['genre' => $genre->slug]) }}" class="genre-pill !py-1 !px-2.5 !text-caption">
                                {{ $genre->name }}
                            </a>
                        @endforeach
                    </div>
                @endif

                {{-- Boutons d'Action (Commencer à lire / Favoris / Noter) --}}
                <div class="flex flex-wrap items-center gap-3 pt-2"
                     x-data="{
                        isFav: {{ (auth()->check() && auth()->user()->favoriteMangas->contains($manga->id)) ? 'true' : 'false' }},
                        init() { 
                            if (window.HiddenScan && typeof window.HiddenScan.isFavorite === 'function') {
                                this.isFav = window.HiddenScan.isFavorite('{{ $manga->slug }}'); 
                            }
                        }
                     }">
                    @if($manga->chapters->count())
                        <a id="top-read-btn" href="{{ route('chapter.show', [$manga->slug, $resumeChapter?->slug ?? $manga->chapters->last()->slug]) }}" 
                           class="btn-primary">
                            <span id="top-read-btn-text">{{ count($readChapterIds) > 0 && $resumeChapter ? 'Reprendre (Ch. ' . $resumeChapter->number . ')' : 'Commencer à lire' }}</span>
                            <span>→</span>
                        </a>
                    @endif

                    <button type="button"
                        @click="if (window.HiddenScan) { isFav = window.HiddenScan.toggleFavorite('{{ $manga->slug }}', '{{ addslashes($manga->title) }}', '{{ $manga->cover_image ? Storage::url($manga->cover_image) : '' }}'); }"
                        class="btn-secondary inline-flex items-center gap-2"
                        :class="isFav ? '!border-[var(--accent)] text-[var(--accent)]' : ''"
                    >
                        <x-lucide name="heart" class="w-4 h-4 shrink-0" ::class="isFav ? 'fill-current text-[var(--accent)]' : 'text-current'" />
                        <span x-text="isFav ? 'Dans vos favoris' : 'Ajouter aux favoris'">{{ (auth()->check() && auth()->user()->favoriteMangas->contains($manga->id)) ? 'Dans vos favoris' : 'Ajouter aux favoris' }}</span>
                    </button>
                </div>

            </div>
        </div>
    </div>

    <div class="section-separator"></div>

    {{-- ═══ SECTION PRINCIPALE : Sidebar 240px + Liste des Chapitres en Tableau ═══ --}}
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
        
        {{-- Sidebar 240px (Détails & Évaluation) --}}
        <div class="lg:col-span-1 space-y-6">
            <div class="bg-[var(--bg-surface)] border border-[var(--border)] rounded-[6px] p-4 space-y-3">
                <h2 class="section-title-info"><span class="text-label text-[var(--text-1)] border-b border-[var(--border)] pb-2 block">Informations</span></h2>
                
                <div class="flex items-center justify-between text-caption py-1">
                    <span class="text-[var(--text-3)]">Statut</span>
                    <span class="text-[var(--text-1)] font-semibold">{{ $manga->status->getLabel() }}</span>
                </div>
                <div class="flex items-center justify-between text-caption py-1">
                    <span class="text-[var(--text-3)]">Type</span>
                    <span class="text-[var(--text-1)] font-semibold">{{ strtoupper($manga->type->value ?? 'MANGA') }}</span>
                </div>
                @if($manga->release_year)
                <div class="flex items-center justify-between text-caption py-1">
                    <span class="text-[var(--text-3)]">Année</span>
                    <span class="text-[var(--text-1)] font-semibold">{{ $manga->release_year }}</span>
                </div>
                @endif
                <div class="flex items-center justify-between text-caption py-1">
                    <span class="text-[var(--text-3)]">Total Chapitres</span>
                    <span class="text-[var(--text-1)] font-semibold">{{ $manga->chapters->count() }}</span>
                </div>
            </div>

            {{-- Module d'Évaluation --}}
            <div class="bg-[var(--bg-surface)] border border-[var(--border)] rounded-[6px] p-4"
                 x-data="ratingComponent('{{ $manga->slug }}', {{ $manga->average_rating }}, {{ $manga->ratings_count ?? 0 }})">
                <h3 class="text-label text-[var(--text-1)] mb-2">Note de la série</h3>
                <div class="flex items-center gap-2 mb-2">
                    <div class="flex items-center gap-1">
                        @for($i = 1; $i <= 5; $i++)
                            <button type="button" @click="rate({{ $i }})" class="p-0.5 cursor-pointer hover:scale-110 transition-transform"
                                    :class="hoverScore >= {{ $i }} || (!hoverScore && (userScore || Math.round(average)) >= {{ $i }}) ? 'text-amber-400' : 'text-[var(--text-3)]'">
                                <x-lucide name="star" class="w-4 h-4" ::class="hoverScore >= {{ $i }} || (!hoverScore && (userScore || Math.round(average)) >= {{ $i }}) ? 'fill-current' : ''" />
                            </button>
                        @endfor
                    </div>
                    <span class="text-caption font-bold text-[var(--text-1)]" x-text="average.toFixed(1)"></span>
                </div>
                <div class="text-[11px] text-[var(--text-3)]" x-text="count + ' vote(s)'"></div>
            </div>

            <script>
            function ratingComponent(slug, avg, count) {
                return {
                    slug: slug,
                    average: avg || 4.8,
                    count: count || 0,
                    userScore: 0,
                    hoverScore: 0,
                    init() {
                        this.userScore = parseInt(localStorage.getItem('hiddenscan_rating_' + this.slug) || '0');
                    },
                    rate(score) {
                        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                        fetch('/manga/' + this.slug + '/noter', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token || '' },
                            body: JSON.stringify({ rating: score })
                        })
                        .then(r => r.json())
                        .then(data => {
                            localStorage.setItem('hiddenscan_rating_' + this.slug, score);
                            this.userScore = score;
                            if (data.average) this.average = data.average;
                            if (data.count) this.count = data.count;
                        });
                    }
                };
            }
            </script>
        </div>

        {{-- Main Area : Tableau des Chapitres --}}
        <div class="lg:col-span-3 space-y-6">
            
            <div class="chapters-container-card">
            <div class="bg-[var(--bg-surface)] border border-[var(--border)] rounded-[6px] overflow-hidden"
                 x-data="{
                     searchChapter: '',
                     orderAsc: false,
                 }">
                
                {{-- En-tête Tableau Chapitres --}}
                <div class="p-4 border-b border-[var(--border)] flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 bg-[var(--bg-surface)]">
                    <div>
                        <h2 class="text-h3 font-semibold text-[var(--text-1)]">Liste des Chapitres</h2>
                        <span class="text-caption text-[var(--text-3)]">{{ $manga->chapters->count() }} chapitres disponibles</span>
                    </div>

                    {{-- Recherche rapide de chapitre --}}
                    <div class="w-full sm:w-48">
                        <input type="text" x-model="searchChapter" placeholder="N° de chapitre..." 
                               class="input-field !py-1 !px-2.5 !text-caption">
                    </div>
                </div>

                {{-- Table des Chapitres --}}
                <div id="chapters-list-container" class="divide-y divide-[var(--border)]">
                    @forelse($manga->chapters->sortByDesc('number') as $chapter)
                        @php
                            $isRead = in_array($chapter->id, $readChapterIds);
                        @endphp
                        <div class="chapter-row flex items-center justify-between p-3.5 hover:bg-[var(--bg-raised)] transition"
                             data-id="{{ $chapter->id }}"
                             data-num="{{ $chapter->number }}"
                             data-url="{{ route('chapter.show', [$manga->slug, $chapter->slug]) }}"
                             x-show="!searchChapter || '{{ $chapter->number }}'.includes(searchChapter)">
                            
                            {{-- Checkbox de lecture + Numéro + Titre --}}
                            <div class="flex items-center gap-3 min-w-0 flex-1">
                                <button type="button" 
                                        class="chapter-circle-btn w-5 h-5 rounded-[4px] border border-[var(--border)] flex items-center justify-center cursor-pointer transition {{ $isRead ? 'bg-[var(--accent)] border-[var(--accent)] text-white' : 'text-transparent hover:border-[var(--text-3)]' }}"
                                        title="Marquer comme lu">
                                    <x-lucide name="check" class="w-3.5 h-3.5 stroke-[3]" />
                                </button>

                                <div class="min-w-0">
                                    <a href="{{ route('chapter.show', [$manga->slug, $chapter->slug]) }}" 
                                       class="font-heading font-semibold text-[14px] text-[var(--text-1)] hover:text-[var(--accent)] transition truncate block">
                                        Chapitre {{ $chapter->number }}
                                        @if($chapter->title)
                                            <span class="text-caption font-normal text-[var(--text-2)] ml-1">— {{ $chapter->title }}</span>
                                        @endif
                                    </a>
                                </div>
                            </div>

                            {{-- Date + Bouton Lire --}}
                            <div class="flex items-center gap-4 flex-shrink-0">
                                @if($chapter->published_at)
                                    <span class="text-caption text-[var(--text-3)] hidden sm:inline">
                                        {{ $chapter->published_at->translatedFormat('d M Y') }}
                                    </span>
                                @endif

                                <a href="{{ route('chapter.show', [$manga->slug, $chapter->slug]) }}" 
                                   class="btn-secondary !py-1 !px-3 !text-caption">
                                    Lire
                                </a>
                            </div>
                        </div>
                    @empty
                        <div class="p-8 text-center text-caption text-[var(--text-3)]">
                            Aucun chapitre disponible pour le moment.
                        </div>
                    @endforelse
                </div>
            </div>
            </div>

            {{-- Script de délégation des chapitres (Synchronisation Optimiste) --}}
            <script>
            (function() {
                const container = document.getElementById('chapters-list-container');
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                if (!container) return;

                container.addEventListener('click', function(e) {
                    const circle = e.target.closest('.chapter-circle-btn');
                    if (circle) {
                        e.preventDefault();
                        e.stopPropagation();
                        const row = circle.closest('.chapter-row');
                        if (!row) return;
                        const id = row.getAttribute('data-id');

                        const isNowChecked = !circle.classList.contains('bg-[var(--accent)]');
                        if (isNowChecked) {
                            circle.classList.add('bg-[var(--accent)]', 'border-[var(--accent)]', 'text-white');
                            circle.classList.remove('text-transparent');
                        } else {
                            circle.classList.remove('bg-[var(--accent)]', 'border-[var(--accent)]', 'text-white');
                            circle.classList.add('text-transparent');
                        }

                        fetch('/api/progress/toggle', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({ chapter_id: parseInt(id) })
                        }).catch(() => {});
                    }
                });
            })();
            </script>

            {{-- ═══ Section Commentaires ═══ --}}
            <div id="manga-comments-section" class="bg-[var(--bg-surface)] border border-[var(--border)] rounded-[6px] p-6">
                <h3 class="text-h3 text-[var(--text-1)] mb-4">Commentaires de la communauté</h3>
                <livewire:public.comment-section
                    type="App\Models\Manga"
                    :id="$manga->id"
                />
            </div>

        </div>
    </div>

</x-layouts.public>