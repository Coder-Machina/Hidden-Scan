@use('Illuminate\Support\Facades\Storage')
<x-layouts.public :title="$manga->title . ' — Ch. ' . $chapter->number . ' | Hidden Scan'" :hide-navbar="true">

    <div x-data="chapterReader()" x-init="init()" class="animate-fade-in pt-14">

        {{-- ═══ Barre de navigation ═══ --}}
        <div class="fixed top-0 left-0 right-0 z-50 px-4 sm:px-6 py-2 glass border-b border-line/30 transition-all duration-300"
             :class="navHidden && !showSettings ? '-translate-y-full opacity-0 pointer-events-none' : 'translate-y-0 opacity-100'"
             @mouseenter="navHidden = false">

            <div class="max-w-4xl mx-auto flex items-center justify-between gap-3">
                {{-- Retour --}}
                <a href="{{ route('manga.show', $manga->slug) }}" class="flex items-center gap-2 text-violet hover:text-violet-glow transition text-sm font-semibold flex-shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    <span class="hidden sm:inline">{{ Str::limit($manga->title, 25) }}</span>
                </a>

                {{-- Sélecteur de chapitre --}}
                <select @change="window.location.href=$event.target.value"
                    class="input-field !w-auto !py-1 px-2 text-xs sm:text-sm text-center !rounded-full max-w-[130px] sm:max-w-[200px]">
                    @foreach($manga->chapters as $c)
                        <option value="{{ route('chapter.show', [$manga->slug, $c->slug]) }}"
                            {{ $c->id === $chapter->id ? 'selected' : '' }}>
                            Ch. {{ $c->number }} {{ $c->title ? '— ' . Str::limit($c->title, 20) : '' }}
                        </option>
                    @endforeach
                </select>

                {{-- Actions --}}
                <div class="flex items-center gap-1">
                    @if($prev)
                        <a href="{{ route('chapter.show', [$manga->slug, $prev->slug]) }}"
                           class="p-2 rounded-lg text-mist hover:text-chalk hover:bg-panel-hi transition" title="Chapitre précédent">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                        </a>
                    @endif
                    @if($next)
                        <a href="{{ route('chapter.show', [$manga->slug, $next->slug]) }}"
                           class="p-2 rounded-lg text-mist hover:text-chalk hover:bg-panel-hi transition" title="Chapitre suivant">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </a>
                    @endif

                    {{-- Réglages --}}
                    <button @click="showSettings = !showSettings"
                            class="p-2 rounded-lg transition"
                            :class="showSettings ? 'text-violet bg-violet/10' : 'text-mist hover:text-chalk hover:bg-panel-hi'"
                            title="Paramètres de lecture">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </button>

                    {{-- Signaler un problème --}}
                    <button @click="openReportModal()"
                            class="p-2 rounded-lg text-mist hover:text-crimson hover:bg-crimson/10 transition"
                            title="Signaler un problème sur ce chapitre">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9"/></svg>
                    </button>
                </div>
            </div>

            {{-- Panneau réglages --}}
            <div x-show="showSettings" x-cloak x-transition class="max-w-4xl mx-auto mt-3 p-4 bg-panel-hi rounded-xl border border-line/50">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    {{-- Mode de lecture --}}
                    <div>
                        <label class="block text-xs font-semibold text-mist mb-2 uppercase tracking-wider">Mode</label>
                        <div class="flex gap-2">
                            <button @click="readMode = 'vertical'" class="flex-1 py-2 px-3 rounded-lg text-sm font-medium transition-all"
                                    :class="readMode === 'vertical' ? 'bg-violet text-white' : 'bg-ink text-mist hover:text-chalk border border-line'">
                                ↕ Vertical
                            </button>
                            <button @click="readMode = 'page'" class="flex-1 py-2 px-3 rounded-lg text-sm font-medium transition-all"
                                    :class="readMode === 'page' ? 'bg-violet text-white' : 'bg-ink text-mist hover:text-chalk border border-line'">
                                ⬜ Page
                            </button>
                        </div>
                    </div>

                    {{-- Largeur --}}
                    <div x-show="!isMobile">
                        <label class="block text-xs font-semibold text-mist mb-2 uppercase tracking-wider">
                            Largeur : <span x-text="readerWidth + '%'"></span>
                        </label>
                        <input type="range" min="50" max="100" x-model="readerWidth"
                               @input="saveSettings()"
                               class="w-full accent-violet cursor-pointer">
                    </div>

                    {{-- Espacement --}}
                    <div>
                        <label class="block text-xs font-semibold text-mist mb-2 uppercase tracking-wider">
                            Espacement : <span x-text="pageGap + 'px'"></span>
                        </label>
                        <input type="range" min="0" max="16" x-model="pageGap"
                               @input="saveSettings()"
                               class="w-full accent-violet cursor-pointer">
                    </div>
                </div>
            </div>
        </div>

        {{-- ═══ Barre de progression ═══ --}}
        <div class="fixed top-0 left-0 right-0 z-50 h-0.5 bg-ink-deep">
            <div class="h-full bg-gradient-to-r from-violet to-violet-glow transition-all duration-200 ease-out"
                 :style="'width:' + progressPercent + '%'"></div>
        </div>

        {{-- ═══ Pages du chapitre ═══ --}}
        <div class="py-2 sm:py-6">
            {{-- Mode vertical (100% pleine largeur sur mobile) --}}
            <div x-show="readMode === 'vertical'" class="flex flex-col items-center mx-auto transition-all w-full"
                 :style="(isMobile ? 'width: 100% !important; max-width: 100% !important;' : 'max-width: ' + readerWidth + '%;') + ' gap:' + pageGap + 'px;'"
                 id="reader-vertical">
                @forelse($chapter->pages as $pageIndex => $page)
                    <div class="w-full relative" data-page="{{ $page->page_number }}">
                        <img
                            src="{{ Storage::url($page->image_path) }}"
                            alt="Page {{ $page->page_number }}"
                            loading="{{ $pageIndex < 3 ? 'eager' : 'lazy' }}"
                            class="w-full block"
                            x-on:error="handleImageError($event)"
                            @load="handleImageLoad({{ $page->page_number }})"
                        >
                    </div>
                @empty
                    <div class="text-center py-24 text-mist">
                        <svg class="w-16 h-16 mx-auto text-mist/20 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        <p class="text-lg">Aucune page disponible pour ce chapitre.</p>
                    </div>
                @endforelse
            </div>

            {{-- Mode page par page --}}
            <div x-show="readMode === 'page'" x-cloak class="flex flex-col items-center mx-auto transition-all w-full"
                 :style="isMobile ? 'width: 100% !important; max-width: 100% !important;' : 'max-width: ' + readerWidth + '%;'">
                @if($chapter->pages->count())
                    <div class="w-full relative">
                        <img
                            :src="pages[currentPage - 1]?.src || ''"
                            :alt="'Page ' + currentPage"
                            class="w-full block cursor-pointer"
                            @click="nextPage()"
                            x-on:error="handleImageError($event)"
                        >
                    </div>

                    {{-- Navigation page --}}
                    <div class="flex items-center justify-center gap-4 mt-4">
                        <button @click="prevPage()" :disabled="currentPage <= 1"
                                class="btn-secondary !py-2 !px-4 !text-sm disabled:opacity-30 disabled:cursor-not-allowed">
                            ← Précédente
                        </button>
                        <span class="text-sm text-mist font-medium">
                            <span x-text="currentPage"></span> / <span x-text="totalPages"></span>
                        </span>
                        <button @click="nextPage()" :disabled="currentPage >= totalPages"
                                class="btn-primary !py-2 !px-4 !text-sm disabled:opacity-30 disabled:cursor-not-allowed">
                            Suivante →
                        </button>
                    </div>
                @endif
            </div>
        </div>

        {{-- ═══ Navigation fin de chapitre ═══ --}}
        <div class="flex flex-col sm:flex-row items-center justify-center gap-4 py-8 border-t border-line/30">
            @if($prev)
                <a href="{{ route('chapter.show', [$manga->slug, $prev->slug]) }}" class="btn-secondary w-full sm:w-auto justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    Chapitre précédent
                </a>
            @endif
            <a href="{{ route('manga.show', $manga->slug) }}" class="btn-secondary w-full sm:w-auto justify-center !border-violet/30 !text-violet">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                Tous les chapitres
            </a>
            @if($next)
                <a href="{{ route('chapter.show', [$manga->slug, $next->slug]) }}" class="btn-primary w-full sm:w-auto justify-center">
                    Chapitre suivant
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </a>
            @endif
        </div>

        {{-- ═══ Réactions au chapitre (style Raijin) ═══ --}}
        <div class="max-w-3xl mx-auto mt-6 mb-4" x-data="chapterReactions()" x-init="init()">
            <div class="bg-[#14151e] rounded-2xl p-5 sm:p-6 text-center">
                <p class="text-[#9da3b4] text-sm mb-4 font-semibold">Qu'avez-vous pensé de ce chapitre ?</p>
                <div class="flex items-center justify-center gap-3 flex-wrap">
                    <template x-for="r in reactions" :key="r.emoji">
                        <button @click="react(r.emoji)"
                                class="flex flex-col items-center gap-1 px-3.5 py-2 rounded-xl transition-all hover:scale-110 cursor-pointer"
                                :class="userReaction === r.emoji ? 'bg-white/10 scale-105' : 'hover:bg-[#1e2029]'">
                            <span class="text-2xl" x-text="r.emoji"></span>
                            <span class="text-xs font-bold" :class="userReaction === r.emoji ? 'text-white' : 'text-[#7d8498]'" x-text="r.count"></span>
                            <span class="text-[10px] text-[#7d8498]" x-text="r.label"></span>
                        </button>
                    </template>
                </div>
            </div>
        </div>

        <script>
        function chapterReactions() {
            return {
                reactions: [
                    { emoji: '🔥', label: 'Incroyable', count: 0 },
                    { emoji: '😍', label: 'Adoré', count: 0 },
                    { emoji: '😢', label: 'Triste', count: 0 },
                    { emoji: '😡', label: 'Énervé', count: 0 },
                    { emoji: '🤯', label: 'Choqué', count: 0 },
                    { emoji: '😂', label: 'Drôle', count: 0 },
                    { emoji: '💤', label: 'Ennuyeux', count: 0 },
                ],
                userReaction: null,
                storageKey: 'hs_reactions_chapter_{{ $chapter->id }}',
                globalKey: 'hs_reactions_counts_chapter_{{ $chapter->id }}',

                init() {
                    // Load user's reaction
                    this.userReaction = localStorage.getItem(this.storageKey) || null;
                    // Load global counts
                    try {
                        const counts = JSON.parse(localStorage.getItem(this.globalKey) || '{}');
                        this.reactions.forEach(r => {
                            r.count = counts[r.emoji] || 0;
                        });
                    } catch(e) {}
                },

                react(emoji) {
                    const counts = {};
                    this.reactions.forEach(r => { counts[r.emoji] = r.count; });

                    // If already reacted with same emoji, toggle off
                    if (this.userReaction === emoji) {
                        counts[emoji] = Math.max(0, (counts[emoji] || 1) - 1);
                        this.userReaction = null;
                        localStorage.removeItem(this.storageKey);
                    } else {
                        // Remove previous reaction
                        if (this.userReaction) {
                            counts[this.userReaction] = Math.max(0, (counts[this.userReaction] || 1) - 1);
                        }
                        // Add new reaction
                        counts[emoji] = (counts[emoji] || 0) + 1;
                        this.userReaction = emoji;
                        localStorage.setItem(this.storageKey, emoji);
                    }

                    // Update counts
                    this.reactions.forEach(r => { r.count = counts[r.emoji] || 0; });
                    localStorage.setItem(this.globalKey, JSON.stringify(counts));
                }
            };
        }
        </script>

        {{-- ═══ Modal Signaler un problème ═══ --}}
        <div x-show="showReportModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="fixed inset-0 bg-black/80 backdrop-blur-sm transition-opacity" @click="showReportModal = false"></div>

            <div class="flex min-h-full items-center justify-center p-4 text-center">
                <div class="relative transform overflow-hidden rounded-2xl bg-[#14141c] border border-white/10 text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-lg p-5 sm:p-6"
                     @click.stop>
                    {{-- En-tête --}}
                    <div class="flex items-center justify-between pb-4 border-b border-white/10">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-crimson/15 border border-crimson/30 flex items-center justify-center text-crimson">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9"/></svg>
                            </div>
                            <div>
                                <h3 class="text-base sm:text-lg font-bold text-white leading-tight">Signaler un problème</h3>
                                <p class="text-xs text-mist mt-0.5">{{ $manga->title }} • Ch. {{ $chapter->number }}</p>
                            </div>
                        </div>
                        <button @click="showReportModal = false" class="text-mist hover:text-white p-1 rounded-lg hover:bg-white/5 transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    {{-- Formulaire --}}
                    <form @submit.prevent="submitReport()" class="mt-5 space-y-4">
                        {{-- Type d'erreur --}}
                        <div>
                            <label class="block text-xs font-semibold text-mist uppercase tracking-wider mb-2">Type d'anomalie</label>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                <label class="flex items-center gap-2.5 p-3 rounded-xl border cursor-pointer transition text-xs sm:text-sm"
                                       :class="reportType === 'page_manquante' ? 'bg-crimson/15 border-crimson text-white' : 'bg-white/[0.03] border-white/10 text-mist hover:border-white/20'">
                                    <input type="radio" value="page_manquante" x-model="reportType" class="hidden">
                                    <span>📄 Page manquante</span>
                                </label>
                                <label class="flex items-center gap-2.5 p-3 rounded-xl border cursor-pointer transition text-xs sm:text-sm"
                                       :class="reportType === 'image_corrompue' ? 'bg-crimson/15 border-crimson text-white' : 'bg-white/[0.03] border-white/10 text-mist hover:border-white/20'">
                                    <input type="radio" value="image_corrompue" x-model="reportType" class="hidden">
                                    <span>⚠️ Image illisible / blanche</span>
                                </label>
                                <label class="flex items-center gap-2.5 p-3 rounded-xl border cursor-pointer transition text-xs sm:text-sm"
                                       :class="reportType === 'ordre_inverse' ? 'bg-crimson/15 border-crimson text-white' : 'bg-white/[0.03] border-white/10 text-mist hover:border-white/20'">
                                    <input type="radio" value="ordre_inverse" x-model="reportType" class="hidden">
                                    <span>🔄 Pages inversées</span>
                                </label>
                                <label class="flex items-center gap-2.5 p-3 rounded-xl border cursor-pointer transition text-xs sm:text-sm"
                                       :class="reportType === 'mauvaise_traduction' ? 'bg-crimson/15 border-crimson text-white' : 'bg-white/[0.03] border-white/10 text-mist hover:border-white/20'">
                                    <input type="radio" value="mauvaise_traduction" x-model="reportType" class="hidden">
                                    <span>✏️ Traduction / Coquille</span>
                                </label>
                                <label class="flex items-center gap-2.5 p-3 rounded-xl border cursor-pointer transition text-xs sm:text-sm sm:col-span-2"
                                       :class="reportType === 'autre' ? 'bg-crimson/15 border-crimson text-white' : 'bg-white/[0.03] border-white/10 text-mist hover:border-white/20'">
                                    <input type="radio" value="autre" x-model="reportType" class="hidden">
                                    <span>❓ Autre souci technique</span>
                                </label>
                            </div>
                        </div>

                        {{-- Page concernée --}}
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="text-xs font-semibold text-mist uppercase tracking-wider">Page concernée</label>
                                <span class="text-xs text-mist/60">(optionnel)</span>
                            </div>
                            <input type="number" min="1" max="{{ $chapter->pages->count() }}" x-model="reportPage"
                                   placeholder="Numéro de la page"
                                   class="input-field !py-2 w-full text-sm">
                        </div>

                        {{-- Précisions --}}
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="text-xs font-semibold text-mist uppercase tracking-wider">Précisions</label>
                                <span class="text-xs text-mist/60">(optionnel)</span>
                            </div>
                            <textarea x-model="reportMessage" rows="3"
                                      placeholder="Ex: La bulle en haut à droite est illisible, la page 14 est en double..."
                                      class="input-field !py-2.5 w-full text-sm resize-none"></textarea>
                        </div>

                        {{-- Message d'erreur ou succès --}}
                        <div x-show="reportError" x-cloak class="p-3 rounded-xl bg-crimson/20 border border-crimson/40 text-crimson text-sm" x-text="reportError"></div>
                        <div x-show="reportSuccess" x-cloak class="p-3 rounded-xl bg-emerald-500/20 border border-emerald-500/40 text-emerald-400 text-sm flex items-center gap-2">
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            <span>Signalement envoyé ! Merci de votre aide.</span>
                        </div>

                        {{-- Actions du modal --}}
                        <div class="flex items-center justify-end gap-3 pt-3 border-t border-white/10">
                            <button type="button" @click="showReportModal = false" class="btn-ghost !py-2 !px-4 text-sm">
                                Annuler
                            </button>
                            <button type="submit" :disabled="reportSubmitting || reportSuccess"
                                    class="btn-primary !bg-crimson hover:!bg-crimson/90 !py-2 !px-5 text-sm font-semibold flex items-center gap-2">
                                <span x-show="!reportSubmitting">Envoyer le signalement</span>
                                <span x-show="reportSubmitting" x-cloak>Envoi...</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- ═══ Commentaires ═══ --}}
        <div class="max-w-3xl mx-auto mt-6 bg-[#131318] rounded-xl sm:rounded-2xl p-3.5 sm:p-7" style="border: 1px solid #252535;">
            <livewire:public.comment-section
                type="App\Models\Chapter"
                :id="$chapter->id"
            />
        </div>
    </div>

    <script>
    function chapterReader() {
        return {
            // State
            readMode: 'vertical',
            readerWidth: 85,
            pageGap: 0,
            isMobile: window.innerWidth < 768,
            showSettings: false,
            navHidden: false,
            lastScrollY: 0,
            progressPercent: 0,
            currentPage: 1,
            totalPages: {{ $chapter->pages->count() }},
            viewTracked: false,

            async trackReadingView() {
                if (this.viewTracked) return;
                this.viewTracked = true;
                try {
                    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';
                    await fetch("{{ route('chapter.track_view', $chapter->id) }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': token,
                            'Accept': 'application/json'
                        }
                    });
                } catch(e) {}
            },

            // Signalement state
            showReportModal: false,
            reportType: 'page_manquante',
            reportPage: 1,
            reportMessage: '',
            reportSubmitting: false,
            reportSuccess: false,
            reportError: '',

            openReportModal() {
                this.reportPage = this.currentPage || 1;
                this.reportError = '';
                this.reportSuccess = false;
                this.showReportModal = true;
            },

            async submitReport() {
                this.reportSubmitting = true;
                this.reportError = '';
                try {
                    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';
                    const res = await fetch("{{ route('chapter.report', $chapter->id) }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': token,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            type: this.reportType,
                            page_number: this.reportPage ? parseInt(this.reportPage) : null,
                            message: this.reportMessage
                        })
                    });
                    const data = await res.json();
                    if (res.ok && data.success) {
                        this.reportSuccess = true;
                        setTimeout(() => {
                            this.showReportModal = false;
                            this.reportSuccess = false;
                            this.reportMessage = '';
                        }, 2000);
                    } else {
                        this.reportError = data.message || 'Une erreur est survenue lors du signalement.';
                    }
                } catch(e) {
                    this.reportError = 'Erreur réseau, veuillez réessayer.';
                } finally {
                    this.reportSubmitting = false;
                }
            },
            pages: [
                @foreach($chapter->pages as $page)
                { page: {{ $page->page_number }}, src: "{{ Storage::url($page->image_path) }}" },
                @endforeach
            ],

            init() {
                this.isMobile = window.innerWidth < 768;
                window.addEventListener('resize', () => {
                    this.isMobile = window.innerWidth < 768;
                });

                // Charger les réglages
                try {
                    const settings = JSON.parse(localStorage.getItem('hiddenscan_reader') || '{}');
                    if (settings.readMode) this.readMode = settings.readMode;
                    if (settings.readerWidth) this.readerWidth = settings.readerWidth;
                    if (settings.pageGap !== undefined) this.pageGap = settings.pageGap;
                } catch(e) {}

                // Écouter le scroll
                window.addEventListener('scroll', () => this.onScroll(), { passive: true });

                // Sauvegarder la progression
                this.saveProgress();

                // Déclencher le comptage réaliste si le lecteur reste actif au moins 10 secondes
                setTimeout(() => {
                    this.trackReadingView();
                }, 10000);
            },

            onScroll() {
                const y = window.scrollY;

                // Auto-hide nav
                if (y > this.lastScrollY && y > 200) {
                    this.navHidden = true;
                } else {
                    this.navHidden = false;
                }
                this.lastScrollY = y;

                // Progress
                const docHeight = document.documentElement.scrollHeight - window.innerHeight;
                this.progressPercent = docHeight > 0 ? Math.min(100, (y / docHeight) * 100) : 0;

                // Comptage de vue réaliste dès 15% de défilement (lecture active)
                if (this.progressPercent > 15) {
                    this.trackReadingView();
                }

                // Save progress periodically
                if (this.progressPercent > 5) {
                    this.saveProgress();
                }
            },

            saveSettings() {
                try {
                    localStorage.setItem('hiddenscan_reader', JSON.stringify({
                        readMode: this.readMode,
                        readerWidth: this.readerWidth,
                        pageGap: this.pageGap,
                    }));
                } catch(e) {}
            },

            saveProgress() {
                try {
                    const data = JSON.parse(localStorage.getItem('hiddenscan') || '{}');
                    if (!data.progress) data.progress = {};
                    data.progress["{{ $manga->slug }}"] = {
                        chapter: {{ $chapter->number }},
                        slug: "{{ $chapter->slug }}",
                        title: "{{ addslashes($manga->title) }}",
                        cover: "{{ $manga->cover_image ? Storage::url($manga->cover_image) : '' }}",
                        chapterTitle: "{{ addslashes($chapter->title ?? 'Chapitre ' . $chapter->number) }}",
                        page: this.currentPage,
                        percent: Math.round(this.progressPercent),
                        updatedAt: new Date().toISOString()
                    };
                    if (!data.history) data.history = [];
                    data.history = data.history.filter(h => !(h.manga === "{{ $manga->slug }}" && (h.chapter === {{ $chapter->number }} || h.chapterSlug === "{{ $chapter->slug }}")));
                    data.history.unshift({
                        manga: "{{ $manga->slug }}",
                        mangaSlug: "{{ $manga->slug }}",
                        mangaTitle: "{{ addslashes($manga->title) }}",
                        chapter: {{ $chapter->number }},
                        chapterSlug: "{{ $chapter->slug }}",
                        chapterTitle: "{{ addslashes($chapter->title ?? 'Chapitre ' . $chapter->number) }}",
                        cover: "{{ $manga->cover_image ? Storage::url($manga->cover_image) : '' }}",
                        readAt: new Date().toISOString()
                    });
                    data.history = data.history.slice(0, 50);
                    localStorage.setItem('hiddenscan', JSON.stringify(data));
                } catch(e) {}
            },

            nextPage() {
                if (this.currentPage < this.totalPages) {
                    this.currentPage++;
                    this.trackReadingView();
                    this.saveProgress();
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                }
            },

            prevPage() {
                if (this.currentPage > 1) {
                    this.currentPage--;
                    this.saveProgress();
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                }
            },

            handleImageError(event) {
                const img = event.target;
                const wrapper = img.parentElement;
                if (!wrapper.querySelector('.retry-btn')) {
                    const div = document.createElement('div');
                    div.className = 'absolute inset-0 flex flex-col items-center justify-center bg-panel rounded-lg';
                    div.innerHTML = `
                        <svg class="w-10 h-10 text-mist/30 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        <p class="text-mist text-sm mb-2">Image non chargée</p>
                        <button class="retry-btn btn-secondary !py-1.5 !px-4 !text-xs" onclick="this.closest('div').previousElementSibling.src=this.closest('div').previousElementSibling.src+'?r='+Date.now();this.closest('div').remove();">Réessayer</button>
                    `;
                    wrapper.appendChild(div);
                }
            },

            handleImageLoad(pageNum) {
                // Track loaded pages for preloading
            }
        };
    }
    </script>

</x-layouts.public>