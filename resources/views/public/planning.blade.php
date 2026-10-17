@use('Illuminate\Support\Facades\Storage')
<x-layouts.public title="Planning des Sorties — Hidden Scan">

    <div class="mb-8" data-aos="fade-in" data-aos-duration="400">
        <h1 class="text-h1">Planning des Sorties</h1>
        <p class="text-caption text-[var(--text-2)] mt-0.5 max-w-2xl">
            Calendrier hebdomadaire des parutions traduites par l'équipe Hidden Scan (Heure de Paris).
        </p>
    </div>

    {{-- Sélecteur de Jours (Scrollable Pills) --}}
    <div x-data="{ activeDay: '{{ $todayKey }}' }" class="space-y-6">
        
        <div class="flex items-center gap-2 overflow-x-auto no-scrollbar pb-1">
            @foreach($schedule as $key => $day)
                <button type="button"
                        @click="activeDay = '{{ $key }}'"
                        class="genre-pill cursor-pointer"
                        :class="activeDay === '{{ $key }}' ? 'active' : ''">
                    <span>{{ $day['label'] }}</span>
                    @if($day['is_today'])
                        <span class="text-[9px] px-1 py-0.2 rounded-[2px] bg-white/20 font-bold uppercase">Auj.</span>
                    @else
                        <span class="text-[10px] text-[var(--text-3)]" :class="activeDay === '{{ $key }}' ? 'text-white/80' : ''">({{ count($day['items']) }})</span>
                    @endif
                </button>
            @endforeach
        </div>

        {{-- Cartes de Sorties par Jour --}}
        @foreach($schedule as $key => $day)
            <div x-show="activeDay === '{{ $key }}'" x-cloak class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                @forelse($day['items'] as $slot)
                    @php
                        $manga = $slot['manga'] ?? null;
                        $latestCh = $slot['latest_chapter'] ?? null;
                    @endphp
                    @if($manga)
                        <div class="p-4 bg-[var(--bg-surface)] border border-[var(--border)] rounded-[6px] hover:border-[var(--text-3)] transition flex items-center gap-4">
                            <a href="{{ route('manga.show', $manga->slug) }}" class="w-14 h-20 rounded-[4px] bg-[var(--bg-raised)] overflow-hidden flex-shrink-0 block">
                                @if($manga->cover_image)
                                    <img src="{{ Storage::url($manga->cover_image) }}" alt="{{ $manga->title }}" class="w-full h-full object-cover">
                                @endif
                            </a>
                            <div class="flex-1 min-w-0">
                                <div class="text-[11px] text-[var(--accent)] font-semibold mb-0.5">
                                    {{ $slot['time'] ?? '18h00' }} • {{ $slot['status_label'] ?? 'Sortie' }}
                                </div>
                                <h3 class="font-heading font-semibold text-[15px] text-[var(--text-1)] truncate">
                                    <a href="{{ route('manga.show', $manga->slug) }}" class="hover:text-[var(--accent)] transition">
                                        {{ $manga->title }}
                                    </a>
                                </h3>
                                @if($latestCh)
                                    <div class="text-caption text-[var(--text-2)] mt-1.5">
                                        <a href="{{ route('chapter.show', [$manga->slug, $latestCh->slug]) }}" class="hover:text-[var(--accent)] transition inline-flex items-center gap-1 font-medium">
                                            <span>Ch. {{ $latestCh->number }} disponible</span>
                                            <span>→</span>
                                        </a>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif
                @empty
                    <div class="col-span-full py-16 text-center bg-[var(--bg-surface)] border border-[var(--border)] rounded-[6px] text-caption text-[var(--text-3)]">
                        Aucun chapitre programmé pour ce jour.
                    </div>
                @endforelse
            </div>
        @endforeach

    </div>

</x-layouts.public>
