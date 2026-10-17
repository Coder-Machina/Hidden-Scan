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
    <a href="{{ route('manga.show', $manga->slug) }}" class="cover-container block relative">
        @if($manga->cover_image)
            <img src="{{ Storage::url($manga->cover_image) }}" 
                 alt="{{ $manga->title }}" 
                 class="cover" 
                 loading="lazy">
        @else
            <div class="w-full h-full flex flex-col items-center justify-center p-4 text-center bg-[#141414] text-[var(--text-3)]">
                <x-lucide name="book-open" class="w-10 h-10 text-[var(--text-3)] mb-2" />
                <span class="text-caption line-clamp-2 text-[var(--text-2)]">{{ $manga->title }}</span>
            </div>
        @endif

        {{-- Permitted Card Overlay --}}
        <div class="card-overlay"></div>

        {{-- Top Badges: Type & Rank/Rating --}}
        <div class="absolute top-2 inset-x-2 flex items-center justify-between gap-1 z-10 pointer-events-none">
            <span class="px-2 py-0.5 rounded-[4px] bg-black/75 backdrop-blur-sm border border-white/10 text-[9px] font-bold text-white uppercase tracking-wider">
                {{ $typeLabel }}
            </span>

            @if($showRank)
                <span class="px-2 py-0.5 rounded-[4px] text-[11px] font-black {{ $showRank <= 3 ? 'bg-[var(--accent)] text-white shadow-md' : 'bg-black/80 text-[var(--accent)] border border-[var(--accent)]/40' }}">
                    #{{ $showRank }}
                </span>
            @elseif($manga->average_rating > 0)
                <span class="px-1.5 py-0.5 rounded-[4px] bg-black/75 backdrop-blur-sm border border-white/10 text-[10px] font-bold text-amber-400 flex items-center gap-1">
                    <x-lucide name="star" class="w-3 h-3 fill-current text-amber-400" />
                    <span>{{ number_format($manga->average_rating, 1) }}</span>
                </span>
            @endif
        </div>

        {{-- Bottom Tags: Genre & Latest Chapter --}}
        <div class="absolute bottom-2 inset-x-2 flex items-center justify-between gap-1 z-10 pointer-events-none">
            <span class="px-2 py-0.5 rounded-[4px] bg-black/80 backdrop-blur-sm border border-white/10 text-[10px] font-semibold text-[#dedede] truncate max-w-[55%]">
                {{ $primaryGenre }}
            </span>

            @if($latestChapter)
                <span class="px-2 py-0.5 rounded-[4px] bg-[var(--accent)] text-white text-[10px] font-bold shadow-md tracking-tight flex-shrink-0 group-hover:bg-[var(--accent-hover)] transition-colors">
                    Ch. {{ $latestChapter->number }}
                </span>
            @endif
        </div>
    </a>

    {{-- Informations du Manga --}}
    <div class="p-3 flex flex-col flex-1 justify-between gap-2.5">
        <a href="{{ route('manga.show', $manga->slug) }}" 
           class="font-heading font-bold text-[14px] text-white hover:text-[var(--accent)] transition-colors line-clamp-2 leading-snug"
           title="{{ $manga->title }}">
            {{ $manga->title }}
        </a>

        @if($showChapters && $manga->chapters && $manga->chapters->isNotEmpty())
            @php
                $recentChapters = $manga->chapters->sortByDesc('number')->take(2);
            @endphp
            <div class="flex flex-col gap-1.5 pt-2 border-t border-[var(--border)]">
                @foreach($recentChapters as $ch)
                    <a href="{{ route('chapter.show', [$manga->slug, $ch->slug]) }}"
                       class="flex items-center justify-between px-2 py-1 rounded-[4px] bg-[var(--bg-base)] hover:bg-[var(--bg-raised)] border border-[var(--border)] hover:border-[var(--text-3)] text-xs transition group/ch">
                        <span class="truncate text-[var(--text-2)] group-hover/ch:text-white font-medium">Ch. {{ $ch->number }}</span>
                        @if($ch->published_at)
                            <span class="text-[10px] text-[var(--text-3)] flex-shrink-0 ml-1">{{ $ch->published_at->diffForHumans(null, true) }}</span>
                        @endif
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</div>
