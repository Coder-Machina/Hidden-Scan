@use('Illuminate\Support\Facades\Storage')
<x-layouts.public :title="$manga->title . ' — Ch. ' . $chapter->number . ' | Hidden Scan'">

    <div x-data="chapterReader()" x-init="init()" class="animate-fade-in">

        {{-- ═══ Barre de navigation ═══ --}}
        <div class="sticky top-16 z-30 -mx-4 sm:-mx-6 px-4 sm:px-6 py-2 glass border-b border-line/30 transition-all duration-300"
             :class="navHidden && !showSettings ? '-translate-y-full opacity-0' : 'translate-y-0 opacity-100'"
             @mouseenter="navHidden = false">

            <div class="max-w-4xl mx-auto flex items-center justify-between gap-3">
                {{-- Retour --}}
                <a href="{{ route('manga.show', $manga->slug) }}" class="flex items-center gap-2 text-violet hover:text-violet-glow transition text-sm font-semibold flex-shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    <span class="hidden sm:inline">{{ Str::limit($manga->title, 25) }}</span>
                </a>

                {{-- Sélecteur de chapitre --}}
                <select @change="window.location.href=$event.target.value"
                    class="input-field !w-auto !py-1.5 text-sm text-center !rounded-full max-w-[200px]">
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
                            :class="showSettings ? 'text-violet bg-violet/10' : 'text-mist hover:text-chalk hover:bg-panel-hi'">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
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
                    <div>
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
        <div class="py-6">
            {{-- Mode vertical --}}
            <div x-show="readMode === 'vertical'" class="flex flex-col items-center mx-auto transition-all"
                 :style="'max-width:' + readerWidth + '%; gap:' + pageGap + 'px'"
                 id="reader-vertical">
                @forelse($chapter->pages as $pageIndex => $page)
                    <div class="w-full relative" data-page="{{ $page->page_number }}">
                        <img
                            src="{{ Storage::url($page->image_path) }}"
                            alt="Page {{ $page->page_number }}"
                            loading="{{ $pageIndex < 3 ? 'eager' : 'lazy' }}"
                            class="w-full"
                            @error="handleImageError($event)"
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
            <div x-show="readMode === 'page'" x-cloak class="flex flex-col items-center mx-auto transition-all"
                 :style="'max-width:' + readerWidth + '%'">
                @if($chapter->pages->count())
                    <div class="w-full relative">
                        <img
                            :src="pages[currentPage - 1]?.src || ''"
                            :alt="'Page ' + currentPage"
                            class="w-full cursor-pointer"
                            @click="nextPage()"
                            @error="handleImageError($event)"
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

        {{-- ═══ Commentaires ═══ --}}
        <div class="max-w-3xl mx-auto mt-4 bg-panel border border-line/50 rounded-xl p-5">
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
            pageGap: 2,
            showSettings: false,
            navHidden: false,
            lastScrollY: 0,
            progressPercent: 0,
            currentPage: 1,
            totalPages: {{ $chapter->pages->count() }},
            pages: [
                @foreach($chapter->pages as $page)
                { page: {{ $page->page_number }}, src: "{{ Storage::url($page->image_path) }}" },
                @endforeach
            ],

            init() {
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
                        page: this.currentPage,
                        percent: Math.round(this.progressPercent),
                        updatedAt: new Date().toISOString()
                    };
                    if (!data.history) data.history = [];
                    data.history = data.history.filter(h => !(h.manga === "{{ $manga->slug }}" && h.chapter === {{ $chapter->number }}));
                    data.history.unshift({ manga: "{{ $manga->slug }}", chapter: {{ $chapter->number }}, readAt: new Date().toISOString() });
                    data.history = data.history.slice(0, 50);
                    localStorage.setItem('hiddenscan', JSON.stringify(data));
                } catch(e) {}
            },

            nextPage() {
                if (this.currentPage < this.totalPages) {
                    this.currentPage++;
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