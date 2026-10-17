<x-layouts.public title="Classement des Lecteurs — Hidden Scan">
    <div class="max-w-[1140px] mx-auto py-6" data-aos="fade-in" data-aos-duration="400">
        
        {{-- En-tête --}}
        <div class="text-center mb-10">
            <h1 class="text-h1 mb-2">Classement des Lecteurs</h1>
            <p class="text-caption text-[var(--text-2)] max-w-xl mx-auto mb-4">
                Chaque chapitre lu nourrit votre progression martiale. Grimpez au sommet du classement et atteignez le pinacle.
            </p>
            @auth
                <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-[4px] bg-[var(--bg-raised)] border border-[var(--border)] text-caption">
                    @if(auth()->user()->isStaffOrAdmin())
                        <span class="text-amber-400 font-bold">🛡️ Staff / Admin</span>
                        <span class="text-[var(--text-3)]">(Hors classement)</span>
                    @elseif($currentUserRank)
                        <span class="text-[var(--text-2)]">Votre rang actuel :</span>
                        <span class="text-[var(--accent)] font-bold">#{{ $currentUserRank }}</span>
                    @else
                        <span class="text-[var(--text-2)]">Votre statut :</span>
                        <span class="text-[var(--text-3)] font-semibold">Non classé (0 chapitre lu)</span>
                    @endif
                </div>
            @endauth
        </div>

        @if($leaderboard['total_cultivators'] === 0)
            {{-- État vierge : aucun lecteur n'a encore marqué de points --}}
            <div class="bg-[var(--bg-surface)] border border-[var(--border)] rounded-[6px] p-12 text-center mb-12 shadow-sm">
                <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-[var(--bg-raised)] border border-[var(--border)] flex items-center justify-center text-3xl">
                    🥋
                </div>
                <h2 class="text-h2 font-bold text-[var(--text-1)] mb-2">Le Panthéon des Cultivateurs est encore vierge</h2>
                <p class="text-caption text-[var(--text-2)] max-w-md mx-auto mb-6">
                    Aucun lecteur n'est encore classé. Lisez vos premiers chapitres pour accumuler du Qi et être le premier à inscrire votre nom au sommet du classement martial !
                </p>
                <a href="{{ route('home') }}" class="btn btn-primary inline-flex items-center gap-2">
                    Explorer le catalogue
                </a>
            </div>
        @else
            {{-- Podium Top 3 --}}
            @if(count($podium) >= 3)
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-12 items-end">
                    {{-- #2 Second --}}
                    <div class="order-2 md:order-1 bg-[var(--bg-surface)] border border-[var(--border)] rounded-[6px] p-5 text-center shadow-sm">
                        <div class="w-8 h-8 mx-auto mb-2 rounded-[4px] bg-[var(--bg-raised)] border border-[var(--border)] text-caption font-bold text-[var(--text-2)] flex items-center justify-center">
                            #2
                        </div>
                        <img src="{{ $podium[1]['avatar_url'] }}" alt="{{ $podium[1]['name'] }}" class="w-16 h-16 mx-auto mb-2 rounded-[4px] object-cover bg-[var(--bg-base)]">
                        <h3 class="font-heading font-semibold text-[15px] text-[var(--text-1)] truncate mb-1">{{ $podium[1]['name'] }}</h3>
                        <div class="text-[11px] font-semibold text-[var(--text-2)] mb-3">
                            {{ $podium[1]['murim_badge'] }} {{ $podium[1]['murim_title'] }}
                        </div>
                        <div class="pt-3 border-t border-[var(--border)] flex justify-around text-caption">
                            <div>
                                <span class="text-[10px] text-[var(--text-3)] block uppercase">Chapitres</span>
                                <span class="font-bold text-[var(--text-1)]">{{ number_format($podium[1]['read_count']) }}</span>
                            </div>
                            <div>
                                <span class="text-[10px] text-[var(--text-3)] block uppercase">Points Qi</span>
                                <span class="font-bold text-[var(--accent)]">{{ number_format($podium[1]['xp']) }}</span>
                            </div>
                        </div>
                    </div>

                    {{-- #1 Grand Champion --}}
                    <div class="order-1 md:order-2 bg-[var(--bg-surface)] border border-[var(--accent)] rounded-[6px] p-6 text-center shadow-sm md:-translate-y-2">
                        <div class="w-8 h-8 mx-auto mb-2 rounded-[4px] bg-[var(--accent)] text-white text-caption font-bold flex items-center justify-center">
                            #1
                        </div>
                        <img src="{{ $podium[0]['avatar_url'] }}" alt="{{ $podium[0]['name'] }}" class="w-20 h-20 mx-auto mb-2 rounded-[4px] object-cover bg-[var(--bg-base)] border border-[var(--accent)]">
                        <h3 class="font-heading font-bold text-[17px] text-[var(--text-1)] truncate mb-1">{{ $podium[0]['name'] }}</h3>
                        <div class="text-[11px] font-semibold text-[var(--accent)] mb-3">
                            {{ $podium[0]['murim_badge'] }} {{ $podium[0]['murim_title'] }}
                        </div>
                        <div class="pt-3 border-t border-[var(--border)] flex justify-around text-caption">
                            <div>
                                <span class="text-[10px] text-[var(--text-3)] block uppercase">Chapitres</span>
                                <span class="font-bold text-[var(--text-1)]">{{ number_format($podium[0]['read_count']) }}</span>
                            </div>
                            <div>
                                <span class="text-[10px] text-[var(--text-3)] block uppercase">Points Qi</span>
                                <span class="font-bold text-[var(--accent)]">{{ number_format($podium[0]['xp']) }}</span>
                            </div>
                        </div>
                    </div>

                    {{-- #3 Troisième --}}
                    <div class="order-3 bg-[var(--bg-surface)] border border-[var(--border)] rounded-[6px] p-5 text-center shadow-sm">
                        <div class="w-8 h-8 mx-auto mb-2 rounded-[4px] bg-[var(--bg-raised)] border border-[var(--border)] text-caption font-bold text-[var(--text-2)] flex items-center justify-center">
                            #3
                        </div>
                        <img src="{{ $podium[2]['avatar_url'] }}" alt="{{ $podium[2]['name'] }}" class="w-16 h-16 mx-auto mb-2 rounded-[4px] object-cover bg-[var(--bg-base)]">
                        <h3 class="font-heading font-semibold text-[15px] text-[var(--text-1)] truncate mb-1">{{ $podium[2]['name'] }}</h3>
                        <div class="text-[11px] font-semibold text-[var(--text-2)] mb-3">
                            {{ $podium[2]['murim_badge'] }} {{ $podium[2]['murim_title'] }}
                        </div>
                        <div class="pt-3 border-t border-[var(--border)] flex justify-around text-caption">
                            <div>
                                <span class="text-[10px] text-[var(--text-3)] block uppercase">Chapitres</span>
                                <span class="font-bold text-[var(--text-1)]">{{ number_format($podium[2]['read_count']) }}</span>
                            </div>
                            <div>
                                <span class="text-[10px] text-[var(--text-3)] block uppercase">Points Qi</span>
                                <span class="font-bold text-[var(--accent)]">{{ number_format($podium[2]['xp']) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Tableau Général --}}
            @if(count($cultivators) > 0)
                <div class="bg-[var(--bg-surface)] border border-[var(--border)] rounded-[6px] overflow-hidden mb-12">
                    <div class="p-4 border-b border-[var(--border)] flex items-center justify-between">
                        <div>
                            <h2 class="text-h3 font-semibold text-[var(--text-1)]">Classement Général</h2>
                            <span class="text-caption text-[var(--text-3)]">Mis à jour automatiquement après chaque lecture</span>
                        </div>
                        @auth
                            @if(auth()->user()->isStaffOrAdmin())
                                <div class="px-3 py-1 rounded-[4px] bg-[var(--bg-raised)] border border-[var(--border)] text-caption text-[var(--text-2)] flex items-center gap-1.5">
                                    <span>🛡️</span> <span>Staff / Admin</span> <span class="text-[var(--text-3)]">(Hors classement)</span>
                                </div>
                            @elseif($currentUserRank)
                                <div class="px-3 py-1 rounded-[4px] bg-[var(--bg-raised)] border border-[var(--border)] text-caption font-semibold text-[var(--text-1)]">
                                    Votre rang : <span class="text-[var(--accent)] font-bold">#{{ $currentUserRank }}</span>
                                </div>
                            @else
                                <div class="px-3 py-1 rounded-[4px] bg-[var(--bg-raised)] border border-[var(--border)] text-caption text-[var(--text-2)]">
                                    Non classé <span class="text-[var(--text-3)]">(0 chapitre lu)</span>
                                </div>
                            @endif
                        @endauth
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-caption">
                            <thead>
                                <tr class="border-b border-[var(--border)] text-[11px] uppercase tracking-wider text-[var(--text-3)]">
                                    <th class="py-3 px-4">Rang</th>
                                    <th class="py-3 px-4">Lecteur</th>
                                    <th class="py-3 px-4">Titre Murim</th>
                                    <th class="py-3 px-4 text-center">Assiduité</th>
                                    <th class="py-3 px-4 text-right">Chapitres</th>
                                    <th class="py-3 px-4 text-right">Points Qi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[var(--border)]">
                                @foreach($cultivators as $c)
                                    <tr class="hover:bg-[var(--bg-raised)] transition {{ $c['is_current_user'] ? 'bg-[var(--bg-raised)]' : '' }}">
                                        <td class="py-3 px-4 font-mono font-semibold text-[var(--text-2)]">
                                            #{{ $c['rank'] }}
                                        </td>
                                        <td class="py-3 px-4">
                                            <div class="flex items-center gap-2.5">
                                                <img src="{{ $c['avatar_url'] }}" alt="{{ $c['name'] }}" class="w-7 h-7 rounded-[4px] object-cover bg-[var(--bg-base)]">
                                                <div class="font-semibold text-[var(--text-1)] truncate">{{ $c['name'] }}</div>
                                            </div>
                                        </td>
                                        <td class="py-3 px-4">
                                            <span class="text-caption text-[var(--text-2)]">
                                                {{ $c['murim_badge'] }} {{ $c['murim_title'] }}
                                            </span>
                                        </td>
                                        <td class="py-3 px-4 text-center">
                                            @if($c['streak'] > 0)
                                                <span class="text-[11px] text-amber-400 font-semibold">🔥 {{ $c['streak'] }}j</span>
                                            @else
                                                <span class="text-[var(--text-3)]">—</span>
                                            @endif
                                        </td>
                                        <td class="py-3 px-4 text-right font-semibold text-[var(--text-1)]">
                                            {{ number_format($c['read_count']) }}
                                        </td>
                                        <td class="py-3 px-4 text-right font-mono font-semibold text-[var(--accent)]">
                                            {{ number_format($c['xp']) }} XP
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        @endif

        {{-- Guide des 7 Royaumes Murim --}}
        <div class="bg-[var(--bg-surface)] border border-[var(--border)] rounded-[6px] p-6">
            <h2 class="text-h3 font-semibold text-[var(--text-1)] mb-4">Les 7 Paliers de Progression</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                @foreach($allRanks as $rank)
                    <div class="p-3.5 bg-[var(--bg-base)] border border-[var(--border)] rounded-[4px] flex flex-col justify-between">
                        <div>
                            <div class="text-xl mb-1">{{ $rank['badge'] }}</div>
                            <h3 class="font-heading font-semibold text-[14px] text-[var(--text-1)] mb-1">{{ $rank['title'] }}</h3>
                            <div class="text-caption text-[var(--accent)] font-semibold mb-2">
                                {{ $rank['min_chapters'] }} {{ (isset($rank['max_chapters']) && $rank['max_chapters'] < 9999) ? 'à ' . $rank['max_chapters'] : '+' }} chapitres
                            </div>
                            <p class="text-caption text-[var(--text-2)] line-clamp-2 mb-2">
                                {{ $rank['description'] }}
                            </p>
                        </div>
                        <div class="text-[11px] italic text-[var(--text-3)] pt-2 border-t border-[var(--border)]">
                            « {{ $rank['quote'] }} »
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

    </div>
</x-layouts.public>
