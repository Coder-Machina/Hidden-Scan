@props(['manga', 'showChapters' => false, 'showRank' => null])

@php
    $flag = match($manga->type?->value ?? '') {
        'manhwa' => '🇰🇷',
        'manhua' => '🇨🇳',
        'manga' => '🇯🇵',
        'comic' => '🇺🇸',
        default => '📖',
    };
    $typeName = match($manga->type?->value ?? '') {
        'manhwa' => 'MANHWA',
        'manhua' => 'MANHUA',
        'manga' => 'MANGA',
        'comic' => 'COMIC',
        'novel' => 'NOVEL',
        default => strtoupper($manga->type?->getLabel() ?? 'MANGA'),
    };
    $rating = $manga->average_rating > 0 ? number_format($manga->average_rating, 1) : '4.8';
    $isInternalHs = !($manga->is_external ?? false);
@endphp

<div class="flex flex-col h-full group/card">
    {{-- Cover Poster Container --}}
    <a href="{{ route('manga.show', $manga->slug) }}" 
       class="relative block w-full rounded-2xl overflow-hidden aspect-[2/3] bg-panel border border-white/[0.08] group-hover/card:border-crimson/50 shadow-lg shadow-black/60 transition-all duration-300 group-hover/card:-translate-y-1 group-hover/card:shadow-xl group-hover/card:shadow-crimson/15">
        
        {{-- Cover Image --}}
        @if($manga->cover_image)
            <img src="{{ Storage::url($manga->cover_image) }}" 
                 alt="{{ $manga->title }}" 
                 class="w-full h-full object-cover transition-transform duration-500 ease-out group-hover/card:scale-105" 
                 loading="lazy">
        @else
            <div class="w-full h-full flex flex-col items-center justify-center p-4 text-center bg-gradient-to-b from-panel-hi to-ink">
                <span class="text-2xl mb-2">📖</span>
                <span class="text-mist text-xs font-semibold line-clamp-3">{{ $manga->title }}</span>
            </div>
        @endif
        
        {{-- Hover Ambient Vignette --}}
        <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-transparent to-black/30 opacity-60 group-hover/card:opacity-40 transition-opacity duration-300 pointer-events-none"></div>

        {{-- Top Left: Badge Identité (Projet Hidden Scan ou Type) --}}
        <div class="absolute top-2 left-2 flex flex-col gap-1 pointer-events-none z-10">
            @if($isInternalHs)
                <span class="badge-official-hs shadow-md">
                    <span>👑</span>
                    <span class="hidden sm:inline">HIDDEN SCAN</span>
                    <span class="sm:hidden">HS</span>
                </span>
            @else
                <span class="badge-type badge-{{ $manga->type?->value ?? 'manga' }} shadow-md">
                    <span class="text-[11px] leading-none">{{ $flag }}</span>
                    <span>{{ $typeName }}</span>
                </span>
            @endif
        </div>
        
        {{-- Top Right: Rating Pill --}}
        <div class="absolute top-2 right-2 bg-black/70 backdrop-blur-md border border-white/10 rounded-full px-2 py-0.5 flex items-center gap-1 shadow-md pointer-events-none z-10">
            <span class="text-[10px] text-amber-400 font-bold leading-none">★</span>
            <span class="text-[11px] font-extrabold text-white leading-none">{{ $rating }}</span>
        </div>

        {{-- Optional Rank Badge (Leaderboard) --}}
        @if($showRank)
            @php
                $rankBg = match($showRank) {
                    1 => 'bg-gradient-to-r from-amber-500 to-yellow-300 text-black border-amber-300 shadow-amber-500/40',
                    2 => 'bg-gradient-to-r from-slate-300 to-slate-100 text-black border-white shadow-slate-400/40',
                    3 => 'bg-gradient-to-r from-amber-700 to-amber-500 text-white border-amber-500 shadow-amber-700/40',
                    default => 'bg-black/80 text-white border-white/20',
                };
            @endphp
            <div class="absolute bottom-2.5 left-2.5 px-2 py-0.5 rounded-lg text-xs font-black border shadow-lg {{ $rankBg }} pointer-events-none z-10">
                #{{ $showRank }}
            </div>
        @endif
    </a>
    
    {{-- Details & Chapters --}}
    <div class="mt-2.5 flex flex-col flex-1">
        {{-- Title --}}
        <a href="{{ route('manga.show', $manga->slug) }}" 
           class="font-display text-[13.5px] sm:text-[14.5px] font-bold text-chalk hover:text-crimson transition-colors line-clamp-1 leading-snug block mb-2" 
           title="{{ $manga->title }}">
            {{ $manga->title }}
        </a>
        
        {{-- Chapter Pills --}}
        @if($showChapters && $manga->chapters && $manga->chapters->isNotEmpty())
            @php
                $latestChapters = $manga->chapters->sortByDesc('number')->take(2);
            @endphp
            <div class="mt-auto flex flex-col gap-1.5">
                @foreach($latestChapters as $chapter)
                    @php
                        $isRecent = $loop->first || ($chapter->published_at && $chapter->published_at->diffInDays() <= 3);
                        $dateStr = $chapter->published_at ? $chapter->published_at->diffForHumans(null, true) : ($chapter->created_at ? $chapter->created_at->diffForHumans(null, true) : '');
                    @endphp
                    <a href="{{ route('chapter.show', [$manga->slug, $chapter->slug]) }}" 
                       class="chapter-pill {{ $isRecent ? 'is-recent' : '' }} group/pill">
                        <span class="truncate group-hover/pill:text-white flex items-center gap-1.5 font-medium">
                            <span class="text-crimson text-[10px]">▶</span>
                            <span>Ch. {{ $chapter->number }}</span>
                        </span>
                        @if($isRecent)
                            <span class="text-[10px] font-extrabold text-crimson px-1.5 py-0.2 rounded bg-crimson/15 border border-crimson/30 flex-shrink-0">
                                NEW
                            </span>
                        @elseif($dateStr)
                            <span class="text-[10.5px] text-muted flex-shrink-0">
                                {{ $dateStr }}
                            </span>
                        @endif
                    </a>
                @endforeach
            </div>
        @elseif($showChapters)
            <div class="mt-auto pt-1">
                <span class="inline-flex items-center gap-1.5 text-[11px] font-medium text-muted bg-panel px-3 py-1 rounded-full border border-white/5">
                    <span class="w-1.5 h-1.5 rounded-full bg-amber-400/80 animate-pulse"></span>
                    À venir
                </span>
            </div>
        @endif
    </div>
</div>
