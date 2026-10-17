@props(['manga', 'showChapters' => false, 'showRank' => null])

@php
    $typeLabel = match($manga->type?->value ?? '') {
        'manhwa' => 'Manhwa',
        'manhua' => 'Manhua',
        'manga' => 'Manga',
        'comic' => 'Comic',
        'novel' => 'Novel',
        default => ucfirst($manga->type?->value ?? 'Manga'),
    };
    $primaryGenre = $manga->genres?->first()?->name ?? $typeLabel;
    $latestChapter = $manga->chapters?->sortByDesc('number')->first();
@endphp

<div class="manga-card group" data-aos="fade-up" data-aos-duration="400" data-aos-easing="ease-out-cubic">
    {{-- Container Couverture (Ratio 3:4 Strict) --}}
    <a href="{{ route('manga.show', $manga->slug) }}" class="cover-container block">
        @if($manga->cover_image)
            <img src="{{ Storage::url($manga->cover_image) }}" 
                 alt="{{ $manga->title }}" 
                 class="cover" 
                 loading="lazy">
        @else
            <div class="w-full h-full flex flex-col items-center justify-center p-4 text-center bg-[#141414] text-[var(--text-3)]">
                <span class="text-2xl mb-1">📖</span>
                <span class="text-caption line-clamp-2">{{ $manga->title }}</span>
            </div>
        @endif

        {{-- Permitted Card Overlay --}}
        <div class="card-overlay"></div>

        {{-- Top Right: Rank Badge si classement --}}
        @if($showRank)
            <div class="absolute top-2 right-2 px-2 py-0.5 rounded-[4px] bg-[#0a0a0a]/90 text-[var(--accent)] border border-[var(--border)] text-caption font-bold">
                #{{ $showRank }}
            </div>
        @endif

        {{-- Bottom Left: Genre Tag --}}
        <div class="absolute bottom-2 left-2 z-10">
            <span class="inline-block px-2 py-0.5 rounded-[4px] bg-[#0a0a0a]/85 border border-[var(--border)] text-[10px] font-semibold uppercase tracking-wider text-[var(--text-2)]">
                {{ $primaryGenre }}
            </span>
        </div>

        {{-- Slide-in Hover Badge (Top Left ou Bottom Right) --}}
        @if($latestChapter)
            <div class="card-badge absolute bottom-2 right-2 z-10">
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-[4px] bg-[var(--accent)] text-white text-[11px] font-bold">
                    <span>Ch. {{ $latestChapter->number }}</span>
                </span>
            </div>
        @endif
    </a>

    {{-- Informations du Manga --}}
    <div class="p-3 flex flex-col flex-1 justify-between gap-2">
        <a href="{{ route('manga.show', $manga->slug) }}" 
           class="font-heading font-semibold text-[14px] text-[var(--text-1)] hover:text-[var(--accent)] transition-colors line-clamp-2 leading-snug"
           title="{{ $manga->title }}">
            {{ $manga->title }}
        </a>

        @if($showChapters && $manga->chapters && $manga->chapters->isNotEmpty())
            @php
                $recentChapters = $manga->chapters->sortByDesc('number')->take(2);
            @endphp
            <div class="flex flex-col gap-1 pt-1 border-t border-[var(--border)]">
                @foreach($recentChapters as $ch)
                    <a href="{{ route('chapter.show', [$manga->slug, $ch->slug]) }}"
                       class="flex items-center justify-between text-caption hover:text-[var(--text-1)] transition-colors py-0.5">
                        <span class="truncate text-[var(--text-2)] hover:text-[var(--text-1)] font-medium">Ch. {{ $ch->number }}</span>
                        @if($ch->published_at)
                            <span class="text-[10px] text-[var(--text-3)]">{{ $ch->published_at->diffForHumans(null, true) }}</span>
                        @endif
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</div>
