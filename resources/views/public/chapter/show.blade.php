@use('Illuminate\Support\Facades\Storage')
@php
    $isGuest = !auth()->check();
    $totalPages = $chapter->pages->count();
    $previewLimit = $totalPages > 1 ? min(4, max(1, $totalPages - 1)) : 1;
@endphp
<x-layouts.public :title="$manga->title . ' — Ch. ' . $chapter->number . ' | Hidden Scan'" :hide-navbar="true" :show-footer="false">

    <div x-data="chapterReader()" x-init="init()" 
         @mousemove="handleActivity()" 
         @touchstart="handleActivity()" 
         @scroll.window="handleActivity()"
         class="min-h-screen bg-[#000000] text-[var(--text-1)] select-none">
        
        {{-- ═══ Reader Header Controls (Auto-hide après 2s d'inactivité) ═══ --}}
        <div class="fixed top-0 left-0 right-0 z-50 h-14 bg-[#000000]/95 border-b border-[#1a1a1a] px-4 transition-all duration-200 flex items-center justify-between"
             :class="controlsVisible ? 'opacity-100 translate-y-0' : 'opacity-0 -translate-y-full pointer-events-none'">
            
            {{-- Back Arrow + Title --}}
            <div class="flex items-center gap-3">
                <a href="{{ route('manga.show', $manga->slug) }}" 
                   class="p-2 rounded-[4px] text-[var(--text-2)] hover:text-white hover:bg-[#1a1a1a] transition"
                   title="Retour à la fiche">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                </a>
                <span class="font-heading font-semibold text-[15px] text-white truncate max-w-[180px] sm:max-w-xs">
                    {{ $manga->title }}
                </span>
            </div>

            {{-- Chapter Selector & Prev/Next Nav --}}
            <div class="flex items-center gap-2">
                @if($prev)
                    <a href="{{ route('chapter.show', [$manga->slug, $prev->slug]) }}"
                       class="p-2 rounded-[4px] text-[var(--text-2)] hover:text-white hover:bg-[#1a1a1a] transition"
                       title="Chapitre précédent">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    </a>
                @endif

                <select @change="window.location.href = $event.target.value"
                        class="bg-[#141414] border border-[#2a2a2a] text-white text-caption font-semibold rounded-[4px] px-2.5 py-1.5 focus:border-[var(--accent)] outline-none cursor-pointer">
                    @foreach($manga->chapters as $c)
                        <option value="{{ route('chapter.show', [$manga->slug, $c->slug]) }}" {{ $c->id === $chapter->id ? 'selected' : '' }}>
                            Ch. {{ $c->number }} {{ $c->title ? '— ' . Str::limit($c->title, 18) : '' }}
                        </option>
                    @endforeach
                </select>

                @if($next)
                    <a href="{{ route('chapter.show', [$manga->slug, $next->slug]) }}"
                       class="p-2 rounded-[4px] text-[var(--text-2)] hover:text-white hover:bg-[#1a1a1a] transition"
                       title="Chapitre suivant">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                @endif
            </div>

            {{-- Page Counter, Défilement 2s & Settings Toggle --}}
            <div class="flex items-center gap-2 sm:gap-3">
                <button type="button" @click="toggleAutoScroll()"
                        class="flex items-center gap-1.5 px-2.5 py-1 rounded-[4px] text-[11px] font-semibold transition cursor-pointer"
                        :class="autoScrollActive ? 'bg-[var(--accent)] text-white shadow-md shadow-[var(--accent)]/30' : 'bg-[#1e1e1e] text-[var(--text-2)] hover:text-white'"
                        :title="autoScrollActive ? 'Arrêter le défilement automatique' : 'Activer le défilement automatique (2s)'">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path x-show="!autoScrollActive" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                        <path x-show="autoScrollActive" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6" />
                    </svg>
                    <span class="hidden sm:inline" x-text="autoScrollActive ? 'Défilement 2s (On)' : 'Défilement 2s'"></span>
                </button>

                <span class="text-caption text-[var(--text-2)] font-mono">
                    <span x-text="currentPage"></span> / <span x-text="totalPages"></span>
                </span>

                <button type="button" @click="showSettings = !showSettings"
                        class="p-2 rounded-[4px] text-[var(--text-2)] hover:text-white hover:bg-[#1a1a1a] transition cursor-pointer"
                        title="Réglages du lecteur">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </button>
            </div>
        </div>

        {{-- Panneau Réglages --}}
        <div x-show="showSettings" x-cloak 
             @click.outside="showSettings = false"
             class="fixed top-16 right-4 z-50 w-72 bg-[#141414] border border-[#2a2a2a] rounded-[6px] p-4 shadow-xl space-y-4">
            <div>
                <label class="text-caption text-[var(--text-3)] block mb-1.5 font-semibold">Mode de lecture</label>
                <div class="grid grid-cols-2 gap-2">
                    <button type="button" @click="readMode = 'vertical'; saveSettings()"
                            :class="readMode === 'vertical' ? 'bg-[var(--accent)] text-white' : 'bg-[#1e1e1e] text-[var(--text-2)]'"
                            class="py-1.5 rounded-[4px] text-caption font-semibold cursor-pointer">
                        Vertical
                    </button>
                    <button type="button" @click="readMode = 'page'; saveSettings()"
                            :class="readMode === 'page' ? 'bg-[var(--accent)] text-white' : 'bg-[#1e1e1e] text-[var(--text-2)]'"
                            class="py-1.5 rounded-[4px] text-caption font-semibold cursor-pointer">
                        Page par page
                    </button>
                </div>
            </div>

            <div>
                <label class="text-caption text-[var(--text-3)] block mb-1.5 font-semibold">Défilement automatique (2 secondes)</label>
                <button type="button" @click="toggleAutoScroll()"
                        :class="autoScrollActive ? 'bg-[var(--accent)] text-white' : 'bg-[#1e1e1e] text-[var(--text-2)]'"
                        class="w-full py-1.5 rounded-[4px] text-caption font-semibold cursor-pointer transition">
                    <span x-text="autoScrollActive ? 'Actif (Défilement toutes les 2s)' : 'Désactivé (Cliquer pour activer)'"></span>
                </button>
            </div>

            <div>
                <label class="text-caption text-[var(--text-3)] block mb-1.5 font-semibold">Largeur du lecteur : <span class="text-white" x-text="readerWidth + '%'"></span></label>
                <input type="range" min="50" max="100" x-model="readerWidth" @input="saveSettings()" class="w-full accent-[var(--accent)] cursor-pointer">
            </div>

            <div>
                <label class="text-caption text-[var(--text-3)] block mb-1.5 font-semibold">Espacement des pages : <span class="text-white" x-text="pageGap + 'px'"></span></label>
                <input type="range" min="0" max="24" step="4" x-model="pageGap" @input="saveSettings()" class="w-full accent-[var(--accent)] cursor-pointer">
            </div>
        </div>

        {{-- Barre de progression discrète en haut --}}
        <div class="fixed top-0 left-0 right-0 z-50 h-[2px] bg-transparent">
            <div class="h-full bg-[var(--accent)] transition-all duration-150" :style="'width: ' + progressPercent + '%'"></div>
        </div>

        {{-- ═══ Pages du Chapitre (Pure Black #000000 Background) ═══ --}}
        <div class="pt-14 pb-16">
            
            {{-- Mode Vertical (Webtoon) --}}
            <div x-show="readMode === 'vertical'" 
                 class="flex flex-col items-center mx-auto transition-all w-full"
                 :style="'max-width: ' + (isMobile ? '100%' : readerWidth + '%') + '; gap: ' + pageGap + 'px;'">
                @forelse($chapter->pages as $index => $page)
                    @if($isGuest && $index >= $previewLimit)
                        {{-- Aperçu flouté incitatif de la page suivante --}}
                        <div class="w-full relative overflow-hidden max-h-[320px] select-none pointer-events-none opacity-25 blur-sm">
                            <img src="{{ Storage::url($page->image_path) }}" 
                                 alt="Page {{ $page->page_number }}" 
                                 class="w-full block">
                            <div class="absolute inset-0 bg-gradient-to-b from-transparent via-[#000000]/60 to-[#000000]"></div>
                        </div>

                        {{-- Carte de verrouillage pour inciter à la connexion --}}
                        @include('public.chapter.partials.login-wall', ['previewLimit' => $previewLimit])
                        @break
                    @else
                        <div class="w-full relative" data-page="{{ $page->page_number }}">
                            <img src="{{ Storage::url($page->image_path) }}" 
                                 alt="Page {{ $page->page_number }}" 
                                 loading="{{ $index < 3 ? 'eager' : 'lazy' }}" 
                                 class="w-full block"
                                 @load="onImageLoad({{ $page->page_number }})">
                        </div>
                    @endif
                @empty
                    <div class="py-24 text-center text-caption text-[var(--text-3)]">
                        Aucune image disponible pour ce chapitre.
                    </div>
                @endforelse
            </div>

            {{-- Mode Page par Page --}}
            <div x-show="readMode === 'page'" x-cloak 
                 class="flex flex-col items-center mx-auto transition-all w-full"
                 :style="'max-width: ' + (isMobile ? '100%' : readerWidth + '%')">
                @if($chapter->pages->count())
                    @if($isGuest)
                        <div x-show="currentPage > {{ $previewLimit }}" class="w-full">
                            @include('public.chapter.partials.login-wall', ['previewLimit' => $previewLimit])
                        </div>

                        <div x-show="currentPage <= {{ $previewLimit }}" class="w-full relative cursor-pointer" @click="nextPage()">
                            <img :src="pages[currentPage - 1]?.src || ''" 
                                 :alt="'Page ' + currentPage" 
                                 class="w-full block">
                        </div>
                    @else
                        <div class="w-full relative cursor-pointer" @click="nextPage()">
                            <img :src="pages[currentPage - 1]?.src || ''" 
                                 :alt="'Page ' + currentPage" 
                                 class="w-full block">
                        </div>
                    @endif

                    <div class="flex items-center justify-center gap-4 mt-6">
                        <button type="button" @click="prevPage()" :disabled="currentPage <= 1" class="btn-secondary !text-caption disabled:opacity-30">
                            ← Précédente
                        </button>
                        <span class="text-caption font-mono text-[var(--text-2)]">
                            <span x-text="currentPage"></span> / <span x-text="totalPages"></span>
                        </span>
                        <button type="button" @click="nextPage()" :disabled="!isGuest && currentPage >= totalPages" class="btn-primary !text-caption disabled:opacity-30">
                            Suivante →
                        </button>
                    </div>
                @endif
            </div>

        </div>

        {{-- ═══ Pied de Chapitre : Navigation Suivant / Précédent ═══ --}}
        <div class="max-w-2xl mx-auto px-4 py-8 border-t border-[#1a1a1a] flex items-center justify-between gap-4">
            @if($prev)
                <a href="{{ route('chapter.show', [$manga->slug, $prev->slug]) }}" class="btn-secondary !text-caption">
                    ← Chapitre précédent
                </a>
            @else
                <div></div>
            @endif

            <a href="{{ route('manga.show', $manga->slug) }}" class="text-caption text-[var(--text-2)] hover:text-white transition">
                Fiche de la série
            </a>

            @if($next)
                <a href="{{ route('chapter.show', [$manga->slug, $next->slug]) }}" class="btn-primary !text-caption">
                    Chapitre suivant →
                </a>
            @else
                <div></div>
            @endif
        </div>

        {{-- ═══ Section Commentaires ═══ --}}
        <div class="max-w-3xl mx-auto px-4 pb-20">
            <div class="bg-[#141414] border border-[#2a2a2a] rounded-[6px] p-6">
                <h3 class="text-h3 text-white mb-4">Commentaires sur le chapitre {{ $chapter->number }}</h3>
                <livewire:public.comment-section
                    type="App\Models\Chapter"
                    :id="$chapter->id"
                />
            </div>
        </div>

    </div>

    {{-- Script Reader (Auto-hide controls after 2s idle) --}}
    <script>
    function chapterReader() {
        return {
            controlsVisible: true,
            idleTimer: null,
            autoScrollActive: false,
            autoScrollTimer: null,
            showSettings: false,
            readMode: localStorage.getItem('hs_read_mode') || 'vertical',
            readerWidth: parseInt(localStorage.getItem('hs_reader_width') || '75'),
            pageGap: parseInt(localStorage.getItem('hs_page_gap') || '0'),
            currentPage: 1,
            totalPages: {{ $totalPages }},
            isGuest: {{ $isGuest ? 'true' : 'false' }},
            previewLimit: {{ $previewLimit }},
            progressPercent: 0,
            isMobile: window.innerWidth < 768,
            pages: [
                @foreach($chapter->pages as $p)
                    { page: {{ $p->page_number }}, src: "{{ Storage::url($p->image_path) }}" },
                @endforeach
            ],

            init() {
                this.resetIdleTimer();
                window.addEventListener('resize', () => {
                    this.isMobile = window.innerWidth < 768;
                });
                window.addEventListener('scroll', () => {
                    this.updateScrollProgress();
                });

                // Enregistrement lecture
                if (window.HiddenScan) {
                    const data = window.HiddenScan._getData();
                    if (!data.progress) data.progress = {};
                    data.progress['{{ $manga->slug }}'] = {
                        mangaTitle: '{{ addslashes($manga->title) }}',
                        mangaSlug: '{{ $manga->slug }}',
                        chapter: '{{ $chapter->number }}',
                        slug: '{{ $chapter->slug }}',
                        updatedAt: new Date().toISOString()
                    };
                    window.HiddenScan._save(data);
                }

                // API view track
                const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                fetch('/chapitre/{{ $chapter->id }}/vue', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token || '' }
                }).catch(() => {});
            },

            handleActivity() {
                this.controlsVisible = true;
                this.resetIdleTimer();
            },

            resetIdleTimer() {
                clearTimeout(this.idleTimer);
                this.idleTimer = setTimeout(() => {
                    if (!this.showSettings) {
                        this.controlsVisible = false;
                    }
                }, 2000);
            },

            updateScrollProgress() {
                const totalHeight = document.documentElement.scrollHeight - window.innerHeight;
                if (totalHeight > 0) {
                    this.progressPercent = Math.min(100, Math.round((window.scrollY / totalHeight) * 100));
                }
            },

            onImageLoad(pageNum) {
                // Auto calculation
            },

            nextPage() {
                if (this.isGuest && this.currentPage >= this.previewLimit) {
                    this.currentPage = this.previewLimit + 1;
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                    return;
                }
                if (this.currentPage < this.totalPages) {
                    this.currentPage++;
                    this.progressPercent = Math.round((this.currentPage / this.totalPages) * 100);
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                }
            },

            prevPage() {
                if (this.currentPage > 1) {
                    this.currentPage--;
                    this.progressPercent = Math.round((this.currentPage / this.totalPages) * 100);
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                }
            },

            saveSettings() {
                localStorage.setItem('hs_read_mode', this.readMode);
                localStorage.setItem('hs_reader_width', this.readerWidth);
                localStorage.setItem('hs_page_gap', this.pageGap);
            },

            toggleAutoScroll() {
                this.autoScrollActive = !this.autoScrollActive;
                if (this.autoScrollActive) {
                    this.startAutoScroll();
                } else {
                    this.stopAutoScroll();
                }
            },

            startAutoScroll() {
                this.stopAutoScroll();
                this.autoScrollTimer = setInterval(() => {
                    if (this.readMode === 'page') {
                        if (this.currentPage < this.totalPages && (!this.isGuest || this.currentPage < this.previewLimit)) {
                            this.nextPage();
                        } else {
                            this.stopAutoScroll();
                            this.autoScrollActive = false;
                        }
                    } else {
                        const maxScroll = document.documentElement.scrollHeight - window.innerHeight;
                        if (window.scrollY < maxScroll - 40) {
                            window.scrollBy({ top: window.innerHeight * 0.85, behavior: 'smooth' });
                        } else {
                            this.stopAutoScroll();
                            this.autoScrollActive = false;
                        }
                    }
                }, 2000);
            },

            stopAutoScroll() {
                if (this.autoScrollTimer) {
                    clearInterval(this.autoScrollTimer);
                    this.autoScrollTimer = null;
                }
            }
        };
    }
    </script>

</x-layouts.public>