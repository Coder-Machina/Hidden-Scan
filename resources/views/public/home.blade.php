@use('Illuminate\Support\Facades\Storage')
<x-layouts.public title="Hidden Scan — Lecture de mangas en ligne">

    {{-- ═══ Hero ═══ --}}
    <section class="relative mb-14 animate-fade-in">
        <div class="relative overflow-hidden rounded-2xl bg-panel border border-line/50">
            {{-- Background pattern --}}
            <div class="absolute inset-0 opacity-30">
                <div class="absolute inset-0" style="background-image: radial-gradient(circle, rgba(155,123,255,0.15) 1px, transparent 1px); background-size: 20px 20px;"></div>
                <div class="absolute inset-0 bg-gradient-to-r from-ink via-transparent to-ink"></div>
            </div>

            <div class="relative px-6 sm:px-10 py-12 sm:py-16 flex flex-col items-center text-center">
                <img src="{{ asset('images/logo.png') }}" alt="Hidden Scan" class="h-20 w-20 sm:h-24 sm:w-24 rounded-2xl mb-6 shadow-2xl shadow-violet/20" style="animation: float 3s ease-in-out infinite;">

                <h1 class="font-display font-extrabold text-4xl sm:text-5xl lg:text-6xl tracking-tight mb-4">
                    Hidden<span class="text-violet">Scan</span>
                </h1>
                <p class="text-mist text-lg sm:text-xl max-w-xl mb-8 leading-relaxed">
                    Lis tes <strong class="text-chalk">mangas</strong>, <strong class="text-chalk">manhwas</strong> et <strong class="text-chalk">manhuas</strong> en ligne. Gratuit, sans inscription.
                </p>
                <div class="flex flex-wrap items-center justify-center gap-3">
                    <a href="{{ route('manga.index') }}" class="btn-primary text-base px-8 py-3">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                        Explorer le catalogue
                    </a>
                    <a href="{{ route('library') }}" class="btn-secondary text-base px-8 py-3">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                        Ma bibliothèque
                    </a>
                </div>
            </div>
        </div>
    </section>

    {{-- ═══ Dernières sorties ═══ --}}
    @if($latest_chapters->count())
    <section class="mb-14 animate-fade-in-up" style="animation-delay: 0.1s;">
        <div class="flex items-center justify-between mb-6">
            <h2 class="section-title">
                <svg class="w-5 h-5 text-mint flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                Dernières sorties
            </h2>
            <a href="{{ route('manga.index', ['sort' => 'latest']) }}" class="text-sm text-violet hover:text-violet-glow transition flex items-center gap-1">
                Tout voir
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </a>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
            @foreach($latest_chapters as $index => $chapter)
            <a href="{{ route('chapter.show', [$chapter->manga->slug, $chapter->slug]) }}" class="manga-card group animate-fade-in stagger-{{ min($index + 1, 6) }}">
                <div class="relative overflow-hidden">
                    @if($chapter->manga->cover_image)
                        <img src="{{ Storage::url($chapter->manga->cover_image) }}" alt="{{ $chapter->manga->title }}" class="manga-cover" loading="lazy">
                    @else
                        <div class="cover-placeholder">
                            <span>{{ $chapter->manga->title }}</span>
                        </div>
                    @endif
                    <div class="manga-overlay"></div>

                    {{-- Badge chapitre --}}
                    <div class="absolute top-2 right-2">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-violet/90 text-white text-[10px] font-bold backdrop-blur-sm">
                            Ch. {{ $chapter->number }}
                        </span>
                    </div>

                    {{-- Badge type --}}
                    <div class="absolute top-2 left-2">
                        <span class="badge-type badge-{{ $chapter->manga->type }}">{{ ucfirst($chapter->manga->type) }}</span>
                    </div>
                </div>
                <div class="p-3">
                    <p class="text-sm font-semibold text-chalk truncate group-hover:text-violet transition">{{ $chapter->manga->title }}</p>
                    <p class="text-xs text-mist mt-0.5">Chapitre {{ $chapter->number }}</p>
                    @if($chapter->published_at)
                        <p class="text-[10px] text-mist/70 mt-1">{{ $chapter->published_at->diffForHumans() }}</p>
                    @endif
                </div>
            </a>
            @endforeach
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
            <a href="{{ route('manga.show', $manga->slug) }}" class="manga-card group animate-fade-in stagger-{{ min($index + 1, 6) }}">
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

                    {{-- Rang --}}
                    @if($index < 3)
                    <div class="absolute bottom-2 left-2">
                        <span class="inline-flex items-center justify-center w-7 h-7 rounded-full {{ $index === 0 ? 'bg-amber text-ink' : ($index === 1 ? 'bg-mist/30 text-chalk' : 'bg-amber/30 text-amber') }} text-xs font-extrabold">
                            {{ $index + 1 }}
                        </span>
                    </div>
                    @endif
                </div>
                <div class="p-3">
                    <p class="text-sm font-semibold text-chalk truncate group-hover:text-violet transition">{{ $manga->title }}</p>
                    <div class="flex items-center gap-2 mt-1">
                        <span class="text-xs text-mist flex items-center gap-1">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            {{ number_format($manga->views_count) }}
                        </span>
                        @if($manga->average_rating > 0)
                        <span class="text-xs text-amber flex items-center gap-0.5">
                            ★ {{ $manga->average_rating }}
                        </span>
                        @endif
                    </div>
                </div>
            </a>
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
            <a href="{{ route('manga.show', $manga->slug) }}" class="manga-card group animate-fade-in stagger-{{ min($index + 1, 6) }}">
                <div class="relative overflow-hidden">
                    @if($manga->cover_image)
                        <img src="{{ Storage::url($manga->cover_image) }}" alt="{{ $manga->title }}" class="manga-cover" loading="lazy">
                    @else
                        <div class="cover-placeholder">
                            <span>{{ $manga->title }}</span>
                        </div>
                    @endif
                    <div class="manga-overlay"></div>
                    <div class="absolute top-2 left-2">
                        <span class="badge-type badge-{{ $manga->type }}">{{ ucfirst($manga->type) }}</span>
                    </div>
                </div>
                <div class="p-3">
                    <p class="text-sm font-semibold text-chalk truncate group-hover:text-violet transition">{{ $manga->title }}</p>
                    <p class="text-xs text-mist mt-0.5">{{ $manga->chapters_count ?? $manga->chapters->count() }} chapitres</p>
                </div>
            </a>
            @endforeach
        </div>
    </section>
    @endif

</x-layouts.public>