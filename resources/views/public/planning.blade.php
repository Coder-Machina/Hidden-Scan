@use('Illuminate\Support\Facades\Storage')
<x-layouts.public title="Planning des Sorties — Hidden Scan">

    {{-- ═══ Header Planning ═══ --}}
    <div class="mb-8 sm:mb-10 animate-fade-in">
        <div class="flex items-center gap-2 mb-2">
            <span class="w-2 h-4 rounded-full bg-crimson inline-block"></span>
            <span class="text-xs font-bold uppercase tracking-wider text-crimson">PROGRAMME DE LA SEMAINE</span>
        </div>
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="font-display font-black text-3xl sm:text-5xl tracking-tight text-chalk">
                    Planning des Sorties
                </h1>
                <p class="text-mist text-sm sm:text-base mt-1.5 max-w-2xl">
                    Retrouvez le calendrier hebdomadaire de publication des scans traduits par l'équipe Hidden Scan. Les horaires sont donnés à titre indicatif (Heure de Paris).
                </p>
            </div>

            {{-- Bouton Discord Notif --}}
            <a href="https://discord.gg/sMUeFSks4" target="_blank" rel="noopener noreferrer"
               class="inline-flex items-center gap-2.5 px-4 py-2.5 rounded-xl bg-[#5865F2]/15 hover:bg-[#5865F2]/25 border border-[#5865F2]/30 text-chalk text-xs sm:text-sm font-bold transition-all shadow-lg hover:scale-105 active:scale-95 flex-shrink-0 self-start sm:self-auto">
                <svg class="w-5 h-5 text-[#5865F2] flex-shrink-0" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M20.317 4.37a19.791 19.791 0 00-4.885-1.515.074.074 0 00-.079.037c-.21.375-.444.864-.608 1.25a18.27 18.27 0 00-5.487 0 12.64 12.64 0 00-.617-1.25.077.077 0 00-.079-.037A19.736 19.736 0 003.677 4.37a.07.07 0 00-.032.027C.533 9.046-.32 13.58.099 18.057a.082.082 0 00.031.057 19.9 19.9 0 005.993 3.03.078.078 0 00.084-.028c.462-.63.874-1.295 1.226-1.994.021-.041.001-.09-.041-.106a13.107 13.107 0 01-1.872-.892.077.077 0 01-.008-.128 10.2 10.2 0 00.372-.292.074.074 0 01.077-.01c3.929 1.793 8.18 1.793 12.061 0a.074.074 0 01.078.01c.12.098.246.198.373.292a.077.077 0 01-.006.127 12.299 12.299 0 01-1.873.894.077.077 0 00-.041.107c.36.698.772 1.362 1.225 1.993a.076.076 0 00.084.028 19.839 19.839 0 006.002-3.03.077.077 0 00.032-.054c.5-5.177-.838-9.674-3.549-13.66a.061.061 0 00-.031-.028zM8.02 15.33c-1.183 0-2.157-1.085-2.157-2.419 0-1.333.956-2.419 2.157-2.419 1.21 0 2.176 1.096 2.157 2.42 0 1.333-.956 2.418-2.157 2.418zm7.975 0c-1.183 0-2.157-1.085-2.157-2.419 0-1.333.955-2.419 2.157-2.419 1.21 0 2.176 1.096 2.157 2.42 0 1.333-.946 2.418-2.157 2.418z"/>
                </svg>
                <span>Être alerté sur Discord</span>
            </a>
        </div>
    </div>

    {{-- ═══ Container Interactif Alpine ═══ --}}
    <div x-data="{ activeDay: '{{ $todayKey }}' }" class="space-y-6">

        {{-- Barre des Jours de la Semaine (Mobile horizontal scrollable) --}}
        <div class="flex items-center gap-2 p-1.5 bg-[#0D1018] border border-white/[0.08] rounded-2xl overflow-x-auto hide-scrollbar shadow-xl">
            @foreach($schedule as $key => $day)
                <button type="button"
                        @click="activeDay = '{{ $key }}'"
                        class="flex-1 min-w-[90px] py-3 px-3 rounded-xl text-center transition-all duration-200 relative group flex flex-col items-center gap-1 cursor-pointer"
                        :class="activeDay === '{{ $key }}' 
                            ? 'bg-crimson text-white shadow-lg shadow-crimson/30 font-bold scale-[1.02]' 
                            : 'hover:bg-white/5 text-mist hover:text-chalk'">
                    
                    <span class="text-xs uppercase tracking-wider font-extrabold"
                          :class="activeDay === '{{ $key }}' ? 'text-white' : 'text-mist group-hover:text-chalk'">
                        {{ $day['label'] }}
                    </span>

                    {{-- Badge Aujourd'hui --}}
                    @if($day['is_today'])
                        <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full text-[9px] font-black uppercase tracking-wider"
                              :class="activeDay === '{{ $key }}' ? 'bg-white text-crimson' : 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30'">
                            <span class="w-1 h-1 rounded-full bg-current animate-pulse"></span>
                            Aujourd'hui
                        </span>
                    @else
                        <span class="text-[10px] opacity-70 font-semibold"
                              :class="activeDay === '{{ $key }}' ? 'text-white/80' : 'text-mist/60'">
                            {{ count($day['items']) }} sortie(s)
                        </span>
                    @endif
                </button>
            @endforeach
        </div>

        {{-- Contenu des Sorties par Jour --}}
        @foreach($schedule as $key => $day)
            <div x-show="activeDay === '{{ $key }}'" 
                 x-cloak 
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 translate-y-2"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 class="space-y-4">
                
                {{-- En-tête du jour actif --}}
                <div class="flex items-center justify-between py-2 border-b border-white/[0.06]">
                    <div class="flex items-center gap-2.5">
                        <h2 class="font-display font-extrabold text-xl text-chalk">
                            Sorties du {{ $day['label'] }}
                        </h2>
                        @if($day['is_today'])
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">
                                En cours aujourd'hui
                            </span>
                        @endif
                    </div>
                    <span class="text-xs text-mist font-semibold">
                        {{ count($day['items']) }} manga(s) au programme
                    </span>
                </div>

                {{-- Grille des Séries prévues --}}
                @if(count($day['items']) > 0)
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach($day['items'] as $slot)
                            @php
                                $manga = $slot['manga'];
                                $isOfficial = !($manga->is_external ?? false);
                                $latestCh = $slot['latest_chapter'];
                            @endphp
                            <div class="bg-[#0D1018] border border-white/[0.08] hover:border-crimson/50 rounded-2xl p-4 sm:p-5 transition-all duration-300 shadow-xl hover:-translate-y-1 hover:shadow-2xl hover:shadow-crimson/10 group flex gap-4 sm:gap-5 items-stretch relative overflow-hidden">
                                
                                {{-- Halo d'ambiance si officiel --}}
                                @if($isOfficial)
                                    <div class="absolute -right-12 -top-12 w-32 h-32 bg-crimson/10 rounded-full blur-2xl pointer-events-none"></div>
                                @endif

                                {{-- Cover Poster --}}
                                <a href="{{ route('manga.show', $manga->slug) }}" 
                                   class="relative w-20 sm:w-24 aspect-[2/3] rounded-xl overflow-hidden flex-shrink-0 bg-panel border border-white/10 group-hover:border-crimson/60 shadow-md">
                                    @if($manga->cover_image)
                                        <img src="{{ Storage::url($manga->cover_image) }}" 
                                             alt="{{ $manga->title }}" 
                                             class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                             loading="lazy">
                                    @else
                                        <div class="w-full h-full flex items-center justify-center p-2 text-center text-xs text-mist">
                                            📖
                                        </div>
                                    @endif
                                </a>

                                {{-- Détails --}}
                                <div class="flex flex-col justify-between flex-grow min-w-0">
                                    <div>
                                        {{-- Badges du haut --}}
                                        <div class="flex flex-wrap items-center gap-1.5 mb-2">
                                            @if($isOfficial)
                                                <span class="badge-official-hs !text-[9px] !py-0.5 !px-2">
                                                    👑 PROJET OFFICIEL
                                                </span>
                                            @else
                                                <span class="badge-type badge-{{ $manga->type?->value ?? 'manhwa' }} !text-[9px] !py-0.5 !px-2">
                                                    {{ strtoupper($manga->type?->getLabel() ?? 'MANHWA') }}
                                                </span>
                                            @endif

                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold bg-white/5 border border-white/10 text-mist">
                                                <svg class="w-3 h-3 text-crimson" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                {{ $slot['time'] }}
                                            </span>
                                        </div>

                                        {{-- Titre Manga --}}
                                        <a href="{{ route('manga.show', $manga->slug) }}" 
                                           class="font-display font-black text-base sm:text-lg text-chalk group-hover:text-crimson transition line-clamp-1 block">
                                            {{ $manga->title }}
                                        </a>

                                        {{-- Dernier chapitre ou teaser --}}
                                        <p class="text-xs text-mist mt-1 line-clamp-2 leading-relaxed">
                                            {{ $manga->synopsis ?? 'Nouvel épisode à paraître selon le cycle de parution hebdomadaire.' }}
                                        </p>
                                    </div>

                                    {{-- Actions du bas --}}
                                    <div class="flex items-center justify-between gap-2 pt-3 mt-3 border-t border-white/[0.04]">
                                        <div class="text-xs font-semibold text-mist">
                                            @if($latestCh)
                                                Dernier dispo : <span class="text-chalk font-bold">Ch. {{ $latestCh->number }}</span>
                                            @else
                                                <span class="text-amber-400 font-bold">À venir</span>
                                            @endif
                                        </div>

                                        <div class="flex items-center gap-2">
                                            @if($latestCh)
                                                <a href="{{ route('chapter.show', [$manga->slug, $latestCh->slug]) }}" 
                                                   class="btn-primary !py-1.5 !px-3 !text-xs">
                                                    <span>Lire Ch. {{ $latestCh->number }}</span>
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                                </a>
                                            @else
                                                <a href="{{ route('manga.show', $manga->slug) }}" 
                                                   class="btn-secondary !py-1.5 !px-3 !text-xs">
                                                    Voir la fiche
                                                </a>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-16 bg-[#0D1018] border border-white/[0.08] rounded-2xl p-6">
                        <span class="text-4xl block mb-2">☕</span>
                        <h3 class="font-display font-bold text-base text-chalk">Jour de repos pour l'équipe</h3>
                        <p class="text-xs text-mist mt-1 max-w-sm mx-auto">Aucune parution fixe prévue ce jour-là. Les traducteurs et cleaners préparent les chapitres de la semaine !</p>
                    </div>
                @endif
            </div>
        @endforeach

        {{-- Bannière Bas de page Discord --}}
        <div class="mt-12 bg-gradient-to-r from-crimson/20 via-[#0D1018] to-[#5865F2]/20 border border-white/10 rounded-2xl p-6 sm:p-8 flex flex-col sm:flex-row items-center justify-between gap-6 shadow-2xl relative overflow-hidden">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 rounded-2xl bg-[#5865F2]/20 border border-[#5865F2]/30 flex items-center justify-center flex-shrink-0 shadow-inner">
                    <svg class="w-8 h-8 text-[#5865F2]" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M20.317 4.37a19.791 19.791 0 00-4.885-1.515.074.074 0 00-.079.037c-.21.375-.444.864-.608 1.25a18.27 18.27 0 00-5.487 0 12.64 12.64 0 00-.617-1.25.077.077 0 00-.079-.037A19.736 19.736 0 003.677 4.37a.07.07 0 00-.032.027C.533 9.046-.32 13.58.099 18.057a.082.082 0 00.031.057 19.9 19.9 0 005.993 3.03.078.078 0 00.084-.028c.462-.63.874-1.295 1.226-1.994.021-.041.001-.09-.041-.106a13.107 13.107 0 01-1.872-.892.077.077 0 01-.008-.128 10.2 10.2 0 00.372-.292.074.074 0 01.077-.01c3.929 1.793 8.18 1.793 12.061 0a.074.074 0 01.078.01c.12.098.246.198.373.292a.077.077 0 01-.006.127 12.299 12.299 0 01-1.873.894.077.077 0 00-.041.107c.36.698.772 1.362 1.225 1.993a.076.076 0 00.084.028 19.839 19.839 0 006.002-3.03.077.077 0 00.032-.054c.5-5.177-.838-9.674-3.549-13.66a.061.061 0 00-.031-.028zM8.02 15.33c-1.183 0-2.157-1.085-2.157-2.419 0-1.333.956-2.419 2.157-2.419 1.21 0 2.176 1.096 2.157 2.42 0 1.333-.956 2.418-2.157 2.418zm7.975 0c-1.183 0-2.157-1.085-2.157-2.419 0-1.333.955-2.419 2.157-2.419 1.21 0 2.176 1.096 2.157 2.42 0 1.333-.946 2.418-2.157 2.418z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="font-display font-black text-lg sm:text-xl text-chalk">Envie d'un ping dès la sortie ?</h3>
                    <p class="text-xs sm:text-sm text-mist mt-0.5">Rejoignez le serveur Discord Hidden Scan pour recevoir une notification instantanée à chaque parution de chapitre.</p>
                </div>
            </div>
            <a href="https://discord.gg/sMUeFSks4" target="_blank" rel="noopener noreferrer"
               class="btn-primary !bg-[#5865F2] hover:!bg-[#4752C4] !py-3 !px-6 text-sm font-bold flex-shrink-0 w-full sm:w-auto text-center justify-center">
                Rejoindre le Discord Officiel
            </a>
        </div>

    </div>

</x-layouts.public>
