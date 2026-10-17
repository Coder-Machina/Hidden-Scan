@use('Illuminate\Support\Facades\Storage')
<x-layouts.public title="Bibliothèque & Mode Rattrapage — Hidden Scan">

    <div class="max-w-[1140px] mx-auto px-4 py-8" x-data="library()">

        {{-- ═══ Header ═══ --}}
        <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4 mb-8 pb-6 border-b border-[var(--bg-raised)]">
            <div>
                <h1 class="font-display text-[32px] font-semibold text-[var(--text-1)] tracking-tight flex items-center gap-3">
                    <svg class="w-7 h-7 text-[var(--accent)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                    </svg>
                    Bibliothèque
                </h1>
                <p class="text-[15px] text-[var(--text-2)] mt-1">Suivez vos lectures, retrouvez vos favoris et rattrapez tous vos chapitres en retard</p>
            </div>
        </div>

        {{-- ═══ Onglets de Navigation ═══ --}}
        <div class="flex gap-2 p-1 bg-[var(--bg-surface)] border border-[var(--border)] rounded-[6px] mb-8 overflow-x-auto no-scrollbar">
            {{-- Onglet : Mode Rattrapage --}}
            <button @click="setTab('rattrapage')"
                    type="button"
                    class="flex-1 min-w-max py-2.5 px-4 rounded-[4px] text-[13px] font-semibold transition-colors whitespace-nowrap flex items-center justify-center gap-2"
                    :class="activeTab === 'rattrapage' ? 'bg-[var(--accent)] text-white' : 'text-[var(--text-2)] hover:text-[var(--text-1)] hover:bg-[var(--bg-raised)]'">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
                <span>Rattrapage</span>
                <span x-show="catchUpCount > 0"
                      x-text="catchUpCount"
                      class="px-1.5 py-0.5 rounded-[4px] text-[10px] font-bold leading-none"
                      :class="activeTab === 'rattrapage' ? 'bg-white text-[var(--accent)]' : 'bg-[var(--accent)] text-white'"></span>
            </button>

            {{-- Onglet : En cours --}}
            <button @click="setTab('progress')"
                    type="button"
                    class="flex-1 min-w-max py-2.5 px-4 rounded-[4px] text-[13px] font-semibold transition-colors whitespace-nowrap flex items-center justify-center gap-2"
                    :class="activeTab === 'progress' ? 'bg-[var(--accent)] text-white' : 'text-[var(--text-2)] hover:text-[var(--text-1)] hover:bg-[var(--bg-raised)]'">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                <span>En cours ({{ count($mangasWithProgress) }})</span>
            </button>

            {{-- Onglet : Favoris --}}
            <button @click="setTab('favorites')"
                    type="button"
                    class="flex-1 min-w-max py-2.5 px-4 rounded-[4px] text-[13px] font-semibold transition-colors whitespace-nowrap flex items-center justify-center gap-2"
                    :class="activeTab === 'favorites' ? 'bg-[var(--accent)] text-white' : 'text-[var(--text-2)] hover:text-[var(--text-1)] hover:bg-[var(--bg-raised)]'">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                <span>Favoris (<span x-text="favorites.length">0</span>)</span>
            </button>

            {{-- Onglet : Historique --}}
            <button @click="setTab('history')"
                    type="button"
                    class="flex-1 min-w-max py-2.5 px-4 rounded-[4px] text-[13px] font-semibold transition-colors whitespace-nowrap flex items-center justify-center gap-2"
                    :class="activeTab === 'history' ? 'bg-[var(--accent)] text-white' : 'text-[var(--text-2)] hover:text-[var(--text-1)] hover:bg-[var(--bg-raised)]'">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span>Historique (<span x-text="history.length">0</span>)</span>
            </button>

            {{-- Onglet : Recommandations --}}
            <button @click="setTab('recommendations')"
                    type="button"
                    class="flex-1 min-w-max py-2.5 px-4 rounded-[4px] text-[13px] font-semibold transition-colors whitespace-nowrap flex items-center justify-center gap-2"
                    :class="activeTab === 'recommendations' ? 'bg-[var(--accent)] text-white' : 'text-[var(--text-2)] hover:text-[var(--text-1)] hover:bg-[var(--bg-raised)]'">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                </svg>
                <span>Recommandations</span>
            </button>
        </div>

        {{-- ══════════════════════════════════════════════════════════════ --}}
        {{-- ═══ Onglet 0 : MODE RATTRAPAGE                           ═══ --}}
        {{-- ══════════════════════════════════════════════════════════════ --}}
        <div x-show="activeTab === 'rattrapage'" x-cloak>

            @guest
                {{-- Invitation de connexion pour les visiteurs non connectés --}}
                <div class="mb-8 bg-[var(--bg-surface)] border border-[var(--border)] rounded-[6px] p-6 flex flex-col sm:flex-row items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div class="w-10 h-10 rounded-[4px] bg-[var(--bg-raised)] border border-[var(--border)] flex items-center justify-center text-[var(--accent)] flex-shrink-0">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-[var(--text-1)] font-semibold text-[15px]">Synchronisez votre progression</h3>
                            <p class="text-[13px] text-[var(--text-2)] mt-0.5">Connectez-vous pour enregistrer vos lectures et rattraper vos chapitres sur tous vos appareils.</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 w-full sm:w-auto flex-shrink-0">
                        <a href="{{ route('login') }}" class="btn-primary text-xs py-2 px-4 w-full sm:w-auto text-center">Se connecter</a>
                        <a href="{{ route('register') }}" class="btn-secondary text-xs py-2 px-4 w-full sm:w-auto text-center">Créer un compte</a>
                    </div>
                </div>
            @endguest

            <template x-if="catchUpCount > 0">
                <div class="space-y-6">
                    {{-- ── Barre d'outils Rattrapage ── --}}
                    <div class="bg-[var(--bg-surface)] border border-[var(--border)] rounded-[6px] p-4 flex flex-col md:flex-row items-stretch md:items-center justify-between gap-4">
                        {{-- Compteur & Résumé --}}
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-[4px] bg-[var(--bg-raised)] border border-[var(--border)] flex items-center justify-center text-[var(--accent)] flex-shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                </svg>
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <h2 class="text-[var(--text-1)] font-semibold text-[16px]">Chapitres non lus</h2>
                                    <span class="px-2 py-0.5 rounded-[4px] text-xs font-bold bg-[var(--accent)] text-white" x-text="catchUpCount"></span>
                                </div>
                                <p class="text-xs text-[var(--text-3)] mt-0.5">
                                    Sur <span class="text-[var(--text-1)] font-medium" x-text="seriesWithUnreadCount"></span> série(s) en cours de lecture
                                </p>
                            </div>
                        </div>

                        {{-- Contrôles : Recherche, Tri, Vue --}}
                        <div class="flex flex-wrap items-center gap-2">
                            {{-- Champ de recherche par série --}}
                            <div class="relative flex-1 sm:w-48 md:w-56">
                                <svg class="w-3.5 h-3.5 absolute left-3 top-1/2 -translate-y-1/2 text-[var(--text-3)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                                <input type="text"
                                       x-model="filterText"
                                       placeholder="Filtrer une série..."
                                       class="w-full bg-[var(--bg-base)] border border-[var(--border)] focus:border-[var(--accent)] rounded-[4px] pl-9 pr-3 py-1.5 text-xs text-[var(--text-1)] placeholder-[var(--text-3)] outline-none transition">
                            </div>

                            {{-- Tri chronologique --}}
                            <button @click="sortOrder = (sortOrder === 'desc' ? 'asc' : 'desc')"
                                    type="button"
                                    class="px-3 py-1.5 rounded-[4px] bg-[var(--bg-raised)] border border-[var(--border)] hover:border-[var(--text-3)] text-[var(--text-2)] hover:text-[var(--text-1)] text-xs font-medium flex items-center gap-1.5 transition">
                                <svg class="w-3.5 h-3.5 text-[var(--accent)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h13M3 8h9m-9 4h6m4 0l4-4m0 0l4 4m-4-4v12"/>
                                </svg>
                                <span class="hidden sm:inline" x-text="sortOrder === 'desc' ? 'Récents' : 'Anciens'"></span>
                            </button>

                            {{-- Toggle d'affichage (Flux vs Par Série) --}}
                            <div class="flex bg-[var(--bg-base)] border border-[var(--border)] rounded-[4px] p-0.5">
                                <button @click="catchUpView = 'feed'"
                                        type="button"
                                        class="px-3 py-1 rounded-[4px] text-xs font-semibold transition"
                                        :class="catchUpView === 'feed' ? 'bg-[var(--accent)] text-white' : 'text-[var(--text-2)] hover:text-[var(--text-1)]'">
                                    Flux
                                </button>
                                <button @click="catchUpView = 'series'"
                                        type="button"
                                        class="px-3 py-1 rounded-[4px] text-xs font-semibold transition"
                                        :class="catchUpView === 'series' ? 'bg-[var(--accent)] text-white' : 'text-[var(--text-2)] hover:text-[var(--text-1)]'">
                                    Par série
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- ── VUE 1 : FLUX CHRONOLOGIQUE DES CHAPITRES ── --}}
                    <div x-show="catchUpView === 'feed'" class="space-y-3">
                        <template x-for="item in filteredCatchUpChapters" :key="item.chapter_id">
                            <div class="bg-[var(--bg-surface)] border border-[var(--border)] hover:border-[var(--accent)] rounded-[6px] p-4 flex items-center justify-between gap-4 transition-colors">
                                {{-- Thumbnail + Titre Manga + Détails Chapitre --}}
                                <div class="flex items-center gap-4 min-w-0">
                                    <a :href="item.url" class="relative w-12 aspect-[3/4] rounded-[4px] overflow-hidden bg-[var(--bg-raised)] border border-[var(--border)] flex-shrink-0">
                                        <template x-if="item.manga_cover">
                                            <img :src="item.manga_cover" :alt="item.manga_title" class="w-full h-full object-cover" loading="lazy">
                                        </template>
                                        <template x-if="!item.manga_cover">
                                            <div class="w-full h-full flex items-center justify-center text-xs text-[var(--text-3)]">📖</div>
                                        </template>
                                    </a>

                                    <div class="min-w-0">
                                        {{-- Titre & Type --}}
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <a :href="'/manga/' + item.manga_slug" class="font-display font-semibold text-[var(--text-1)] hover:text-[var(--accent)] transition text-[15px] truncate" x-text="item.manga_title"></a>
                                            <span class="text-[11px] font-semibold uppercase tracking-wider text-[var(--text-3)] bg-[var(--bg-raised)] px-1.5 py-0.5 rounded-[4px]" x-text="item.manga_type"></span>
                                        </div>

                                        {{-- Chapitre --}}
                                        <div class="mt-1 flex items-center gap-2 flex-wrap text-xs">
                                            <a :href="item.url" class="font-medium text-[var(--accent)] hover:underline">
                                                <span>Chapitre</span> <span x-text="item.chapter_number"></span>
                                            </a>
                                            <template x-if="item.chapter_title">
                                                <span class="text-[var(--text-3)] truncate max-w-[200px] sm:max-w-xs" x-text="'— ' + item.chapter_title"></span>
                                            </template>
                                            <span class="text-[var(--text-3)]">•</span>
                                            <span class="text-[var(--text-3)]" x-text="item.published_at_human || item.published_at_formatted"></span>
                                        </div>
                                    </div>
                                </div>

                                {{-- Boutons d'action : Marquer lu & Lire --}}
                                <div class="flex items-center gap-2 flex-shrink-0">
                                    <button @click="markAsRead(item)"
                                            type="button"
                                            :disabled="item._marking"
                                            class="py-1.5 px-3 rounded-[4px] bg-[var(--bg-raised)] hover:bg-[var(--border)] text-[var(--text-2)] hover:text-[var(--text-1)] border border-[var(--border)] transition text-xs font-semibold"
                                            title="Marquer comme lu">
                                        <span class="hidden md:inline">Marquer lu</span>
                                        <span class="md:hidden">✓</span>
                                    </button>

                                    <a :href="item.url" class="btn-primary text-xs py-1.5 px-4 inline-flex items-center gap-1.5">
                                        <span>Lire</span>
                                    </a>
                                </div>
                            </div>
                        </template>

                        <template x-if="filteredCatchUpChapters.length === 0">
                            <div class="text-center py-12 text-[var(--text-3)] text-[14px] bg-[var(--bg-surface)] border border-[var(--border)] rounded-[6px]">
                                Aucun chapitre ne correspond à votre recherche.
                            </div>
                        </template>
                    </div>

                    {{-- ── VUE 2 : PAR SÉRIE ── --}}
                    <div x-show="catchUpView === 'series'" class="space-y-4">
                        <template x-for="mangaGroup in filteredCatchUpByManga" :key="mangaGroup.manga_id">
                            <div class="bg-[var(--bg-surface)] border border-[var(--border)] rounded-[6px] p-6">
                                {{-- Manga Header --}}
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-[var(--bg-raised)]">
                                    <div class="flex items-center gap-4 min-w-0">
                                        <a :href="'/manga/' + mangaGroup.manga_slug" class="w-12 aspect-[3/4] rounded-[4px] overflow-hidden bg-[var(--bg-raised)] border border-[var(--border)] flex-shrink-0">
                                            <template x-if="mangaGroup.manga_cover">
                                                <img :src="mangaGroup.manga_cover" :alt="mangaGroup.manga_title" class="w-full h-full object-cover">
                                            </template>
                                            <template x-if="!mangaGroup.manga_cover">
                                                <div class="w-full h-full flex items-center justify-center text-xs text-[var(--text-3)]">📖</div>
                                            </template>
                                        </a>
                                        <div class="min-w-0">
                                            <a :href="'/manga/' + mangaGroup.manga_slug" class="block font-display font-semibold text-[var(--text-1)] hover:text-[var(--accent)] transition text-[16px] truncate" x-text="mangaGroup.manga_title"></a>
                                            <div class="flex items-center gap-2 mt-1">
                                                <span class="text-[11px] font-semibold uppercase tracking-wider text-[var(--text-3)] bg-[var(--bg-raised)] px-1.5 py-0.5 rounded-[4px]" x-text="mangaGroup.manga_type"></span>
                                                <span class="text-xs font-medium text-[var(--accent)]" x-text="mangaGroup.unread_count + ' non lu(s)'"></span>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Actions par série --}}
                                    <div class="flex items-center gap-2 self-end sm:self-center">
                                        <button @click="markSeriesAllRead(mangaGroup)"
                                                type="button"
                                                class="px-3 py-1.5 rounded-[4px] bg-[var(--bg-raised)] hover:bg-[var(--border)] text-[var(--text-2)] hover:text-[var(--text-1)] border border-[var(--border)] text-xs font-semibold transition">
                                            <span>Tout marquer lu</span>
                                        </button>

                                        <template x-if="mangaGroup.earliest_unread">
                                            <a :href="mangaGroup.earliest_unread.url" class="btn-primary text-xs py-1.5 px-3">
                                                <span>Reprendre (Ch. <span x-text="mangaGroup.earliest_unread.chapter_number"></span>)</span>
                                            </a>
                                        </template>
                                    </div>
                                </div>

                                {{-- Liste des chapitres en attente pour cette série --}}
                                <div class="mt-4">
                                    <div class="text-[11px] font-semibold uppercase tracking-wider text-[var(--text-3)] mb-2">Chapitres en attente :</div>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
                                        <template x-for="ch in mangaGroup.unread_chapters" :key="ch.chapter_id">
                                            <div class="bg-[var(--bg-base)] border border-[var(--border)] hover:border-[var(--accent)] rounded-[4px] p-2.5 flex items-center justify-between gap-2 transition">
                                                <a :href="ch.url" class="min-w-0 flex-1 flex items-center gap-2">
                                                    <span class="text-xs font-semibold text-[var(--accent)]">Ch. <span x-text="ch.chapter_number"></span></span>
                                                    <span class="text-[11px] text-[var(--text-3)] truncate" x-text="ch.published_at_human || ch.published_at_formatted"></span>
                                                </a>
                                                <div class="flex items-center gap-1.5 flex-shrink-0">
                                                    <button @click="markAsRead(ch)"
                                                            type="button"
                                                            :disabled="ch._marking"
                                                            class="p-1 rounded-[4px] bg-[var(--bg-raised)] hover:bg-[var(--border)] text-[var(--text-2)] hover:text-[var(--text-1)] transition"
                                                            title="Marquer comme lu">
                                                        <span class="text-xs">✓</span>
                                                    </button>
                                                    <a :href="ch.url"
                                                       class="px-2 py-0.5 rounded-[4px] bg-[var(--accent)] text-white text-[11px] font-semibold hover:bg-[var(--accent-hover)] transition">
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
                <div class="text-center py-16 bg-[var(--bg-surface)] border border-[var(--border)] rounded-[6px] p-8">
                    <div class="w-12 h-12 mx-auto rounded-[4px] bg-[var(--bg-raised)] border border-[var(--border)] flex items-center justify-center mb-4 text-[var(--text-1)]">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-[var(--text-1)] mb-2">Vous êtes à jour</h3>
                    <p class="text-[var(--text-2)] mb-6 max-w-md mx-auto text-sm">
                        Aucun chapitre non lu sur vos séries en cours. Dès qu'un nouveau chapitre sortira, vous le retrouverez directement ici.
                    </p>
                    <div class="flex flex-wrap items-center justify-center gap-3">
                        <button @click="setTab('progress')" type="button" class="btn-secondary text-xs py-2 px-4">
                            Voir mes lectures en cours
                        </button>
                        <a href="{{ route('manga.index') }}" class="btn-primary text-xs py-2 px-4">
                            Explorer le catalogue
                        </a>
                    </div>
                </div>
            </template>
        </div>

        {{-- ══════════════════════════════════════════════════════════════ --}}
        {{-- ═══ Onglet 1 : PROGRESSION EN COURS                     ═══ --}}
        {{-- ══════════════════════════════════════════════════════════════ --}}
        <div x-show="activeTab === 'progress'" x-cloak>
            @if(count($mangasWithProgress) > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($mangasWithProgress as $item)
                        @php
                            $manga = $item['manga'];
                            $readCount = $item['read_count'];
                            $totalChapters = $item['total_chapters'];
                            $percent = $item['percent'];
                            $resumeChapter = $item['resume_chapter'];
                        @endphp
                        <div class="bg-[var(--bg-surface)] border border-[var(--border)] hover:border-[var(--accent)] rounded-[6px] p-4 flex gap-4 transition-colors">
                            {{-- Cover --}}
                            <a href="{{ route('manga.show', $manga->slug) }}" class="flex-shrink-0 w-20 aspect-[3/4] rounded-[4px] overflow-hidden bg-[var(--bg-raised)] border border-[var(--border)]">
                                @if($manga->cover_image)
                                    <img src="{{ Storage::url($manga->cover_image) }}" alt="{{ $manga->title }}" class="w-full h-full object-cover" loading="lazy">
                                @else
                                    <div class="w-full h-full flex items-center justify-center p-2 text-center text-xs text-[var(--text-3)]">
                                        {{ $manga->title }}
                                    </div>
                                @endif
                            </a>

                            {{-- Info & Actions --}}
                            <div class="flex-1 flex flex-col justify-between min-w-0">
                                <div>
                                    <a href="{{ route('manga.show', $manga->slug) }}" class="block font-display font-semibold text-[var(--text-1)] hover:text-[var(--accent)] transition text-[15px] truncate">
                                        {{ $manga->title }}
                                    </a>
                                    
                                    {{-- Type badge --}}
                                    <span class="inline-block mt-1 text-[11px] font-semibold uppercase tracking-wider text-[var(--text-3)]">
                                        {{ $manga->type?->getLabel() ?? 'Œuvre' }}
                                    </span>

                                    {{-- Barre de progression --}}
                                    <div class="mt-3">
                                        <div class="flex items-center justify-between text-xs text-[var(--text-2)] mb-1">
                                            <span>{{ $readCount }}/{{ $totalChapters }} lus</span>
                                            <span class="text-[var(--text-1)] font-semibold">{{ $percent }}%</span>
                                        </div>
                                        <div class="w-full bg-[var(--bg-raised)] rounded-[4px] h-1.5 overflow-hidden">
                                            <div class="bg-[var(--accent)] h-1.5 rounded-[4px] transition-all" style="width: {{ $percent }}%"></div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Bouton Reprendre --}}
                                <div class="mt-4">
                                    @if($resumeChapter)
                                        <a href="{{ route('chapter.show', [$manga->slug, $resumeChapter->slug]) }}"
                                           class="btn-primary w-full text-xs py-2 text-center block">
                                            {{ $percent >= 100 ? 'Relire' : 'Reprendre (Ch. ' . $resumeChapter->number . ')' }}
                                        </a>
                                    @else
                                        <a href="{{ route('manga.show', $manga->slug) }}"
                                           class="btn-secondary w-full text-xs py-2 text-center block">
                                            Voir la fiche
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-16 bg-[var(--bg-surface)] border border-[var(--border)] rounded-[6px] p-8">
                    <p class="text-base text-[var(--text-1)] font-semibold mb-2">Aucune lecture en cours</p>
                    <p class="text-[var(--text-2)] mb-6 max-w-md mx-auto text-xs">Cochez les chapitres que vous lisez sur la page d'une œuvre pour suivre votre progression ici.</p>
                    <a href="{{ route('manga.index') }}" class="btn-primary text-xs py-2 px-4">
                        Parcourir le catalogue
                    </a>
                </div>
            @endif
        </div>

        {{-- ══════════════════════════════════════════════════════════════ --}}
        {{-- ═══ Onglet 2 : FAVORIS                                  ═══ --}}
        {{-- ══════════════════════════════════════════════════════════════ --}}
        <div x-show="activeTab === 'favorites'" x-cloak>
            <template x-if="favorites.length > 0">
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
                    <template x-for="manga in favorites" :key="manga.slug">
                        <div class="manga-card bg-[var(--bg-surface)] border border-[var(--border)] rounded-[6px] overflow-hidden flex flex-col justify-between relative group">
                            <a :href="'/manga/' + manga.slug" class="block">
                                <div class="relative aspect-[3/4] bg-[var(--bg-raised)] overflow-hidden">
                                    <template x-if="manga.cover">
                                        <img :src="manga.cover" :alt="manga.title" class="cover w-full h-full object-cover" loading="lazy">
                                    </template>
                                    <template x-if="!manga.cover">
                                        <div class="w-full h-full flex items-center justify-center p-2 text-center text-xs text-[var(--text-3)]">
                                            <span x-text="manga.title"></span>
                                        </div>
                                    </template>
                                    <div class="absolute inset-0 card-overlay pointer-events-none"></div>
                                </div>
                                <div class="p-3">
                                    <h3 class="font-display font-semibold text-[13px] text-[var(--text-1)] group-hover:text-[var(--accent)] transition-colors line-clamp-2" x-text="manga.title"></h3>
                                    
                                    {{-- Progression si chargée --}}
                                    <template x-if="serverProgress[manga.slug]">
                                        <div class="mt-2">
                                            <div class="flex items-center justify-between text-[10px] text-[var(--text-3)] mb-1">
                                                <span x-text="serverProgress[manga.slug].read_count + '/' + serverProgress[manga.slug].total_chapters + ' lus'"></span>
                                                <span x-text="serverProgress[manga.slug].percent + '%'"></span>
                                            </div>
                                            <div class="w-full bg-[var(--bg-raised)] rounded-[4px] h-1 overflow-hidden">
                                                <div class="bg-[var(--accent)] h-1 rounded-[4px]" :style="'width: ' + serverProgress[manga.slug].percent + '%'"></div>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </a>

                            {{-- Bouton Supprimer --}}
                            <button @click.prevent="removeFavorite(manga.slug)"
                                    type="button"
                                    class="absolute top-2 right-2 p-1.5 rounded-[4px] bg-[rgba(10,10,10,0.85)] text-[var(--text-3)] hover:text-white hover:bg-[var(--accent)] transition opacity-0 group-hover:opacity-100"
                                    title="Retirer des favoris">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                    </template>
                </div>
            </template>
            <template x-if="favorites.length === 0">
                <div class="text-center py-16 bg-[var(--bg-surface)] border border-[var(--border)] rounded-[6px] p-8">
                    <p class="text-base text-[var(--text-1)] font-semibold mb-2">Aucun favori pour le moment</p>
                    <p class="text-[var(--text-2)] mb-6 text-xs">Ajoutez des séries à vos favoris pour les retrouver facilement ici.</p>
                    <a href="{{ route('manga.index') }}" class="btn-primary text-xs py-2 px-4">Parcourir le catalogue</a>
                </div>
            </template>
        </div>

        {{-- ══════════════════════════════════════════════════════════════ --}}
        {{-- ═══ Onglet 3 : HISTORIQUE                                ═══ --}}
        {{-- ══════════════════════════════════════════════════════════════ --}}
        <div x-show="activeTab === 'history'" x-cloak>
            <template x-if="history.length > 0">
                <div class="space-y-4">
                    <div class="flex justify-end">
                        <button @click="clearHistory()" type="button" class="text-xs text-[var(--text-3)] hover:text-[var(--accent)] transition flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            Effacer l'historique
                        </button>
                    </div>

                    <div class="bg-[var(--bg-surface)] border border-[var(--border)] rounded-[6px] overflow-hidden divide-y divide-[var(--border)]">
                        <template x-for="item in historyDetails" :key="item.slug + item.chapter">
                            <div class="p-4 flex items-center justify-between gap-4 hover:bg-[var(--bg-raised)] transition-colors">
                                <div class="flex items-center gap-4 min-w-0">
                                    <template x-if="item.cover">
                                        <img :src="item.cover" class="w-10 aspect-[3/4] object-cover rounded-[4px] flex-shrink-0 border border-[var(--border)]" alt="">
                                    </template>
                                    <template x-if="!item.cover">
                                        <div class="w-10 aspect-[3/4] bg-[var(--bg-raised)] border border-[var(--border)] rounded-[4px] flex-shrink-0 flex items-center justify-center text-xs text-[var(--text-3)]">
                                            📖
                                        </div>
                                    </template>

                                    <div class="min-w-0">
                                        <a :href="'/manga/' + item.slug" class="block font-display font-semibold text-[var(--text-1)] hover:text-[var(--accent)] transition truncate text-[14px]" x-text="item.title || item.slug"></a>
                                        <div class="flex items-center gap-2 mt-0.5 text-xs">
                                            <a :href="'/manga/' + item.slug + '/chapter/' + item.chapterSlug" class="text-[var(--accent)] hover:underline font-medium">
                                                Chapitre <span x-text="item.chapter"></span>
                                            </a>
                                            <span class="text-[var(--text-3)]">•</span>
                                            <span class="text-[var(--text-3)]" x-text="formatDate(item.readAt)"></span>
                                        </div>
                                    </div>
                                </div>

                                <a :href="'/manga/' + item.slug + '/chapter/' + item.chapterSlug"
                                   class="btn-secondary text-xs py-1.5 px-3 flex-shrink-0">
                                    Relire
                                </a>
                            </div>
                        </template>
                    </div>
                </div>
            </template>
            <template x-if="history.length === 0">
                <div class="text-center py-16 bg-[var(--bg-surface)] border border-[var(--border)] rounded-[6px] p-8">
                    <p class="text-base text-[var(--text-1)] font-semibold mb-2">Aucun historique de lecture</p>
                    <p class="text-[var(--text-2)] text-xs">Vos dernières lectures apparaîtront ici.</p>
                </div>
            </template>
        </div>

        {{-- ══════════════════════════════════════════════════════════════ --}}
        {{-- ═══ Onglet 4 : RECOMMANDATIONS                            ═══ --}}
        {{-- ══════════════════════════════════════════════════════════════ --}}
        <div x-show="activeTab === 'recommendations'" x-cloak>

            {{-- Bannière explicative --}}
            <div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-[var(--bg-surface)] border border-[var(--border)] rounded-[6px] p-6">
                <div>
                    <h2 class="font-display font-semibold text-[18px] text-[var(--text-1)]">Si t'as aimé X, lis Y</h2>
                    <p class="text-[13px] text-[var(--text-2)] mt-1">Recommandations générées sur-mesure à partir des séries cochées comme lues dans votre bibliothèque.</p>
                </div>
                <div class="flex items-center gap-2 flex-shrink-0">
                    <a href="{{ route('manga.random') }}" class="btn-secondary text-xs py-2 px-3">
                        <span>🎲 Surprends-moi</span>
                    </a>
                </div>
            </div>

            {{-- Skeleton loading --}}
            <template x-if="loadingRecommendations">
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    <div class="skeleton aspect-[3/4]"></div>
                    <div class="skeleton aspect-[3/4]"></div>
                    <div class="skeleton aspect-[3/4]"></div>
                    <div class="skeleton aspect-[3/4]"></div>
                </div>
            </template>

            {{-- Liste des groupes de recommandations --}}
            <template x-if="!loadingRecommendations && recommendations.length > 0">
                <div class="space-y-6">
                    <template x-for="group in recommendations" :key="group.source_manga.id">
                        <div class="bg-[var(--bg-surface)] border border-[var(--border)] rounded-[6px] p-6">
                            
                            {{-- En-tête de la source --}}
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 mb-4 border-b border-[var(--bg-raised)]">
                                <div class="flex items-center gap-3 min-w-0">
                                    <a :href="group.source_manga.url" class="relative w-10 aspect-[3/4] rounded-[4px] overflow-hidden bg-[var(--bg-raised)] border border-[var(--border)] flex-shrink-0">
                                        <template x-if="group.source_manga.cover_image">
                                            <img :src="group.source_manga.cover_image" :alt="group.source_manga.title" class="w-full h-full object-cover">
                                        </template>
                                        <template x-if="!group.source_manga.cover_image">
                                            <div class="w-full h-full flex items-center justify-center text-xs text-[var(--text-3)]">📖</div>
                                        </template>
                                    </a>
                                    <div class="min-w-0">
                                        <div class="text-[11px] font-semibold uppercase tracking-wider text-[var(--accent)]">Parce que vous avez lu</div>
                                        <a :href="group.source_manga.url" class="font-display font-semibold text-[var(--text-1)] hover:text-[var(--accent)] transition text-[16px] block truncate" x-text="group.source_manga.title"></a>
                                    </div>
                                </div>
                            </div>

                            {{-- Grille des mangas recommandés --}}
                            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
                                <template x-for="item in group.recommendations" :key="item.id">
                                    <div class="manga-card bg-[var(--bg-base)] border border-[var(--border)] rounded-[6px] p-3 flex flex-col justify-between">
                                        <div>
                                            {{-- Cover --}}
                                            <div class="relative w-full aspect-[3/4] rounded-[4px] overflow-hidden bg-[var(--bg-raised)] mb-2.5">
                                                <template x-if="item.cover_image">
                                                    <img :src="item.cover_image" :alt="item.title" class="cover w-full h-full object-cover" loading="lazy">
                                                </template>
                                                <template x-if="!item.cover_image">
                                                    <div class="w-full h-full flex items-center justify-center text-[var(--text-3)] text-xs">📖</div>
                                                </template>
                                                <div class="absolute inset-0 card-overlay pointer-events-none"></div>

                                                {{-- Match badge --}}
                                                <span class="card-badge absolute top-2 right-2 text-[10px] font-bold text-white bg-[var(--accent)] px-1.5 py-0.5 rounded-[4px]" x-text="item.match_percent + '%'"></span>
                                            </div>

                                            {{-- Titre --}}
                                            <h4 class="font-display font-semibold text-[var(--text-1)] text-xs line-clamp-1 mb-1" :title="item.title">
                                                <a :href="item.url" class="hover:text-[var(--accent)] transition-colors" x-text="item.title"></a>
                                            </h4>

                                            {{-- Raison --}}
                                            <p class="text-[11px] text-[var(--text-3)] line-clamp-2 mb-3" x-text="item.reason"></p>
                                        </div>

                                        {{-- Actions --}}
                                        <div class="pt-2 border-t border-[var(--border)] flex items-center gap-2">
                                            <a :href="item.first_chapter_url" class="btn-primary flex-1 text-center text-xs py-1.5">
                                                Lire Ch. 1
                                            </a>
                                            <a :href="item.url" class="btn-secondary p-1.5 rounded-[4px]" title="Fiche de la série">
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
                <div class="text-center py-16 bg-[var(--bg-surface)] border border-[var(--border)] rounded-[6px] p-8">
                    <h3 class="text-base text-[var(--text-1)] font-semibold mb-2">Aucune recommandation disponible</h3>
                    <p class="text-[var(--text-2)] max-w-lg mx-auto mb-6 text-xs">
                        Pour que l'algorithme puisse vous proposer des suggestions, cochez des chapitres comme lus dans le lecteur ou ajoutez des œuvres à vos favoris.
                    </p>
                    <div class="flex flex-wrap items-center justify-center gap-3">
                        <a href="{{ route('manga.index') }}" class="btn-primary text-xs py-2 px-4">
                            Explorer le catalogue
                        </a>
                        <a href="{{ route('manga.random') }}" class="btn-secondary text-xs py-2 px-4">
                            🎲 Surprends-moi
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
                        this.catchUpChapters = this.catchUpChapters.filter(c => c.chapter_id !== item.chapter_id);

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