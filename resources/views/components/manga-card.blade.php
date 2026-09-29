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
    $rating = $manga->average_rating > 0 ? number_format($manga->average_rating, 1) : '4.5';
@endphp

<div class="flex flex-col h-full group/card">
    {{-- Cover Container --}}
    <a href="{{ route('manga.show', $manga->slug) }}" class="relative block w-full rounded-2xl overflow-hidden aspect-[2/3] bg-panel border border-line/30 shadow-md">
        @if($manga->cover_image)
            <img src="{{ Storage::url($manga->cover_image) }}" alt="{{ $manga->title }}" class="w-full h-full object-cover transition-transform duration-300 group-hover/card:scale-105" loading="lazy">
        @else
            <div class="w-full h-full flex items-center justify-center p-4 text-center bg-panel-hi">
                <span class="text-mist text-xs font-medium">{{ $manga->title }}</span>
            </div>
        @endif
        
        {{-- Subtle dark overlay on hover --}}
        <div class="absolute inset-0 bg-black/20 opacity-0 group-hover/card:opacity-100 transition-opacity duration-300 pointer-events-none"></div>
        
        {{-- Top Left Badge: Flag + Type --}}
        <div class="absolute top-2 left-2 bg-black/60 backdrop-blur-md border border-white/10 rounded-md px-2 py-0.5 flex items-center gap-1.5 shadow-sm pointer-events-none">
            <span class="text-xs leading-none select-none">{{ $flag }}</span>
            <span class="text-[10px] font-bold text-white tracking-wider uppercase">{{ $typeName }}</span>
        </div>
        
        {{-- Bottom Right Badge: Rating --}}
        <div class="absolute bottom-2 right-2 bg-black/65 backdrop-blur-md border border-white/10 rounded-md px-1.5 py-0.5 flex items-center gap-1 shadow-sm pointer-events-none">
            <span class="text-[10px] text-amber-400 font-bold leading-none">✦</span>
            <span class="text-[11px] font-bold text-white leading-none">{{ $rating }}</span>
        </div>

        {{-- Optional Rank Badge --}}
        @if($showRank)
            <div class="absolute bottom-2 left-2 bg-black/75 backdrop-blur-md px-2 py-0.5 rounded-md text-[11px] font-extrabold text-amber-400 border border-amber-500/30 shadow-sm pointer-events-none">
                #{{ $showRank }}
            </div>
        @endif
    </a>
    
    {{-- Details & Chapters --}}
    <div class="mt-2.5 flex flex-col flex-1">
        {{-- Title --}}
        <a href="{{ route('manga.show', $manga->slug) }}" class="text-[13px] sm:text-[14px] font-bold text-chalk hover:text-violet transition truncate leading-tight block mb-2" title="{{ $manga->title }}">
            {{ $manga->title }}
        </a>
        
        {{-- Chapter Buttons (Vertical Stack of Split Pills) --}}
        @if($showChapters && $manga->chapters && $manga->chapters->isNotEmpty())
            @php
                $latestChapters = $manga->chapters->sortByDesc('number')->take(2);
            @endphp
            <div class="mt-auto flex flex-col gap-1.5">
                @foreach($latestChapters as $index => $chapter)
                    @php
                        $isRecent = $loop->first || ($chapter->published_at && $chapter->published_at->diffInDays() <= 3);
                        $dateStr = $chapter->published_at ? $chapter->published_at->format('d M') : ($chapter->created_at ? $chapter->created_at->format('d M') : '');
                    @endphp
                    <div class="flex items-center gap-1 sm:gap-1.5 w-full">
                        {{-- Main Chapter Capsule --}}
                        <a href="{{ route('chapter.show', [$manga->slug, $chapter->slug]) }}" 
                           class="flex-1 flex items-center justify-start px-2.5 sm:px-3.5 py-1 sm:py-1.5 bg-[#1e2029] hover:bg-[#2b2e3b] rounded-full transition-colors duration-200 group/chap min-w-0">
                            <span class="text-[11px] sm:text-[12px] font-medium text-[#d5d8e4] group-hover/chap:text-white truncate">
                                Chapitre {{ $chapter->number }}
                            </span>
                        </a>

                        {{-- Date or Flame Capsule --}}
                        @if($isRecent)
                            <a href="{{ route('chapter.show', [$manga->slug, $chapter->slug]) }}" 
                               class="w-6 h-6 sm:w-7 sm:h-7 rounded-full bg-[#1e2029] hover:bg-[#2b2e3b] flex items-center justify-center flex-shrink-0 text-[10px] sm:text-[11px] transition-colors duration-200" 
                               title="Nouveau">
                                🔥
                            </a>
                        @elseif($dateStr)
                            <a href="{{ route('chapter.show', [$manga->slug, $chapter->slug]) }}" 
                               class="px-2 sm:px-2.5 py-1 sm:py-1.5 bg-[#1e2029] hover:bg-[#2b2e3b] rounded-full flex items-center justify-center flex-shrink-0 text-[10px] sm:text-[11px] font-medium text-[#7d8498] hover:text-[#d5d8e4] transition-colors duration-200">
                                {{ $dateStr }}
                            </a>
                        @endif
                    </div>
                @endforeach
            </div>
        @elseif($showChapters)
            <div class="mt-auto pt-1">
                <span class="inline-flex items-center gap-1.5 text-[11px] font-medium text-[#7d8498] bg-[#1e2029] px-2.5 py-1 rounded-full border border-white/5">
                    <span class="w-1.5 h-1.5 rounded-full bg-amber-400/80"></span>
                    Bientôt disponible
                </span>
            </div>
        @endif
    </div>
</div>
