@use('Illuminate\Support\Facades\Storage')
<x-layouts.public title="Bibliothèque & Mode Rattrapage — Hidden Scan">

    <div class="max-w-6xl mx-auto animate-fade-in" x-data="library()">

        {{-- ═══ Header ═══ --}}
        <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4 mb-6 sm:mb-8">
            <div>
                <h1 class="font-display font-extrabold text-3xl sm:text-4xl tracking-tight flex items-center gap-3 text-white">
                    <svg class="w-8 h-8 text-[#dc2626]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                    </svg>
                    Bibliothèque
                </h1>
                <p class="text-[#7070a0] mt-1 text-sm">Suivez vos lectures, retrouvez vos favoris et rattrapez tous vos chapitres en retard</p>
            </div>
        </div>

        {{-- ═══ Onglets de Navigation ═══ --}}
        <div class="flex gap-1.5 sm:gap-2 p-1.5 bg-[#111118] border border-[#1e1e2e] rounded-xl mb-6 sm:mb-8 overflow-x-auto hide-scrollbar">
            {{-- Onglet : Mode Rattrapage --}}
            <button @click="setTab('rattrapage')"
                    class="flex-1 min-w-max py-2 sm:py-2.5 px-3 sm:px-4 rounded-lg text-xs sm:text-sm font-semibold transition-all whitespace-nowrap flex items-center justify-center gap-1.5 sm:gap-2 group"
                    :class="activeTab === 'rattrapage' ? 'bg-[#dc2626] text-white shadow-lg shadow-[#dc2626]/30' : 'text-[#7070a0] hover:text-[#e8e8f0] hover:bg-white/5'">
                <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4 flex-shrink-0 text-amber-400 group-hover:scale-110 transition-transform" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clip-rule="evenodd"/>
                </svg>
                <span>Rattrapage</span>
                <span x-show="catchUpCount > 0"
                      x-text="catchUpCount"
                      class="px-1.5 py-0.5 rounded-full text-[10px] font-extrabold leading-none transition-colors"
                      :class="activeTab === 'rattrapage' ? 'bg-white text-[#dc2626]' : 'bg-[#dc2626] text-white animate-pulse'"></span>
            </button>

            {{-- Onglet : En cours --}}
            <button @click="setTab('progress')"
                    class="flex-1 min-w-max py-2 sm:py-2.5 px-3 sm:px-4 rounded-lg text-xs sm:text-sm font-semibold transition-all whitespace-nowrap flex items-center justify-center gap-1.5 sm:gap-2"
                    :class="activeTab === 'progress' ? 'bg-[#dc2626] text-white shadow-lg shadow-[#dc2626]/30' : 'text-[#7070a0] hover:text-[#e8e8f0] hover:bg-white/5'">
                <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                <span>En cours ({{ count($mangasWithProgress) }})</span>
            </button>

            {{-- Onglet : Favoris --}}
            <button @click="setTab('favorites')"
                    class="flex-1 min-w-max py-2 sm:py-2.5 px-3 sm:px-4 rounded-lg text-xs sm:text-sm font-semibold transition-all whitespace-nowrap flex items-center justify-center gap-1.5 sm:gap-2"
                    :class="activeTab === 'favorites' ? 'bg-[#dc2626] text-white shadow-lg shadow-[#dc2626]/30' : 'text-[#7070a0] hover:text-[#e8e8f0] hover:bg-white/5'">
                <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                <span>Favoris (<span x-text="favorites.length">0</span>)</span>
            </button>

            {{-- Onglet : Historique --}}
            <button @click="setTab('history')"
                    class="flex-1 min-w-max py-2 sm:py-2.5 px-3 sm:px-4 rounded-lg text-xs sm:text-sm font-semibold transition-all whitespace-nowrap flex items-center justify-center gap-1.5 sm:gap-2"
                    :class="activeTab === 'history' ? 'bg-[#dc2626] text-white shadow-lg shadow-[#dc2626]/30' : 'text-[#7070a0] hover:text-[#e8e8f0] hover:bg-white/5'">
                <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span>Historique (<span x-text="history.length">0</span>)</span>
            </button>

            {{-- Onglet : Recommandations "Si t'as aimé..." --}}
            <button @click="setTab('recommendations')"
                    class="flex-1 min-w-max py-2 sm:py-2.5 px-3 sm:px-4 rounded-lg text-xs sm:text-sm font-semibold transition-all whitespace-nowrap flex items-center justify-center gap-1.5 sm:gap-2 group"
                    :class="activeTab === 'recommendations' ? 'bg-[#dc2626] text-white shadow-lg shadow-[#dc2626]/30' : 'text-[#7070a0] hover:text-[#e8e8f0] hover:bg-white/5'">
                <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4 flex-shrink-0 text-amber-400 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                </svg>
                <span>Si t'as aimé...</span>
                <span class="text-[10px] px-1.5 py-0.5 rounded-full bg-amber-500/20 text-amber-300 font-extrabold border border-amber-500/30">POUR TOI</span>
            </button>
        </div>

        {{-- ══════════════════════════════════════════════════════════════ --}}
        {{-- ═══ Onglet 0 : MODE RATTRAPAGE                           ═══ --}}
        {{-- ══════════════════════════════════════════════════════════════ --}}
        <div x-show="activeTab === 'rattrapage'" x-cloak class="animate-fade-in-up">

            @guest
                {{-- Invitation de connexion pour les visiteurs non connectés --}}
                <div class="mb-6 bg-gradient-to-r from-[#1c1830] to-[#12121a] border border-[#3b3260] rounded-2xl p-4 sm:p-6 flex flex-col sm:flex-row items-center justify-between gap-4 shadow-xl">
                    <div class="flex items-center gap-3.5">
                        <div class="w-11 h-11 rounded-xl bg-[#dc2626]/20 border border-[#dc2626]/30 flex items-center justify-center text-amber-400 flex-shrink-0">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-white font-bold text-sm sm:text-base">Synchronisez votre Mode Rattrapage</h3>
                            <p class="text-xs text-[#9090b8] mt-0.5">Connectez-vous pour enregistrer votre progression en ligne et rattraper vos chapitres d'un coup sur tous vos appareils.</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 w-full sm:w-auto flex-shrink-0">
                        <a href="{{ route('login') }}" class="btn-primary text-xs py-2 px-4 w-full sm:w-auto text-center">Se connecter</a>
                        <a href="{{ route('register') }}" class="px-3.5 py-2 rounded-xl bg-white/10 hover:bg-white/15 text-white text-xs font-semibold transition text-center w-full sm:w-auto">Créer un compte</a>
                    </div>
                </div>
            @endguest

            <template x-if="catchUpCount > 0">
                <div class="space-y-4 sm:space-y-5">
                    {{-- ── Barre d'outils Rattrapage ── --}}
                    <div class="bg-[#111118] border border-[#1e1e2e] rounded-2xl p-3.5 sm:p-4 flex flex-col md:flex-row items-stretch md:items-center justify-between gap-3 sm:gap-4 shadow-xl">
                        {{-- Compteur & Résumé --}}
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-[#dc2626]/20 to-[#f59e0b]/20 border border-[#dc2626]/30 flex items-center justify-center text-amber-400 flex-shrink-0 shadow-inner">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div>
                                <div class="flex items-center gap-2 flex-wrap">
                                    <h2 class="text-white font-bold text-base sm:text-lg">Chapitres non lus</h2>
                                    <span class="px-2 py-0.5 rounded-full text-xs font-extrabold bg-[#dc2626] text-white" x-text="catchUpCount"></span>
                                </div>
                                <p class="text-xs text-[#7070a0]">
                                    Sur <span class="text-white font-semibold" x-text="seriesWithUnreadCount"></span> manga(s) en cours de lecture
                                </p>
                            </div>
                        </div>

                        {{-- Contrôles : Recherche, Tri, Vue --}}
                        <div class="flex flex-wrap items-center gap-2 sm:gap-3">
                            {{-- Champ de recherche par série --}}
                            <div class="relative flex-1 sm:w-48 md:w-56">
                                <svg class="w-3.5 h-3.5 absolute left-3 top-1/2 -translate-y-1/2 text-[#7070a0]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                                <input type="text"
                                       x-model="filterText"
                                       placeholder="Filtrer une série..."
                                       class="w-full bg-[#161622] border border-[#1e1e2e] focus:border-[#dc2626] rounded-xl pl-9 pr-3 py-1.5 text-xs text-white placeholder-[#7070a0] outline-none transition">
                            </div>

                            {{-- Tri chronologique --}}
                            <button @click="sortOrder = (sortOrder === 'desc' ? 'asc' : 'desc')"
                                    class="px-3 py-1.5 rounded-xl bg-[#161622] border border-[#1e1e2e] hover:border-white/20 text-[#a0a6b8] hover:text-white text-xs font-medium flex items-center gap-1.5 transition"
                                    :title="sortOrder === 'desc' ? 'Trié du plus récent au plus ancien' : 'Trié du plus ancien au plus récent'">
                                <svg class="w-3.5 h-3.5 text-[#dc2626]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h13M3 8h9m-9 4h6m4 0l4-4m0 0l4 4m-4-4v12"/>
                                </svg>
                                <span class="hidden sm:inline" x-text="sortOrder === 'desc' ? 'Récents' : 'Anciens'"></span>
                            </button>

                            {{-- Toggle d'affichage (Flux vs Par Série) --}}
                            <div class="flex bg-[#161622] border border-[#1e1e2e] rounded-xl p-0.5">
                                <button @click="catchUpView = 'feed'"
                                        class="px-2.5 sm:px-3 py-1.5 rounded-lg text-xs font-semibold transition flex items-center gap-1.5"
                                        :class="catchUpView === 'feed' ? 'bg-[#dc2626] text-white shadow' : 'text-[#7070a0] hover:text-white'">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                                    </svg>
                                    <span class="hidden sm:inline">Flux</span>
                                </button>
                                <button @click="catchUpView = 'series'"
                                        class="px-2.5 sm:px-3 py-1.5 rounded-lg text-xs font-semibold transition flex items-center gap-1.5"
                                        :class="catchUpView === 'series' ? 'bg-[#dc2626] text-white shadow' : 'text-[#7070a0] hover:text-white'">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                    </svg>
                                    <span class="hidden sm:inline">Par série</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- ── VUE 1 : FLUX CHRONOLOGIQUE DES CHAPITRES ── --}}
                    <div x-show="catchUpView === 'feed'" class="space-y-3">
                        <template x-for="item in filteredCatchUpChapters" :key="item.chapter_id">
                            <div class="bg-[#111118] border border-[#1e1e2e] hover:border-[#dc2626]/40 rounded-2xl p-3 sm:p-4 flex items-center justify-between gap-3 sm:gap-4 transition-all duration-200 shadow-md group">
                                {{-- Thumbnail + Titre Manga + Détails Chapitre --}}
                                <div class="flex items-center gap-3 sm:gap-4 min-w-0">
                                    <a :href="item.url" class="relative w-12 sm:w-14 aspect-[2/3] rounded-xl overflow-hidden bg-[#16161f] border border-white/5 flex-shrink-0 group-hover:scale-105 transition-transform duration-300">
                                        <template x-if="item.manga_cover">
                                            <img :src="item.manga_cover" :alt="item.manga_title" class="w-full h-full object-cover" loading="lazy">
                                        </template>
                                        <template x-if="!item.manga_cover">
                                            <div class="w-full h-full flex items-center justify-center p-1 text-[10px] text-[#7070a0] text-center">
                                                📖
                                            </div>
                                        </template>
                                    </a>

                                    <div class="min-w-0">
                                        {{-- Titre de l'œuvre & Badges --}}
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <a :href="'/manga/' + item.manga_slug" class="font-bold text-white hover:text-[#dc2626] transition text-sm sm:text-base truncate" x-text="item.manga_title"></a>
                                            <span class="text-[10px] font-bold uppercase tracking-wider text-[#a78bfa] bg-[#2a1a4a]/60 border border-[#4a2a8a]/40 px-1.5 py-0.5 rounded" x-text="item.manga_type"></span>
                                            <template x-if="item.is_recent">
                                                <span class="px-1.5 py-0.5 rounded text-[9px] font-extrabold uppercase bg-amber-500/20 text-amber-300 border border-amber-500/30">Nouveau</span>
                                            </template>
                                        </div>

                                        {{-- Chapitre --}}
                                        <div class="mt-1 flex items-center gap-2 flex-wrap">
                                            <a :href="item.url" class="text-xs sm:text-sm font-extrabold text-red-400 hover:text-white transition flex items-center gap-1 font-mono">
                                                <span>Chapitre</span>
                                                <span x-text="item.chapter_number"></span>
                                            </a>
                                            <template x-if="item.chapter_title">
                                                <span class="text-xs text-[#8080a8] truncate max-w-[180px] sm:max-w-xs" x-text="'— ' + item.chapter_title"></span>
                                            </template>
                                        </div>

                                        {{-- Date de publication --}}
                                        <div class="mt-1 text-[11px] text-[#7070a0] flex items-center gap-1.5">
                                            <svg class="w-3.5 h-3.5 text-[#50507a]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                            <span x-text="item.published_at_human || item.published_at_formatted"></span>
                                        </div>
                                    </div>
                                </div>

                                {{-- Boutons d'action : Marquer lu & Lire --}}
                                <div class="flex items-center gap-2 flex-shrink-0">
                                    <button @click="markAsRead(item)"
                                            :disabled="item._marking"
                                            class="p-2 sm:px-3 sm:py-2 rounded-xl bg-white/5 hover:bg-emerald-500/20 text-[#7070a0] hover:text-emerald-400 border border-white/5 hover:border-emerald-500/30 transition text-xs font-semibold flex items-center gap-1.5"
                                            title="Marquer comme lu">
                                        <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                        </svg>
                                        <span class="hidden md:inline">Marquer lu</span>
                                    </button>

                                    <a :href="item.url"
                                       class="py-2 px-3 sm:px-4 rounded-xl bg-[#dc2626] hover:bg-[#b91c1c] text-white text-xs sm:text-sm font-bold flex items-center gap-1.5 shadow-md shadow-[#dc2626]/20 transition-all active:scale-95">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        <span>Lire</span>
                                    </a>
                                </div>
                            </div>
                        </template>

                        <template x-if="filteredCatchUpChapters.length === 0">
                            <div class="text-center py-12 text-[#7070a0] text-sm bg-[#111118] border border-[#1e1e2e] rounded-2xl">
                                Aucun chapitre ne correspond à votre recherche.
                            </div>
                        </template>
                    </div>

                    {{-- ── VUE 2 : PAR SÉRIE ── --}}
                    <div x-show="catchUpView === 'series'" class="space-y-4">
                        <template x-for="mangaGroup in filteredCatchUpByManga" :key="mangaGroup.manga_id">
                            <div class="bg-[#111118] border border-[#1e1e2e] rounded-2xl p-4 sm:p-5 hover:border-[#dc2626]/30 transition shadow-lg">
                                {{-- Manga Header --}}
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-[#1e1e2e]">
                                    <div class="flex items-center gap-3.5 min-w-0">
                                        <a :href="'/manga/' + mangaGroup.manga_slug" class="w-12 h-16 sm:w-14 sm:h-20 rounded-xl overflow-hidden bg-[#16161f] border border-white/5 flex-shrink-0">
                                            <template x-if="mangaGroup.manga_cover">
                                                <img :src="mangaGroup.manga_cover" :alt="mangaGroup.manga_title" class="w-full h-full object-cover">
                                            </template>
                                            <template x-if="!mangaGroup.manga_cover">
                                                <div class="w-full h-full flex items-center justify-center text-xs text-[#7070a0]">📖</div>
                                            </template>
                                        </a>
                                        <div class="min-w-0">
                                            <a :href="'/manga/' + mangaGroup.manga_slug" class="block font-bold text-white hover:text-[#dc2626] transition text-base sm:text-lg truncate" x-text="mangaGroup.manga_title"></a>
                                            <div class="flex items-center gap-2 mt-1">
                                                <span class="text-[10px] font-bold uppercase tracking-wider text-[#a78bfa] bg-[#2a1a4a]/60 border border-[#4a2a8a]/40 px-1.5 py-0.5 rounded" x-text="mangaGroup.manga_type"></span>
                                                <span class="text-xs font-extrabold text-[#ef4444] bg-[#dc2626]/15 border border-[#dc2626]/30 px-2 py-0.5 rounded-full" x-text="mangaGroup.unread_count + ' non lu(s)'"></span>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Actions par série --}}
                                    <div class="flex items-center gap-2 self-end sm:self-center">
                                        <button @click="markSeriesAllRead(mangaGroup)"
                                                class="px-3 py-1.5 rounded-xl bg-white/5 hover:bg-emerald-500/20 text-[#a0a6b8] hover:text-emerald-300 border border-white/5 hover:border-emerald-500/30 text-xs font-semibold transition flex items-center gap-1.5">
                                            <svg class="w-3.5 h-3.5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                            <span>Tout marquer lu</span>
                                        </button>

                                        <template x-if="mangaGroup.earliest_unread">
                                            <a :href="mangaGroup.earliest_unread.url"
                                               class="py-1.5 px-3.5 rounded-xl bg-[#dc2626] hover:bg-[#b91c1c] text-white text-xs font-bold transition flex items-center gap-1.5 shadow-md shadow-[#dc2626]/20">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/></svg>
                                                <span>Reprendre (Ch. <span x-text="mangaGroup.earliest_unread.chapter_number"></span>)</span>
                                            </a>
                                        </template>
                                    </div>
                                </div>

                                {{-- Liste des chapitres en attente pour cette série --}}
                                <div class="mt-3.5">
                                    <div class="text-[11px] font-semibold uppercase tracking-wider text-[#7070a0] mb-2">Chapitres en attente :</div>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
                                        <template x-for="ch in mangaGroup.unread_chapters" :key="ch.chapter_id">
                                            <div class="bg-[#161622] border border-[#1e1e2e] hover:border-white/20 rounded-xl p-2.5 flex items-center justify-between gap-2 transition group/ch">
                                                <a :href="ch.url" class="min-w-0 flex-1 flex items-center gap-2">
                                                    <span class="text-xs font-bold text-red-400 font-mono group-hover/ch:text-white transition">Ch. <span x-text="ch.chapter_number"></span></span>
                                                    <span class="text-[11px] text-[#7070a0] truncate" x-text="ch.published_at_human || ch.published_at_formatted"></span>
                                                </a>
                                                <div class="flex items-center gap-1.5 flex-shrink-0">
                                                    <button @click="markAsRead(ch)"
                                                            :disabled="ch._marking"
                                                            class="p-1 rounded-lg bg-white/5 hover:bg-emerald-500/20 text-[#7070a0] hover:text-emerald-400 transition"
                                                            title="Marquer comme lu">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                                    </button>
                                                    <a :href="ch.url"
                                                       class="px-2 py-1 rounded-lg bg-[#dc2626]/20 hover:bg-[#dc2626] text-[#ef4444] hover:text-white text-[11px] font-bold transition">
                                                        Lire
                                                    </a>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </template>

            {{-- ── État vide : quand 100% à jour ── --}}
            <template x-if="catchUpCount === 0">
                <div class="text-center py-16 sm:py-20 bg-[#111118] border border-[#1e1e2e] rounded-2xl p-6 sm:p-10 relative overflow-hidden">
                    <div class="w-16 h-16 sm:w-20 sm:h-20 mx-auto rounded-2xl bg-emerald-500/10 border border-emerald-500/25 flex items-center justify-center mb-4 text-emerald-400 shadow-lg shadow-emerald-500/10">
                        <svg class="w-8 h-8 sm:w-10 sm:h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl sm:text-2xl font-bold text-white mb-2">Vous êtes à jour !</h3>
                    <p class="text-[#7070a0] mb-6 max-w-md mx-auto text-xs sm:text-sm">
                        Aucun chapitre non lu sur vos séries en cours. Dès qu'un nouveau chapitre sortira, vous le retrouverez directement ici sans avoir à fouiller manga par manga.
                    </p>
                    <div class="flex flex-wrap items-center justify-center gap-3">
                        <button @click="setTab('progress')" class="px-4 py-2.5 rounded-xl bg-white/10 hover:bg-white/15 text-white text-xs sm:text-sm font-semibold transition">
                            Voir mes lectures en cours
                        </button>
                        <a href="{{ route('manga.index') }}" class="btn-primary inline-flex items-center gap-2 text-xs sm:text-sm">
                            Explorer le catalogue
                        </a>
                    </div>
                </div>
            </template>
        </div>

        {{-- ══════════════════════════════════════════════════════════════ --}}
        {{-- ═══ Onglet 1 : PROGRESSION EN COURS                     ═══ --}}
        {{-- ══════════════════════════════════════════════════════════════ --}}
        <div x-show="activeTab === 'progress'" x-cloak class="animate-fade-in-up">
            @if(count($mangasWithProgress) > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                    @foreach($mangasWithProgress as $item)
                        @php
                            $manga = $item['manga'];
                            $readCount = $item['read_count'];
                            $totalChapters = $item['total_chapters'];
                            $percent = $item['percent'];
                            $resumeChapter = $item['resume_chapter'];
                        @endphp
                        <div class="bg-[#111118] border border-[#1e1e2e] rounded-2xl p-3 sm:p-4 flex gap-3 sm:gap-4 hover:border-[#dc2626]/40 transition-all duration-300 shadow-xl group">
                            {{-- Cover --}}
                            <a href="{{ route('manga.show', $manga->slug) }}" class="flex-shrink-0 w-20 sm:w-28 rounded-xl overflow-hidden aspect-[2/3] bg-[#16161f] relative border border-white/5 shadow-md">
                                @if($manga->cover_image)
                                    <img src="{{ Storage::url($manga->cover_image) }}" alt="{{ $manga->title }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" loading="lazy">
                                @else
                                    <div class="w-full h-full flex items-center justify-center p-2 text-center text-xs text-[#7070a0]">
                                        {{ $manga->title }}
                                    </div>
                                @endif
                            </a>

                            {{-- Info & Actions --}}
                            <div class="flex-1 flex flex-col justify-between min-w-0">
                                <div>
                                    <a href="{{ route('manga.show', $manga->slug) }}" class="block font-bold text-white text-base truncate group-hover:text-[#dc2626] transition">
                                        {{ $manga->title }}
                                    </a>
                                    
                                    {{-- Type badge --}}
                                    <span class="inline-block mt-1 text-[11px] font-semibold uppercase tracking-wider text-[#7070a0]">
                                        {{ $manga->type?->getLabel() ?? 'Œuvre' }}
                                    </span>

                                    {{-- Barre de progression --}}
                                    <div class="mt-3">
                                        <div class="flex items-center justify-between text-xs font-medium text-[#a0a6b8] mb-1.5">
                                            <span>{{ $readCount }}/{{ $totalChapters }} chapitres lus</span>
                                            <span class="text-white font-bold">{{ $percent }}%</span>
                                        </div>
                                        <div class="w-full bg-[#1b1c28] rounded-full h-2 overflow-hidden border border-white/5">
                                            <div class="bg-gradient-to-r from-[#dc2626] to-[#ef4444] h-2 rounded-full transition-all duration-300" style="width: {{ $percent }}%"></div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Bouton Reprendre --}}
                                <div class="mt-4 flex items-center gap-2">
                                    @if($resumeChapter)
                                        <a href="{{ route('chapter.show', [$manga->slug, $resumeChapter->slug]) }}"
                                           class="flex-1 py-2 px-3 rounded-xl bg-[#dc2626] hover:bg-[#b91c1c] text-white text-xs font-bold text-center transition-all shadow-md shadow-[#dc2626]/25 flex items-center justify-center gap-1.5">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            <span>{{ $percent >= 100 ? 'Relire' : 'Reprendre (Ch. ' . $resumeChapter->number . ')' }}</span>
                                        </a>
                                    @else
                                        <a href="{{ route('manga.show', $manga->slug) }}"
                                           class="flex-1 py-2 px-3 rounded-xl bg-white/10 hover:bg-white/15 text-white text-xs font-semibold text-center transition">
                                            Voir la fiche
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-20 bg-[#111118] border border-[#1e1e2e] rounded-2xl p-8">
                    <svg class="w-16 h-16 mx-auto text-[#7070a0]/30 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                    </svg>
                    <p class="text-lg text-white font-bold mb-2">Aucune lecture en cours</p>
                    <p class="text-[#7070a0] mb-6 max-w-md mx-auto text-sm">Cochez les chapitres que vous lisez sur la page d'une œuvre pour suivre automatiquement votre progression ici.</p>
                    <a href="{{ route('manga.index') }}" class="btn-primary inline-flex items-center gap-2">
                        Parcourir le catalogue
                    </a>
                </div>
            @endif
        </div>

        {{-- ══════════════════════════════════════════════════════════════ --}}
        {{-- ═══ Onglet 2 : FAVORIS                                  ═══ --}}
        {{-- ══════════════════════════════════════════════════════════════ --}}
        <div x-show="activeTab === 'favorites'" x-cloak class="animate-fade-in-up">
            <template x-if="favorites.length > 0">
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
                    <template x-for="manga in favorites" :key="manga.slug">
                        <div class="bg-[#111118] border border-[#1e1e2e] rounded-2xl overflow-hidden group relative hover:border-[#dc2626]/40 transition-all flex flex-col justify-between">
                            <a :href="'/manga/' + manga.slug" class="block">
                                <div class="relative overflow-hidden aspect-[2/3] bg-[#16161f]">
                                    <template x-if="manga.cover">
                                        <img :src="manga.cover" :alt="manga.title" class="w-full h-full object-cover group-hover:scale-105 transition duration-300" loading="lazy">
                                    </template>
                                    <template x-if="!manga.cover">
                                        <div class="w-full h-full flex items-center justify-center p-2 text-center text-xs text-[#7070a0]">
                                            <span x-text="manga.title"></span>
                                        </div>
                                    </template>
                                </div>
                                <div class="p-3">
                                    <p class="text-sm font-semibold text-[#e8e8f0] truncate group-hover:text-[#dc2626] transition" x-text="manga.title"></p>
                                    
                                    {{-- Progression si chargée --}}
                                    <template x-if="serverProgress[manga.slug]">
                                        <div class="mt-2">
                                            <div class="flex items-center justify-between text-[10px] text-[#7070a0] mb-1 font-medium">
                                                <span x-text="serverProgress[manga.slug].read_count + '/' + serverProgress[manga.slug].total_chapters + ' lus'"></span>
                                                <span x-text="serverProgress[manga.slug].percent + '%'"></span>
                                            </div>
                                            <div class="w-full bg-[#1b1c28] rounded-full h-1.5 overflow-hidden">
                                                <div class="bg-[#dc2626] h-1.5 rounded-full" :style="'width: ' + serverProgress[manga.slug].percent + '%'"></div>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </a>

                            {{-- Bouton Reprendre & Supprimer --}}
                            <div class="p-3 pt-0 flex gap-2">
                                <template x-if="serverProgress[manga.slug] && serverProgress[manga.slug].next_chapter">
                                    <a :href="serverProgress[manga.slug].next_chapter.url"
                                       class="flex-1 py-1.5 rounded-lg bg-[#dc2626] hover:bg-[#b91c1c] text-white text-[11px] font-bold text-center transition">
                                        Reprendre
                                    </a>
                                </template>
                                <template x-if="!serverProgress[manga.slug] || !serverProgress[manga.slug].next_chapter">
                                    <a :href="'/manga/' + manga.slug"
                                       class="flex-1 py-1.5 rounded-lg bg-white/10 hover:bg-white/15 text-white text-[11px] font-semibold text-center transition">
                                        Lire
                                    </a>
                                </template>
                            </div>

                            <button @click.prevent="removeFavorite(manga.slug)"
                                    class="absolute top-2 right-2 p-1.5 rounded-md bg-[#08080d]/80 text-[#7070a0] hover:text-[#ef4444] hover:bg-[#ef4444]/20 backdrop-blur-sm transition opacity-0 group-hover:opacity-100"
                                    title="Retirer des favoris">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </div>
                    </template>
                </div>
            </template>
            <template x-if="favorites.length === 0">
                <div class="text-center py-20 bg-[#111118] border border-[#1e1e2e] rounded-2xl p-8">
                    <svg class="w-16 h-16 mx-auto text-[#7070a0]/30 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                    <p class="text-lg text-white font-bold mb-2">Aucun favori pour le moment</p>
                    <p class="text-[#7070a0] mb-6 text-sm">Ajoutez des séries à vos favoris pour les retrouver facilement ici.</p>
                    <a href="{{ route('manga.index') }}" class="btn-primary">Parcourir le catalogue</a>
                </div>
            </template>
        </div>

        {{-- ══════════════════════════════════════════════════════════════ --}}
        {{-- ═══ Onglet 3 : HISTORIQUE                                ═══ --}}
        {{-- ══════════════════════════════════════════════════════════════ --}}
        <div x-show="activeTab === 'history'" x-cloak class="animate-fade-in-up">
            <template x-if="history.length > 0">
                <div class="space-y-3">
                    <div class="flex justify-end mb-2">
                        <button @click="clearHistory()" class="text-xs text-[#7070a0] hover:text-[#ef4444] transition flex items-center gap-1 font-medium">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            Effacer l'historique
                        </button>
                    </div>

                    <div class="bg-[#111118] border border-[#1e1e2e] rounded-2xl overflow-hidden divide-y divide-[#1e1e2e]">
                        <template x-for="item in historyDetails" :key="item.slug + item.chapter">
                            <div class="p-4 flex items-center justify-between gap-4 hover:bg-[#16161f] transition">
                                <div class="flex items-center gap-4 min-w-0">
                                    <template x-if="item.cover">
                                        <img :src="item.cover" class="w-12 h-16 object-cover rounded-xl flex-shrink-0 border border-white/5" alt="">
                                    </template>
                                    <template x-if="!item.cover">
                                        <div class="w-12 h-16 bg-[#16161f] border border-[#1e1e2e] rounded-xl flex-shrink-0 flex items-center justify-center text-xs text-[#7070a0]">
                                            📖
                                        </div>
                                    </template>

                                    <div class="min-w-0">
                                        <a :href="'/manga/' + item.slug" class="block font-bold text-white hover:text-[#dc2626] transition truncate text-sm" x-text="item.title || item.slug"></a>
                                        <div class="flex items-center gap-2 mt-1">
                                            <a :href="'/manga/' + item.slug + '/chapter/' + item.chapterSlug" class="text-xs text-[#a0a6b8] hover:text-white transition font-medium">
                                                Chapitre <span x-text="item.chapter"></span>
                                            </a>
                                            <span class="text-xs text-[#7070a0]" x-text="formatDate(item.readAt)"></span>
                                        </div>
                                    </div>
                                </div>

                                <a :href="'/manga/' + item.slug + '/chapter/' + item.chapterSlug"
                                   class="px-3 py-1.5 rounded-lg bg-white/5 hover:bg-[#dc2626] hover:text-white text-[#a0a6b8] text-xs font-semibold transition flex-shrink-0">
                                    Relire
                                </a>
                            </div>
                        </template>
                    </div>
                </div>
            </template>
            <template x-if="history.length === 0">
                <div class="text-center py-20 bg-[#111118] border border-[#1e1e2e] rounded-2xl p-8">
                    <svg class="w-16 h-16 mx-auto text-[#7070a0]/30 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <p class="text-lg text-white font-bold mb-2">Aucun historique de lecture</p>
                    <p class="text-[#7070a0] mb-6 text-sm">Vos dernières lectures apparaîtront ici.</p>
                </div>
            </template>
        </div>

        {{-- ══════════════════════════════════════════════════════════════ --}}
        {{-- ═══ Onglet 4 : RECOMMANDATIONS "SI T'AS AIMÉ..."         ═══ --}}
        {{-- ══════════════════════════════════════════════════════════════ --}}
        <div x-show="activeTab === 'recommendations'" x-cloak class="animate-fade-in-up">

            {{-- Bannière explicative --}}
            <div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-gradient-to-r from-[#1c162b] via-[#12111c] to-[#1a1215] border border-amber-500/20 rounded-2xl p-4 sm:p-5 shadow-xl">
                <div class="flex items-center gap-3.5">
                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-amber-500/20 to-[#dc2626]/20 border border-amber-500/30 flex items-center justify-center text-amber-400 flex-shrink-0 shadow-inner">
                        <svg class="w-6 h-6 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                        </svg>
                    </div>
                    <div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <h2 class="text-white font-display font-extrabold text-lg sm:text-xl">Si t'as aimé X, lis Y</h2>
                            <span class="text-[10px] px-2 py-0.5 rounded-full bg-amber-500/20 text-amber-300 font-extrabold border border-amber-500/30 tracking-wider uppercase">Algorithme d'affinité</span>
                        </div>
                        <p class="text-xs sm:text-sm text-[#9090b8] mt-0.5">Recommandations générées sur-mesure à partir des séries cochées comme lues dans votre bibliothèque.</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 flex-shrink-0">
                    <a href="{{ route('manga.random') }}" class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-[#dc2626]/10 hover:bg-[#dc2626]/20 border border-[#dc2626]/30 text-white text-xs font-semibold transition group">
                        <span class="group-hover:rotate-12 transition-transform">🎲</span>
                        <span>Surprise-moi</span>
                    </a>
                </div>
            </div>

            {{-- Chargement dynamique (notamment pour les invités) --}}
            <template x-if="loadingRecommendations">
                <div class="bg-[#111118] border border-[#1e1e2e] rounded-2xl p-12 text-center">
                    <div class="inline-block w-8 h-8 border-2 border-amber-400 border-t-transparent rounded-full animate-spin mb-3"></div>
                    <p class="text-white font-bold text-sm">Calcul de vos affinités personnalisées...</p>
                    <p class="text-xs text-[#7070a0] mt-1">Analyse des auteurs, illustrateurs et genres en cours</p>
                </div>
            </template>

            {{-- Liste des groupes de recommandations --}}
            <template x-if="!loadingRecommendations && recommendations.length > 0">
                <div class="space-y-6 sm:space-y-8">
                    <template x-for="group in recommendations" :key="group.source_manga.id">
                        <div class="bg-[#111118] border border-[#1e1e2e] hover:border-amber-500/30 rounded-2xl p-4 sm:p-6 shadow-xl transition-all duration-300">
                            
                            {{-- En-tête de la source --}}
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 mb-5 border-b border-[#1e1e2e]">
                                <div class="flex items-center gap-3.5 min-w-0">
                                    <a :href="group.source_manga.url" class="relative w-12 sm:w-14 aspect-[2/3] rounded-xl overflow-hidden bg-[#16161f] border border-white/10 flex-shrink-0 hover:scale-105 transition-transform duration-300 shadow-md">
                                        <template x-if="group.source_manga.cover_image">
                                            <img :src="group.source_manga.cover_image" :alt="group.source_manga.title" class="w-full h-full object-cover">
                                        </template>
                                        <template x-if="!group.source_manga.cover_image">
                                            <div class="w-full h-full flex items-center justify-center text-xs text-[#7070a0]">📖</div>
                                        </template>
                                    </a>
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <span class="text-[10px] font-extrabold uppercase tracking-wider text-amber-400 bg-amber-400/10 border border-amber-400/20 px-2 py-0.5 rounded-full flex items-center gap-1">
                                                <span>🎯</span> Lu dans ta bibliothèque
                                            </span>
                                            <span class="text-[10px] font-bold uppercase tracking-wider text-[#a78bfa] bg-[#2a1a4a]/60 border border-[#4a2a8a]/40 px-1.5 py-0.5 rounded" x-text="group.source_manga.type"></span>
                                        </div>
                                        <a :href="group.source_manga.url" class="font-display font-extrabold text-white hover:text-[#dc2626] transition text-base sm:text-xl block mt-1 truncate">
                                            <span class="text-[#7070a0] font-normal text-sm sm:text-base">Si t'as aimé</span> <span class="text-white" x-text="group.source_manga.title"></span>...
                                        </a>
                                    </div>
                                </div>

                                <div class="flex items-center gap-2 self-start sm:self-center flex-shrink-0">
                                    <span class="text-xs text-[#9090b8] bg-[#161622] px-3 py-1.5 rounded-xl border border-white/5 flex items-center gap-1.5 font-medium">
                                        <span>👉</span> Tu devrais adorer :
                                    </span>
                                </div>
                            </div>

                            {{-- Grille des mangas recommandés Y --}}
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                                <template x-for="item in group.recommendations" :key="item.id">
                                    <div class="bg-[#151520] border border-[#1e1e2e] hover:border-[#dc2626]/50 rounded-xl p-3 flex flex-col justify-between transition-all duration-300 hover:-translate-y-1 hover:shadow-2xl hover:shadow-[#dc2626]/10 group/card relative">
                                        <div>
                                            {{-- Cover + Badges --}}
                                            <div class="relative w-full aspect-[3/4] rounded-lg overflow-hidden bg-[#1a1a26] border border-white/5 mb-3 group/cover">
                                                <template x-if="item.cover_image">
                                                    <img :src="item.cover_image" :alt="item.title" class="w-full h-full object-cover group-hover/card:scale-105 transition-transform duration-500" loading="lazy">
                                                </template>
                                                <template x-if="!item.cover_image">
                                                    <div class="w-full h-full flex items-center justify-center text-[#7070a0] text-sm">📖</div>
                                                </template>

                                                <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-transparent to-black/30 pointer-events-none"></div>

                                                {{-- Badges flottants --}}
                                                <div class="absolute top-2 left-2 right-2 flex items-center justify-between gap-1 pointer-events-none">
                                                    <span class="text-[9px] font-black uppercase tracking-wider text-white bg-black/70 backdrop-blur-md border border-white/10 px-1.5 py-0.5 rounded shadow" x-text="item.type"></span>
                                                    <span class="text-[9px] font-black tracking-wide text-emerald-300 bg-emerald-950/80 backdrop-blur-md border border-emerald-500/40 px-2 py-0.5 rounded-full shadow flex items-center gap-1">
                                                        <span>⚡</span> <span x-text="item.match_percent + '%'"></span>
                                                    </span>
                                                </div>

                                                {{-- Infos bas de jaquette --}}
                                                <div class="absolute bottom-2 left-2 right-2 flex items-center justify-between text-[10px] text-white/90 pointer-events-none">
                                                    <span class="font-medium bg-black/60 backdrop-blur-sm px-1.5 py-0.5 rounded border border-white/10" x-text="item.chapters_count + ' ch.'"></span>
                                                    <template x-if="item.rating">
                                                        <span class="flex items-center gap-0.5 font-bold text-amber-400 bg-black/60 backdrop-blur-sm px-1.5 py-0.5 rounded border border-white/10">
                                                            ★ <span x-text="item.rating"></span>
                                                        </span>
                                                    </template>
                                                </div>
                                            </div>

                                            {{-- Titre --}}
                                            <h4 class="font-bold text-white group-hover/card:text-[#dc2626] transition text-sm line-clamp-1 mb-1.5" :title="item.title">
                                                <a :href="item.url" x-text="item.title"></a>
                                            </h4>

                                            {{-- Raison de la reco --}}
                                            <div class="min-h-[38px] mb-3">
                                                <p class="text-[11px] text-amber-300/90 font-medium leading-snug line-clamp-2 bg-amber-500/10 border border-amber-500/20 rounded-md px-2 py-1 flex items-start gap-1">
                                                    <span class="text-amber-400 text-xs mt-0.5 flex-shrink-0">💡</span>
                                                    <span x-text="item.reason"></span>
                                                </p>
                                            </div>
                                        </div>

                                        {{-- Actions --}}
                                        <div class="pt-2 border-t border-[#1e1e2e] flex items-center gap-2">
                                            <a :href="item.first_chapter_url" class="flex-1 py-2 px-2.5 rounded-lg bg-[#dc2626] hover:bg-[#b91c1c] text-white text-xs font-bold transition flex items-center justify-center gap-1 text-center shadow-md shadow-[#dc2626]/20">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                                                <span>Lire Ch. 1</span>
                                            </a>
                                            <a :href="item.url" class="p-2 rounded-lg bg-white/5 hover:bg-white/10 text-[#a0a6b8] hover:text-white transition" title="Fiche de la série">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            </a>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </template>

            {{-- État vide --}}
            <template x-if="!loadingRecommendations && recommendations.length === 0">
                <div class="text-center py-16 sm:py-20 bg-[#111118] border border-[#1e1e2e] rounded-2xl p-6 sm:p-8">
                    <div class="w-16 h-16 mx-auto rounded-2xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400 mb-4 shadow-inner">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                        </svg>
                    </div>
                    <h3 class="text-lg text-white font-bold mb-2">Aucune recommandation disponible pour le moment</h3>
                    <p class="text-[#7070a0] max-w-lg mx-auto mb-6 text-xs sm:text-sm">
                        Pour que l'algorithme "Si t'as aimé X, lis Y" puisse analyser vos préférences, commencez par cocher des chapitres comme lus dans le lecteur ou ajoutez des mangas à vos favoris !
                    </p>
                    <div class="flex flex-wrap items-center justify-center gap-3">
                        <a href="{{ route('manga.index') }}" class="btn-primary text-xs py-2 px-5">
                            Explorer le catalogue
                        </a>
                        <a href="{{ route('manga.random') }}" class="px-4 py-2 rounded-xl bg-white/10 hover:bg-white/15 text-white text-xs font-semibold transition flex items-center gap-2">
                            <span>🎲</span> Surprise-moi
                        </a>
                    </div>
                </div>
            </template>
        </div>

    </div>

    <script>
    function library() {
        const urlParams = new URLSearchParams(window.location.search);
        const defaultTab = urlParams.get('tab') || '{{ $unreadTotal > 0 ? "rattrapage" : (count($mangasWithProgress) > 0 ? "progress" : "favorites") }}';

        return {
            activeTab: defaultTab,
            favorites: [],
            history: [],
            serverProgress: {},
            historyDetails: [],

            // Mode Rattrapage state
            catchUpChapters: @json($catchUpChapters),
            catchUpByManga: @json($catchUpByManga),
            catchUpView: 'feed', // 'feed' | 'series'
            sortOrder: 'desc',   // 'desc' | 'asc'
            filterText: '',

            // Recommandations state
            recommendations: @json($recommendations),
            loadingRecommendations: false,

            init() {
                this.loadData();
                this.fetchProgress();
                if (this.recommendations.length === 0) {
                    this.fetchGuestRecommendations();
                }
            },

            setTab(tab) {
                this.activeTab = tab;
                const url = new URL(window.location);
                url.searchParams.set('tab', tab);
                window.history.replaceState({}, '', url);
            },

            get catchUpCount() {
                return this.catchUpChapters.length;
            },

            get seriesWithUnreadCount() {
                const set = new Set(this.catchUpChapters.map(c => c.manga_id));
                return set.size;
            },

            get filteredCatchUpChapters() {
                let list = [...this.catchUpChapters];
                if (this.filterText.trim()) {
                    const q = this.filterText.toLowerCase().trim();
                    list = list.filter(c => (c.manga_title && c.manga_title.toLowerCase().includes(q)) ||
                                            ('chapitre ' + c.chapter_number).includes(q) ||
                                            (c.chapter_title && c.chapter_title.toLowerCase().includes(q)));
                }

                list.sort((a, b) => {
                    const tsA = a.timestamp || 0;
                    const tsB = b.timestamp || 0;
                    return this.sortOrder === 'asc' ? tsA - tsB : tsB - tsA;
                });

                return list;
            },

            get filteredCatchUpByManga() {
                let list = [...this.catchUpByManga];
                if (this.filterText.trim()) {
                    const q = this.filterText.toLowerCase().trim();
                    list = list.filter(m => m.manga_title && m.manga_title.toLowerCase().includes(q));
                }
                return list.filter(m => m.unread_count > 0);
            },

            async markAsRead(item) {
                if (item._marking) return;
                item._marking = true;

                try {
                    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                    const res = await fetch('/api/progress/toggle', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': token || ''
                        },
                        body: JSON.stringify({ chapter_id: item.chapter_id })
                    });
                    const data = await res.json();
                    if (data.success && data.is_read) {
                        // Optimistically remove from catchUpChapters
                        this.catchUpChapters = this.catchUpChapters.filter(c => c.chapter_id !== item.chapter_id);

                        // Also update catchUpByManga
                        const mangaGroup = this.catchUpByManga.find(m => m.manga_id === item.manga_id);
                        if (mangaGroup) {
                            mangaGroup.unread_chapters = mangaGroup.unread_chapters.filter(c => c.chapter_id !== item.chapter_id);
                            mangaGroup.unread_count = mangaGroup.unread_chapters.length;
                            if (mangaGroup.unread_chapters.length > 0) {
                                mangaGroup.earliest_unread = [...mangaGroup.unread_chapters].sort((a, b) => a.chapter_number - b.chapter_number)[0];
                            }
                        }
                    }
                } catch (e) {
                    console.error('Erreur lors du marquage comme lu:', e);
                } finally {
                    item._marking = false;
                }
            },

            async markSeriesAllRead(mangaGroup) {
                if (!confirm(`Marquer tous les chapitres de "${mangaGroup.manga_title}" comme lus ?`)) return;

                const latestChapter = [...mangaGroup.unread_chapters].sort((a, b) => b.chapter_number - a.chapter_number)[0];
                if (!latestChapter) return;

                try {
                    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                    const res = await fetch('/api/progress/mark-up-to', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': token || ''
                        },
                        body: JSON.stringify({ chapter_id: latestChapter.chapter_id })
                    });
                    const data = await res.json();
                    if (data.success) {
                        this.catchUpChapters = this.catchUpChapters.filter(c => c.manga_id !== mangaGroup.manga_id);
                        mangaGroup.unread_chapters = [];
                        mangaGroup.unread_count = 0;
                    }
                } catch (e) {
                    console.error('Erreur lors du marquage de la série:', e);
                }
            },

            loadData() {
                this.favorites = window.HiddenScan?.getFavorites ? window.HiddenScan.getFavorites().sort((a, b) => new Date(b.addedAt) - new Date(a.addedAt)) : [];
                this.history = window.HiddenScan?.getHistory ? window.HiddenScan.getHistory() : [];
                this.enrichHistory();
            },

            fetchProgress() {
                fetch('/api/progress')
                    .then(r => r.json())
                    .then(data => {
                        if (data && data.success && data.progress) {
                            this.serverProgress = data.progress;
                        }
                    })
                    .catch(err => console.debug('Failed to fetch server progress:', err));
            },

            fetchGuestRecommendations() {
                if (this.recommendations && this.recommendations.length > 0) return;

                const slugs = new Set();
                if (Array.isArray(this.history) && this.history.length > 0) {
                    this.history.forEach(h => {
                        const s = h.manga || h.slug;
                        if (s) slugs.add(s);
                    });
                }
                if (Array.isArray(this.favorites) && this.favorites.length > 0) {
                    this.favorites.forEach(f => {
                        if (f.slug) slugs.add(f.slug);
                    });
                }

                if (slugs.size === 0) return;

                this.loadingRecommendations = true;
                const slugsParam = Array.from(slugs).slice(0, 10).join(',');

                fetch('/api/recommendations?slugs=' + encodeURIComponent(slugsParam))
                    .then(r => r.json())
                    .then(data => {
                        if (data && data.success && Array.isArray(data.recommendations) && data.recommendations.length > 0) {
                            this.recommendations = data.recommendations;
                        }
                    })
                    .catch(err => console.debug('Recommandations fetch err:', err))
                    .finally(() => {
                        this.loadingRecommendations = false;
                    });
            },

            removeFavorite(slug) {
                if(confirm('Retirer cette série des favoris ?')) {
                    if (window.HiddenScan?.toggleFavorite) {
                        window.HiddenScan.toggleFavorite(slug);
                    }
                    this.loadData();
                }
            },

            clearHistory() {
                if(confirm('Effacer tout votre historique de lecture ?')) {
                    if (window.HiddenScan?._getData) {
                        const data = window.HiddenScan._getData();
                        data.history = [];
                        window.HiddenScan._save(data);
                    }
                    this.loadData();
                }
            },

            enrichHistory() {
                this.historyDetails = this.history.map(item => {
                    const fav = this.favorites.find(f => f.slug === item.manga);
                    return {
                        ...item,
                        title: fav ? fav.title : item.manga.replace(/-/g, ' '),
                        cover: fav ? fav.cover : null,
                        chapterSlug: item.chapterSlug || 'chapitre-' + item.chapter
                    };
                });
            },

            formatDate(isoString) {
                if(!isoString) return '';
                const date = new Date(isoString);
                return date.toLocaleDateString('fr-FR', { day: 'numeric', month: 'short' });
            }
        };
    }
    </script>

</x-layouts.public>