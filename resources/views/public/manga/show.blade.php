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
                        <span class="badge-type badge-{{ $manga->type }}">{{ ucfirst($manga->type) }}</span>
                        <span class="badge-status badge-{{ $manga->status }}">{{ ucfirst(str_replace('_', ' ', $manga->status)) }}</span>
                    </div>
                    <h1 class="font-display font-extrabold text-2xl sm:text-3xl lg:text-4xl tracking-tight text-chalk leading-tight truncate">{{ $manga->title }}</h1>
                    @if($manga->author)
                        <p class="text-mist text-sm mt-1">
                            {{ $manga->author->name }}
                            @if($manga->artist && $manga->artist->id !== $manga->author->id)
                                · {{ $manga->artist->name }}
                            @endif
                        </p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Espace pour la cover qui dépasse --}}
    <div class="h-12 sm:h-16"></div>

    {{-- ═══ Actions ═══ --}}
    <div class="flex flex-wrap items-center gap-3 mb-8 animate-fade-in-up" style="animation-delay: 0.1s;"
         x-data="{
            isFav: false,
            init() { this.isFav = window.HiddenScan.isFavorite('{{ $manga->slug }}'); }
         }">
        @if($manga->chapters->count())
            <a href="{{ route('chapter.show', [$manga->slug, $manga->chapters->last()->slug]) }}" class="btn-primary">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Commencer à lire
            </a>
        @endif

        <button
            @click="isFav = window.HiddenScan.toggleFavorite('{{ $manga->slug }}', '{{ addslashes($manga->title) }}', '{{ $manga->cover_image ? Storage::url($manga->cover_image) : '' }}');"
            class="btn-secondary transition-all"
            :class="isFav ? '!bg-violet !border-violet !text-white' : ''"
        >
            <svg class="w-5 h-5" fill="none" :fill="isFav ? 'currentColor' : 'none'" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
            <span x-text="isFav ? 'Dans vos favoris' : 'Ajouter aux favoris'"></span>
        </button>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        {{-- ═══ Colonne gauche: Infos ═══ --}}
        <div class="lg:col-span-1 space-y-4 animate-fade-in-up" style="animation-delay: 0.15s;">

            {{-- Notation --}}
            <div class="bg-panel border border-line/50 rounded-xl p-5" x-data="ratingWidget()" x-init="init()">
                <h2 class="font-display font-bold text-chalk mb-3 flex items-center gap-2">
                    <svg class="w-4 h-4 text-amber" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                    Note
                </h2>
                <div class="flex items-center gap-3">
                    <div class="flex gap-0.5">
                        @for($i = 1; $i <= 5; $i++)
                        <button
                            @click="rate({{ $i }})"
                            class="text-2xl transition-all hover:scale-125 cursor-pointer"
                            :class="{{ $i }} <= (hoverScore || userScore || 0) ? 'text-amber' : 'text-line'"
                            @mouseenter="hoverScore = {{ $i }}"
                            @mouseleave="hoverScore = 0"
                        >★</button>
                        @endfor
                    </div>
                    <span class="text-sm text-mist" x-text="ratingText"></span>
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
                    get ratingText() {
                        if (this.average > 0) return this.average + '/5 (' + this.count + ' votes)';
                        return 'Pas encore noté';
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

            {{-- Informations --}}
            <div class="bg-panel border border-line/50 rounded-xl p-5">
                <h2 class="font-display font-bold text-chalk mb-3">Informations</h2>
                <dl class="space-y-3 text-sm">
                    <div class="flex items-center justify-between">
                        <dt class="text-mist">Statut</dt>
                        <dd><span class="badge-status badge-{{ $manga->status }}">{{ ucfirst(str_replace('_', ' ', $manga->status)) }}</span></dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt class="text-mist">Type</dt>
                        <dd><span class="badge-type badge-{{ $manga->type }}">{{ ucfirst($manga->type) }}</span></dd>
                    </div>
                    @if($manga->release_year)
                    <div class="flex items-center justify-between">
                        <dt class="text-mist">Année</dt>
                        <dd class="font-semibold">{{ $manga->release_year }}</dd>
                    </div>
                    @endif
                    <div class="flex items-center justify-between">
                        <dt class="text-mist">Chapitres</dt>
                        <dd class="font-semibold">{{ $manga->chapters->count() }}</dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt class="text-mist">Vues</dt>
                        <dd class="font-semibold flex items-center gap-1">
                            <svg class="w-3.5 h-3.5 text-mist" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            {{ number_format($manga->views_count) }}
                        </dd>
                    </div>
                </dl>
            </div>

            {{-- Genres --}}
            @if($manga->genres->count())
            <div class="bg-panel border border-line/50 rounded-xl p-5">
                <h2 class="font-display font-bold text-chalk mb-3">Genres</h2>
                <div class="flex flex-wrap gap-2">
                    @foreach($manga->genres as $genre)
                        <a href="{{ route('manga.index', ['genre' => $genre->slug]) }}"
                           class="text-xs px-3 py-1.5 rounded-full bg-panel-hi border border-line/50 text-violet hover:bg-violet hover:text-white hover:border-violet transition-all">
                            {{ $genre->name }}
                        </a>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Tags --}}
            @if($manga->tags->count())
            <div class="bg-panel border border-line/50 rounded-xl p-5">
                <h2 class="font-display font-bold text-chalk mb-3">Tags</h2>
                <div class="flex flex-wrap gap-2">
                    @foreach($manga->tags as $tag)
                        <span class="text-xs px-3 py-1.5 rounded-full bg-ink-deep border border-line/30 text-mist">
                            {{ $tag->name }}
                        </span>
                    @endforeach
                </div>
            </div>
            @endif
        </div>

        {{-- ═══ Colonne droite: Synopsis + Chapitres ═══ --}}
        <div class="lg:col-span-2 space-y-6 animate-fade-in-up" style="animation-delay: 0.2s;">

            {{-- Synopsis --}}
            @if($manga->synopsis)
            <div class="bg-panel border border-line/50 rounded-xl p-5">
                <h2 class="font-display font-bold text-chalk mb-3">Synopsis</h2>
                <p class="text-mist text-sm leading-relaxed">{{ $manga->synopsis }}</p>
            </div>
            @endif

            {{-- Chapitres --}}
            <div class="bg-panel border border-line/50 rounded-xl p-5">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="font-display font-bold text-chalk flex items-center gap-2">
                        <svg class="w-5 h-5 text-violet" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                        Chapitres
                    </h2>
                    <span class="text-xs text-mist bg-panel-hi px-2.5 py-1 rounded-full">{{ $manga->chapters->count() }} chapitres</span>
                </div>

                @if($manga->chapters->count())
                    {{-- Bouton Commencer --}}
                    @if($manga->chapters->last())
                        <a href="{{ route('chapter.show', [$manga->slug, $manga->chapters->last()->slug]) }}"
                           class="block w-full text-center btn-primary mb-4 !py-3">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/></svg>
                            Commencer la lecture
                        </a>
                    @endif

                    {{-- Liste des chapitres --}}
                    <div class="space-y-1 max-h-[500px] overflow-y-auto pr-1" x-data="{ progress: window.HiddenScan.getProgress() }">
                        @foreach($manga->chapters as $chapter)
                        <a href="{{ route('chapter.show', [$manga->slug, $chapter->slug]) }}"
                           class="group flex items-center justify-between px-4 py-3 rounded-lg hover:bg-panel-hi/70 transition-all relative"
                           :class="progress['{{ $manga->slug }}']?.chapter === {{ $chapter->number }} ? 'bg-violet/5 border border-violet/20' : ''">

                            <div class="flex items-center gap-3 min-w-0">
                                {{-- Indicateur de lecture --}}
                                <div class="w-1.5 h-1.5 rounded-full flex-shrink-0"
                                     :class="progress['{{ $manga->slug }}']?.chapter >= {{ $chapter->number }} ? 'bg-violet' : 'bg-line'">
                                </div>

                                <div class="min-w-0">
                                    <span class="text-sm font-medium text-chalk group-hover:text-violet transition">
                                        Chapitre {{ $chapter->number }}
                                        @if($chapter->title)
                                            <span class="text-mist font-normal">— {{ $chapter->title }}</span>
                                        @endif
                                    </span>
                                </div>
                            </div>

                            <div class="flex items-center gap-3 flex-shrink-0">
                                @if($chapter->published_at)
                                    <span class="text-xs text-mist/70 hidden sm:block">{{ $chapter->published_at->diffForHumans() }}</span>
                                @endif
                                <svg class="w-4 h-4 text-mist/40 group-hover:text-violet transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </div>
                        </a>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-12">
                        <svg class="w-12 h-12 mx-auto text-mist/30 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                        <p class="text-mist">Aucun chapitre disponible pour le moment.</p>
                    </div>
                @endif
            </div>

            {{-- Commentaires --}}
            <div class="bg-panel border border-line/50 rounded-xl p-5">
                <livewire:public.comment-section
                    type="App\Models\Manga"
                    :id="$manga->id"
                />
            </div>
        </div>
    </div>

</x-layouts.public>