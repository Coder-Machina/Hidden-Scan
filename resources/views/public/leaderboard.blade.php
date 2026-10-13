<x-layouts.public title="Classement des Lecteurs — Hidden Scan">
    <div class="min-h-screen py-10 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto">
        {{-- En-tête / Hero --}}
        <div class="text-center mb-12 relative">
            <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-crimson/10 border border-crimson/30 text-crimson text-xs font-black uppercase tracking-widest mb-4">
                <span>⚡</span>
                <span>Voie Martiale & Élite des Ombres</span>
            </div>
            <h1 class="text-3xl sm:text-5xl font-display font-black tracking-tight text-white mb-4">
                LE PANTHÉON DES <span class="text-crimson drop-shadow-[0_0_15px_rgba(255,42,85,0.5)]">CULTIVATEURS</span>
            </h1>
            <p class="text-mist max-w-2xl mx-auto text-sm sm:text-base leading-relaxed">
                Chaque chapitre lu nourrit votre Dantian en Qi pur. Progressez à travers les 7 royaumes martiaux, grimpez au sommet du classement et gravez votre nom dans la légende.
            </p>
        </div>

        {{-- Podium Top 3 --}}
        @if(count($podium) >= 3)
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-16 items-end">
                {{-- #2 Second --}}
                <div class="order-2 md:order-1 bg-gradient-to-t from-panel via-panel/80 to-slate-800/30 border border-slate-700/50 rounded-3xl p-6 text-center relative overflow-hidden shadow-xl hover:border-slate-500/50 transition-all">
                    <div class="absolute -top-10 left-1/2 -translate-x-1/2 w-32 h-32 bg-slate-400/10 rounded-full blur-2xl pointer-events-none"></div>
                    <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-slate-400/20 text-slate-300 font-display font-black text-xl mb-4 border border-slate-400/30">
                        🥈 2
                    </div>
                    <div class="relative w-20 h-20 mx-auto mb-4 rounded-full p-1 bg-gradient-to-tr from-slate-400 to-slate-200">
                        <img src="{{ $podium[1]['avatar_url'] }}" alt="{{ $podium[1]['name'] }}" class="w-full h-full rounded-full object-cover">
                    </div>
                    <h3 class="font-display font-black text-lg text-white mb-1 truncate">{{ $podium[1]['name'] }}</h3>
                    <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold mb-4" style="background-color: {{ $podium[1]['murim_color'] }}20; color: {{ $podium[1]['murim_color'] }}; border: 1px solid {{ $podium[1]['murim_color'] }}40;">
                        <span>{{ $podium[1]['murim_badge'] }}</span>
                        <span>{{ $podium[1]['murim_title'] }}</span>
                    </div>
                    <div class="grid grid-cols-2 gap-2 pt-4 border-t border-white/5 text-left text-xs">
                        <div>
                            <span class="text-mist block text-[10px] uppercase font-bold">Chapitres</span>
                            <span class="text-white font-black text-base">{{ number_format($podium[1]['read_count']) }}</span>
                        </div>
                        <div>
                            <span class="text-mist block text-[10px] uppercase font-bold">Points de Qi</span>
                            <span class="text-cyan-400 font-black text-base">{{ number_format($podium[1]['xp']) }} XP</span>
                        </div>
                    </div>
                </div>

                {{-- #1 Grand Champion (Premier) --}}
                <div class="order-1 md:order-2 bg-gradient-to-t from-panel via-panel/90 to-amber-500/20 border-2 border-amber-500/50 rounded-3xl p-8 text-center relative overflow-hidden shadow-[0_0_35px_rgba(245,158,11,0.25)] hover:border-amber-400 transition-all md:-translate-y-4">
                    <div class="absolute -top-12 left-1/2 -translate-x-1/2 w-48 h-48 bg-amber-500/20 rounded-full blur-3xl pointer-events-none"></div>
                    <div class="inline-flex items-center gap-1.5 px-4 py-1.5 rounded-full bg-amber-500/20 text-amber-300 font-display font-black text-sm mb-4 border border-amber-400/40 animate-pulse">
                        👑 PREMIER DU JIANGHU
                    </div>
                    <div class="relative w-24 h-24 mx-auto mb-4 rounded-full p-1.5 bg-gradient-to-tr from-amber-500 via-yellow-300 to-amber-600 shadow-[0_0_25px_rgba(245,158,11,0.5)]">
                        <img src="{{ $podium[0]['avatar_url'] }}" alt="{{ $podium[0]['name'] }}" class="w-full h-full rounded-full object-cover">
                        <span class="absolute -bottom-2 -right-2 text-2xl">👑</span>
                    </div>
                    <h3 class="font-display font-black text-2xl text-white mb-1 truncate">{{ $podium[0]['name'] }}</h3>
                    <div class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-xs font-black mb-4 shadow-sm" style="background-color: {{ $podium[0]['murim_color'] }}25; color: {{ $podium[0]['murim_color'] }}; border: 1px solid {{ $podium[0]['murim_color'] }}60;">
                        <span>{{ $podium[0]['murim_badge'] }}</span>
                        <span>{{ $podium[0]['murim_title'] }}</span>
                        <span>🔥 {{ $podium[0]['streak'] }}j</span>
                    </div>
                    <div class="grid grid-cols-2 gap-3 pt-4 border-t border-amber-500/20 text-left text-xs bg-black/20 rounded-2xl p-3">
                        <div>
                            <span class="text-mist block text-[10px] uppercase font-bold">Chapitres Lus</span>
                            <span class="text-white font-black text-lg">{{ number_format($podium[0]['read_count']) }}</span>
                        </div>
                        <div>
                            <span class="text-mist block text-[10px] uppercase font-bold">Points de Qi</span>
                            <span class="text-amber-400 font-black text-lg">{{ number_format($podium[0]['xp']) }} XP</span>
                        </div>
                    </div>
                </div>

                {{-- #3 Troisième --}}
                <div class="order-3 bg-gradient-to-t from-panel via-panel/80 to-amber-900/30 border border-amber-800/50 rounded-3xl p-6 text-center relative overflow-hidden shadow-xl hover:border-amber-700/50 transition-all">
                    <div class="absolute -top-10 left-1/2 -translate-x-1/2 w-32 h-32 bg-amber-700/10 rounded-full blur-2xl pointer-events-none"></div>
                    <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-amber-700/20 text-amber-500 font-display font-black text-xl mb-4 border border-amber-700/30">
                        🥉 3
                    </div>
                    <div class="relative w-20 h-20 mx-auto mb-4 rounded-full p-1 bg-gradient-to-tr from-amber-700 to-amber-500">
                        <img src="{{ $podium[2]['avatar_url'] }}" alt="{{ $podium[2]['name'] }}" class="w-full h-full rounded-full object-cover">
                    </div>
                    <h3 class="font-display font-black text-lg text-white mb-1 truncate">{{ $podium[2]['name'] }}</h3>
                    <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold mb-4" style="background-color: {{ $podium[2]['murim_color'] }}20; color: {{ $podium[2]['murim_color'] }}; border: 1px solid {{ $podium[2]['murim_color'] }}40;">
                        <span>{{ $podium[2]['murim_badge'] }}</span>
                        <span>{{ $podium[2]['murim_title'] }}</span>
                    </div>
                    <div class="grid grid-cols-2 gap-2 pt-4 border-t border-white/5 text-left text-xs">
                        <div>
                            <span class="text-mist block text-[10px] uppercase font-bold">Chapitres</span>
                            <span class="text-white font-black text-base">{{ number_format($podium[2]['read_count']) }}</span>
                        </div>
                        <div>
                            <span class="text-mist block text-[10px] uppercase font-bold">Points de Qi</span>
                            <span class="text-cyan-400 font-black text-base">{{ number_format($podium[2]['xp']) }} XP</span>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- Tableau du Panthéon (#4 à fin) --}}
        <div class="bg-panel/90 border border-white/10 rounded-3xl p-6 sm:p-8 backdrop-blur-md shadow-2xl mb-16">
            <div class="flex items-center justify-between flex-wrap gap-4 mb-6">
                <div>
                    <h2 class="font-display font-black text-xl text-white">Classement Général des Disciples</h2>
                    <p class="text-xs text-mist mt-0.5">Mis à jour en temps réel selon les lectures de chapitres</p>
                </div>
                @auth
                    <div class="px-4 py-2 rounded-xl bg-crimson/15 border border-crimson/30 text-xs font-bold text-white flex items-center gap-2">
                        <span>🥋 Votre rang :</span>
                        <span class="text-crimson font-black text-sm">#{{ $currentUserRank }}</span>
                    </div>
                @endauth
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-white/10 text-[11px] font-black uppercase tracking-wider text-mist">
                            <th class="py-3 px-4">Rang</th>
                            <th class="py-3 px-4">Cultivateur</th>
                            <th class="py-3 px-4">Royaume Murim</th>
                            <th class="py-3 px-4 text-center">Assiduité</th>
                            <th class="py-3 px-4 text-right">Chapitres</th>
                            <th class="py-3 px-4 text-right">Points de Qi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5 text-sm">
                        @foreach($cultivators as $c)
                            <tr class="hover:bg-white/[0.03] transition-colors {{ $c['is_current_user'] ? 'bg-crimson/10 font-bold' : '' }}">
                                <td class="py-4 px-4 font-display font-black text-mist text-base">
                                    #{{ $c['rank'] }}
                                </td>
                                <td class="py-4 px-4">
                                    <div class="flex items-center gap-3">
                                        <img src="{{ $c['avatar_url'] }}" alt="{{ $c['name'] }}" class="w-9 h-9 rounded-full object-cover border border-white/10">
                                        <div class="min-w-0">
                                            <div class="font-bold text-white flex items-center gap-2 truncate">
                                                <span>{{ $c['name'] }}</span>
                                                @if($c['is_staff'] && $c['staff_badge'])
                                                    <span class="px-1.5 py-0.5 text-[9px] rounded font-black uppercase {{ $c['staff_badge']['bg'] }} {{ $c['staff_badge']['text'] }} border {{ $c['staff_badge']['border'] }}">
                                                        {{ $c['staff_badge']['name'] }}
                                                    </span>
                                                @endif
                                            </div>
                                            <span class="text-[11px] text-mist">{{ $c['murim_aura'] }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-4 px-4">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-bold" style="background-color: {{ $c['murim_color'] }}15; color: {{ $c['murim_color'] }}; border: 1px solid {{ $c['murim_color'] }}30;">
                                        <span>{{ $c['murim_badge'] }}</span>
                                        <span>{{ $c['murim_title'] }}</span>
                                    </span>
                                </td>
                                <td class="py-4 px-4 text-center">
                                    @if($c['streak'] > 0)
                                        <span class="inline-flex items-center gap-1 text-xs font-bold text-amber-400 bg-amber-500/10 px-2 py-0.5 rounded-full border border-amber-500/20">
                                            🔥 {{ $c['streak'] }}j
                                        </span>
                                    @else
                                        <span class="text-mist text-xs">—</span>
                                    @endif
                                </td>
                                <td class="py-4 px-4 text-right font-black text-white">
                                    {{ number_format($c['read_count']) }}
                                </td>
                                <td class="py-4 px-4 text-right font-black text-cyan-400">
                                    {{ number_format($c['xp']) }} XP
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Guide des 7 Royaumes Murim --}}
        <div class="bg-panel-hi/60 border border-white/10 rounded-3xl p-8 backdrop-blur-md">
            <div class="text-center max-w-xl mx-auto mb-10">
                <h2 class="font-display font-black text-2xl text-white mb-2">Les 7 Royaumes de Cultivation</h2>
                <p class="text-xs text-mist">Chaque palier franchi renforce vos méridiens et vous octroie de la notoriété dans la communauté.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                @foreach($allRanks as $rank)
                    <div class="p-5 rounded-2xl bg-ink-deep/60 border border-white/5 hover:border-white/20 transition-all flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-2xl">{{ $rank['badge'] }}</span>
                                <span class="text-[10px] font-black uppercase px-2 py-0.5 rounded" style="background-color: {{ $rank['color'] }}20; color: {{ $rank['color'] }};">
                                    {{ $rank['hangeul'] }}
                                </span>
                            </div>
                            <h3 class="font-display font-black text-sm text-white mb-1" style="color: {{ $rank['color'] }};">
                                {{ $rank['title'] }}
                            </h3>
                            <div class="text-[11px] text-amber-400 font-semibold mb-2">
                                📖 {{ $rank['min_chapters'] }} {{ (isset($rank['max_chapters']) && $rank['max_chapters'] < 9999) ? 'à ' . $rank['max_chapters'] : '+' }} chapitres
                            </div>
                            <p class="text-xs text-mist leading-relaxed mb-4">
                                {{ $rank['description'] }}
                            </p>
                        </div>
                        <div class="text-[10px] italic text-mist/80 pt-3 border-t border-white/5">
                            « {{ $rank['quote'] }} »
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-layouts.public>
