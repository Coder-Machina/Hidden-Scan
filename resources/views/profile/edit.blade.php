<x-layouts.public title="Profil — Hidden Scan">
    @php
        $initials = strtoupper(substr($user->name, 0, 2));
    @endphp

    <style>
        /* ── Cadres d'Avatar & Auras de Qi ── */
        .frame-novice {
            border: 2px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.4);
        }
        .frame-crimson-flame {
            border: 3px solid #ef4444;
            box-shadow: 0 0 20px rgba(239, 68, 68, 0.55), inset 0 0 12px rgba(239, 68, 68, 0.25);
            animation: pulse-red-flame 3s infinite alternate ease-in-out;
        }
        .frame-purple-lightning {
            border: 3px solid #8b5cf6;
            box-shadow: 0 0 24px rgba(139, 92, 246, 0.65), inset 0 0 14px rgba(139, 92, 246, 0.3);
            animation: pulse-purple-lightning 2.5s infinite alternate ease-in-out;
        }
        .frame-golden-celestial {
            border: 3px solid #f59e0b;
            box-shadow: 0 0 26px rgba(245, 158, 11, 0.7), inset 0 0 16px rgba(245, 158, 11, 0.35);
            animation: pulse-gold-celestial 2.8s infinite alternate ease-in-out;
        }
        .frame-abyssal-sovereign {
            border: 3px solid #ec4899;
            box-shadow: 0 0 28px rgba(236, 72, 153, 0.75), inset 0 0 16px rgba(236, 72, 153, 0.35);
            animation: pulse-abyssal 3.2s infinite alternate ease-in-out;
        }
        .frame-divine-sovereign {
            border: 3.5px solid #ffd700;
            box-shadow: 0 0 32px rgba(255, 215, 0, 0.85), inset 0 0 20px rgba(255, 215, 0, 0.45);
            animation: pulse-divine 2s infinite alternate ease-in-out;
        }

        @keyframes pulse-red-flame {
            0% { box-shadow: 0 0 14px rgba(239, 68, 68, 0.4); }
            100% { box-shadow: 0 0 26px rgba(239, 68, 68, 0.75); }
        }
        @keyframes pulse-purple-lightning {
            0% { box-shadow: 0 0 16px rgba(139, 92, 246, 0.4); }
            100% { box-shadow: 0 0 28px rgba(139, 92, 246, 0.85); }
        }
        @keyframes pulse-gold-celestial {
            0% { box-shadow: 0 0 16px rgba(245, 158, 11, 0.45); }
            100% { box-shadow: 0 0 32px rgba(245, 158, 11, 0.9); }
        }
        @keyframes pulse-abyssal {
            0% { box-shadow: 0 0 18px rgba(236, 72, 153, 0.45); }
            100% { box-shadow: 0 0 34px rgba(236, 72, 153, 0.9); }
        }
        @keyframes pulse-divine {
            0% { box-shadow: 0 0 20px rgba(255, 215, 0, 0.6); }
            100% { box-shadow: 0 0 40px rgba(255, 215, 0, 1); }
        }

        /* ── Scrollbars fines personnalisées ── */
        .custom-scrollbar::-webkit-scrollbar {
            height: 6px;
            width: 6px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.2);
            border-radius: 9999px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.15);
            border-radius: 9999px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.3);
        }
    </style>

    <script>
        function profileComponent() {
            return {
                currentTab: '{{ $defaultTab ?? "overview" }}',
                murimSubtab: 'guide',
                settingsSubtab: 'profile',
                avatarPreview: @json($user->avatar ? $user->avatar_url : ''),
                bannerPreview: @json($user->banner ? $user->banner_url : ''),
                avatarData: '',
                bannerData: '',
                favorites: [],
                history: [],
                serverProgress: {},
                libTab: 'favorites',
                mangasLookup: @json($mangasLookup ?? []),

                // ═══ Top 5 Séries & Tier List ═══
                topMangasIds: @json($topMangasIds ?? []),
                topMangasMap: @json($topMangasMap ?? []),
                tierList: @json($tierListConfig),
                allMangas: @json($allMangasData ?? []),

                // ═══ Sélecteur Modal Partagé ═══
                selectorOpen: false,
                selectorTarget: null,
                selectorSearch: '',
                toastMessage: '',
                toastVisible: false,

                // ═══ Bête Spirituelle & Cadres & Carte Martiale ═══
                currentFrame: @json($currentFrame ?? 'novice'),
                avatarFrames: @json($avatarFrames),
                spiritualBeast: @json($user->spiritual_beast ?? 'shadow_crow'),
                spiritualBeasts: @json($spiritualBeasts),
                userBeastStatus: @json($userBeastStatus),
                cardModalOpen: false,
                exportingCard: false,

                init() {
                    // Normalisation des onglets précédents ou alias
                    if (this.currentTab === 'activity') {
                        this.currentTab = 'overview';
                    } else if (['beast', 'auras', 'tierlist'].includes(this.currentTab)) {
                        this.murimSubtab = this.currentTab === 'tierlist' ? 'tierlist' : (this.currentTab === 'auras' ? 'auras' : 'beast');
                        this.currentTab = 'murim';
                    } else if (this.currentTab === 'edit') {
                        this.currentTab = 'settings';
                        this.settingsSubtab = 'profile';
                    }

                    this.loadData();
                    this.fetchProgress();

                    // Restauration résiliente locale si disponible
                    if (window.HiddenScan) {
                        const local = window.HiddenScan.getProfile();
                        if (!this.avatarPreview && (local.avatar_url || local.avatar)) {
                            this.avatarPreview = local.avatar_url || local.avatar;
                            this.avatarData = local.avatar || local.avatar_url || '';
                        }
                        if (!this.bannerPreview && (local.banner_url || local.banner)) {
                            this.bannerPreview = local.banner_url || local.banner;
                            this.bannerData = local.banner || local.banner_url || '';
                        }
                        window.HiddenScan.syncProfile();
                    }
                },

                showToast(msg) {
                    this.toastMessage = msg;
                    this.toastVisible = true;
                    setTimeout(() => { this.toastVisible = false; }, 3500);
                },

                switchTab(name) {
                    this.currentTab = name;
                    window.history.replaceState(null, '', '?tab=' + name);
                },

                openSelector(target) {
                    this.selectorTarget = target;
                    this.selectorSearch = '';
                    this.selectorOpen = true;
                    this.$nextTick(() => {
                        if (this.$refs.searchInput) this.$refs.searchInput.focus();
                    });
                },

                closeSelector() {
                    this.selectorOpen = false;
                    this.selectorTarget = null;
                },

                get filteredMangas() {
                    if (!this.selectorSearch.trim()) return this.allMangas;
                    const q = this.selectorSearch.toLowerCase();
                    return this.allMangas.filter(m => m.title.toLowerCase().includes(q));
                },

                getManga(id) {
                    return this.allMangas.find(m => m.id === parseInt(id)) || this.topMangasMap[id] || null;
                },

                async selectMangaForTarget(manga) {
                    if (!this.selectorTarget) return;

                    if (this.selectorTarget.startsWith('top:')) {
                        const slot = parseInt(this.selectorTarget.split(':')[1]);
                        while (this.topMangasIds.length <= slot) {
                            this.topMangasIds.push(null);
                        }
                        this.topMangasIds[slot] = manga.id;
                        this.topMangasMap[manga.id] = manga;
                        await this.saveTopMangas();
                    } else if (this.selectorTarget.startsWith('tier:')) {
                        const tierKey = this.selectorTarget.split(':')[1];
                        if (!this.tierList[tierKey]) this.tierList[tierKey] = [];
                        if (!this.tierList[tierKey].includes(manga.id)) {
                            this.tierList[tierKey].push(manga.id);
                            await this.saveTierList();
                        } else {
                            this.showToast('Cette série est déjà dans ce rang !');
                        }
                    }

                    this.closeSelector();
                },

                async removeTopSlot(slotIndex) {
                    this.topMangasIds.splice(slotIndex, 1);
                    await this.saveTopMangas();
                },

                async removeFromTier(tierKey, mangaId) {
                    if (this.tierList[tierKey]) {
                        this.tierList[tierKey] = this.tierList[tierKey].filter(id => id !== mangaId);
                        await this.saveTierList();
                    }
                },

                async saveTierList() {
                    try {
                        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                        const res = await fetch('/api/profile/tier-list', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': token || '',
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({ tier_list: this.tierList })
                        });
                        const data = await res.json();
                        if (res.ok && data.success) {
                            this.showToast('Tier List enregistrée avec succès !');
                        }
                    } catch(e) {
                        this.showToast('Erreur lors de l\'enregistrement.');
                    }
                },

                async saveTopMangas() {
                    try {
                        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                        const res = await fetch('/api/profile/top-mangas', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': token || '',
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({ top_mangas: this.topMangasIds })
                        });
                        const data = await res.json();
                        if (res.ok && data.success) {
                            this.showToast('Top 5 Séries mis à jour !');
                        }
                    } catch(e) {
                        this.showToast('Erreur lors de la mise à jour du Top 5.');
                    }
                },

                getFrameClass(frameKey) {
                    const k = frameKey || this.currentFrame || 'novice';
                    return 'frame-' + k.replace(/_/g, '-');
                },

                async equipFrame(frameKey) {
                    try {
                        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                        const res = await fetch('/api/profile/avatar-frame', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': token || '',
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({ avatar_frame: frameKey })
                        });
                        const data = await res.json();
                        if (res.ok && data.success) {
                            this.currentFrame = frameKey;
                            this.showToast(data.message || 'Aura équipée avec succès !');
                        } else {
                            this.showToast(data.message || 'Impossible d\'équiper cette aura.');
                        }
                    } catch(e) {
                        this.showToast('Erreur lors de la mise à jour de l\'aura.');
                    }
                },

                async chooseBeast(beastKey) {
                    try {
                        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                        const res = await fetch('/api/profile/spiritual-beast', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': token || '',
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({ spiritual_beast: beastKey })
                        });
                        const data = await res.json();
                        if (res.ok && data.success) {
                            this.spiritualBeast = beastKey;
                            this.userBeastStatus = data.beast_status;
                            this.showToast(data.message || 'Pacte spirituel scellé !');
                        } else {
                            this.showToast(data.message || 'Erreur pacte.');
                        }
                    } catch(e) {
                        this.showToast('Erreur lors du pacte spirituel.');
                    }
                },

                openCardModal() {
                    this.cardModalOpen = true;
                },

                closeCardModal() {
                    this.cardModalOpen = false;
                },

                copyProfileCardLink() {
                    const text = `🥋 Cultivateur ${this.$el.dataset.name || '{{ $user->name }}'} | Rang : {{ $murimStatus['current']['title'] }} ({{ $readCount }} chapitres lus sur Hidden Scan)`;
                    navigator.clipboard.writeText(text).then(() => {
                        this.showToast('Statut copié dans le presse-papier !');
                    }).catch(() => {
                        this.showToast('Impossible de copier automatiquement.');
                    });
                },

                fetchProgress() {
                    fetch('/api/progress')
                        .then(r => r.json())
                        .then(data => {
                            if (data && data.success && data.progress) {
                                this.serverProgress = data.progress;
                            }
                        })
                        .catch(err => console.debug('Profile progress fetch failed:', err));
                },

                loadData() {
                    if (window.HiddenScan) {
                        const rawFavs = window.HiddenScan.getFavorites() || [];
                        this.favorites = rawFavs.map(fav => {
                            const lookup = this.mangasLookup[fav.slug] || {};
                            return {
                                ...fav,
                                title: lookup.title || fav.title || fav.slug,
                                cover: lookup.cover || fav.cover || '',
                            };
                        }).sort((a, b) => new Date(b.addedAt) - new Date(a.addedAt));

                        const rawHistory = window.HiddenScan.getHistory() || [];
                        const progress = window.HiddenScan.getProgress() || {};
                        
                        this.history = rawHistory.map(item => {
                            const mSlug = item.mangaSlug || item.manga || '';
                            const lookup = this.mangasLookup[mSlug] || {};
                            const fav = this.favorites.find(f => f.slug === mSlug);
                            const prog = progress[mSlug];
                            const cNum = item.chapter || (prog ? prog.chapter : 1);
                            const cSlug = item.chapterSlug || (prog ? prog.slug : ('chapitre-' + cNum));
                            const title = lookup.title || item.mangaTitle || (fav ? fav.title : (prog ? prog.title : mSlug.replace(/-/g, ' ')));
                            const cTitle = item.chapterTitle || (prog ? prog.chapterTitle : ('Chapitre ' + cNum));
                            const cover = lookup.cover || item.cover || (fav ? fav.cover : (prog ? prog.cover : ''));

                            return {
                                mangaSlug: mSlug,
                                chapterSlug: cSlug,
                                mangaTitle: title,
                                chapterTitle: cTitle,
                                cover: cover,
                                readAt: item.readAt || new Date().toISOString()
                            };
                        });
                    }
                },

                removeFavorite(slug) {
                    if (window.HiddenScan && confirm('Retirer cette série des favoris ?')) {
                        window.HiddenScan.toggleFavorite(slug);
                        this.loadData();
                        this.showToast('Série retirée des favoris.');
                    }
                },

                handleAvatar(event) {
                    const file = event.target.files[0];
                    if (file) {
                        this.avatarPreview = URL.createObjectURL(file);
                        this.compressAndConvert(file, 300, 300, 0.85).then(dataUrl => {
                            if (dataUrl) {
                                this.avatarData = dataUrl;
                                this.avatarPreview = dataUrl;
                                if (window.HiddenScan) {
                                    window.HiddenScan.saveProfile({ avatar: dataUrl, avatar_url: dataUrl });
                                }
                            }
                        });
                    }
                },

                handleBanner(event) {
                    const file = event.target.files[0];
                    if (file) {
                        this.bannerPreview = URL.createObjectURL(file);
                        this.compressAndConvert(file, 1200, 400, 0.85).then(dataUrl => {
                            if (dataUrl) {
                                this.bannerData = dataUrl;
                                this.bannerPreview = dataUrl;
                                if (window.HiddenScan) {
                                    window.HiddenScan.saveProfile({ banner: dataUrl, banner_url: dataUrl });
                                }
                            }
                        });
                    }
                },

                compressAndConvert(file, maxWidth, maxHeight, quality) {
                    return new Promise((resolve) => {
                        const reader = new FileReader();
                        reader.onload = (e) => {
                            const img = new Image();
                            img.onload = () => {
                                const canvas = document.createElement('canvas');
                                let width = img.width;
                                let height = img.height;
                                if (width > maxWidth || height > maxHeight) {
                                    const ratio = Math.min(maxWidth / width, maxHeight / height);
                                    width = Math.round(width * ratio);
                                    height = Math.round(height * ratio);
                                }
                                canvas.width = width;
                                canvas.height = height;
                                const ctx = canvas.getContext('2d');
                                ctx.drawImage(img, 0, 0, width, height);
                                resolve(canvas.toDataURL('image/jpeg', quality));
                            };
                            img.onerror = () => resolve(e.target.result);
                            img.src = e.target.result;
                        };
                        reader.onerror = () => resolve(null);
                        reader.readAsDataURL(file);
                    });
                }
            };
        }
    </script>

    <div class="w-full max-w-7xl mx-auto px-3 sm:px-4 md:px-6 py-4 md:py-6" x-data="profileComponent()">

        {{-- ═══════════════════════════════════════════════════════════
             1. HERO BANNER & PROFIL (Style Poseidon Scans & Aniverse)
             ═══════════════════════════════════════════════════════════ --}}
        <div class="relative w-full rounded-3xl overflow-hidden border border-white/10 bg-[#0c0d14] shadow-2xl mb-6">
            {{-- Bannière Arrière-Plan --}}
            <div class="relative w-full h-44 sm:h-56 md:h-64 lg:h-72 overflow-hidden bg-[#090910]">
                <template x-if="!bannerPreview">
                    <svg class="w-full h-full object-cover" viewBox="0 0 1200 300" preserveAspectRatio="xMidYMid slice" xmlns="http://www.w3.org/2000/svg">
                        <defs>
                            <radialGradient id="murimBannerGrad" cx="70%" cy="25%" r="80%">
                                <stop offset="0%" stop-color="#b91c1c" stop-opacity="0.8"/>
                                <stop offset="35%" stop-color="#3b0764" stop-opacity="0.7"/>
                                <stop offset="70%" stop-color="#111827" stop-opacity="0.95"/>
                                <stop offset="100%" stop-color="#0a0a10" stop-opacity="1"/>
                            </radialGradient>
                            <radialGradient id="murimGlow" cx="80%" cy="30%" r="50%">
                                <stop offset="0%" stop-color="#ff2a55" stop-opacity="0.5"/>
                                <stop offset="100%" stop-color="#ff2a55" stop-opacity="0"/>
                            </radialGradient>
                        </defs>
                        <rect width="1200" height="300" fill="url(#murimBannerGrad)" />
                        <rect width="1200" height="300" fill="url(#murimGlow)" />
                        {{-- Constellations d'étoiles --}}
                        <circle cx="80" cy="50" r="1.5" fill="#ffffff" opacity="0.8" />
                        <circle cx="220" cy="140" r="1.2" fill="#ffffff" opacity="0.6" />
                        <circle cx="360" cy="70" r="1.8" fill="#ffffff" opacity="0.9" />
                        <circle cx="500" cy="180" r="1.0" fill="#ffffff" opacity="0.5" />
                        <circle cx="680" cy="80" r="2.0" fill="#ffffff" opacity="0.85" />
                        <circle cx="850" cy="160" r="1.4" fill="#ffffff" opacity="0.7" />
                        <circle cx="1020" cy="60" r="1.6" fill="#ffffff" opacity="0.9" />
                        <circle cx="1120" cy="200" r="1.2" fill="#ffffff" opacity="0.6" />
                        {{-- Corps céleste nébuleux --}}
                        <circle cx="820" cy="110" r="60" fill="#f59e0b" opacity="0.08" filter="blur(30px)" />
                        <circle cx="300" cy="90" r="45" fill="#8b5cf6" opacity="0.1" filter="blur(25px)" />
                    </svg>
                </template>
                <template x-if="bannerPreview">
                    <img :src="bannerPreview" class="w-full h-full object-cover transition-opacity duration-300" alt="Bannière de profil">
                </template>
                {{-- Dégradé d'assombrissement inférieur fluide --}}
                <div class="absolute inset-0 bg-gradient-to-t from-[#0c0d14] via-[#0c0d14]/60 to-transparent"></div>
            </div>

            {{-- Contenu flottant du profil (Avatar + Nom + Badges + Actions) --}}
            <div class="relative px-4 sm:px-6 md:px-8 pb-6 pt-0 -mt-16 sm:-mt-20 md:-mt-24 z-10 flex flex-col md:flex-row md:items-end md:justify-between gap-6">
                {{-- Gauche: Avatar & Informations Utilisateur --}}
                <div class="flex flex-col sm:flex-row items-center sm:items-end gap-5 text-center sm:text-left min-w-0">
                    {{-- Avatar avec Aura de Qi --}}
                    <div class="relative flex-shrink-0 group">
                        <div class="w-28 h-28 sm:w-32 sm:h-32 md:w-36 md:h-36 rounded-full p-1 bg-[#12131f] flex items-center justify-center transition-all duration-300 relative" :class="getFrameClass(currentFrame)">
                            <div class="w-full h-full rounded-full overflow-hidden bg-[#181926] flex items-center justify-center">
                                <template x-if="!avatarPreview">
                                    <span class="text-3xl sm:text-4xl font-black text-amber-300 tracking-wider select-none">{{ $initials }}</span>
                                </template>
                                <template x-if="avatarPreview">
                                    <img :src="avatarPreview" class="w-full h-full object-cover" alt="{{ $user->name }}">
                                </template>
                            </div>
                        </div>

                        {{-- Bouton raccourci de changement d'aura --}}
                        <button 
                            type="button" 
                            @click="switchTab('murim'); murimSubtab = 'auras'" 
                            class="absolute -bottom-1 -right-1 w-8 h-8 rounded-full bg-[#181826] border border-amber-400/40 text-amber-300 hover:text-white hover:bg-amber-500/20 flex items-center justify-center text-xs shadow-lg transition" 
                            title="Changer d'Aura Martiale"
                        >
                            ✨
                        </button>
                    </div>

                    {{-- Nom, Titre & Badges --}}
                    <div class="flex-1 min-w-0 pb-1">
                        <div class="flex flex-wrap items-center justify-center sm:justify-start gap-2.5 mb-1.5">
                            <h1 class="text-2xl sm:text-3xl font-black text-white tracking-tight truncate drop-shadow-md">
                                {{ $user->name }}
                            </h1>

                            {{-- Badge de Rang Murim --}}
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-black shadow-lg" style="background-color: {{ $murimStatus['current']['color'] }}25; color: {{ $murimStatus['current']['color'] }}; border: 1px solid {{ $murimStatus['current']['color'] }}60;">
                                <span>{{ $murimStatus['current']['badge'] }}</span>
                                <span>{{ $murimStatus['current']['title'] }}</span>
                            </span>

                            {{-- Badge de Staff (si staff) --}}
                            @if($user->staff_badge)
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wider border {{ $user->staff_badge['bg'] }} {{ $user->staff_badge['text'] }} {{ $user->staff_badge['border'] }} shadow-sm">
                                    {{ $user->staff_badge['name'] }}
                                </span>
                            @endif
                        </div>

                        {{-- Ligne de chips d'informations secondaires --}}
                        <div class="flex flex-wrap items-center justify-center sm:justify-start gap-x-3 gap-y-1 text-xs text-mist font-semibold">
                            <span class="flex items-center gap-1 text-chalk">
                                <span>📅</span>
                                <span>Membre depuis {{ $user->created_at ? $user->created_at->translatedFormat('M Y') : '2024' }}</span>
                            </span>
                            <span class="text-white/20">•</span>
                            <span class="flex items-center gap-1 text-amber-400">
                                <span>🔥</span>
                                <span>{{ $user->streak ?? 0 }}j d'assiduité</span>
                            </span>
                            <span class="text-white/20">•</span>
                            <span class="flex items-center gap-1 text-emerald-400" title="Boucliers Spirituels anti-perte de streak">
                                <span>🛡️</span>
                                <span>{{ $qiShields }}/2 Bouclier(s)</span>
                            </span>
                            <span class="text-white/20">•</span>
                            <span class="flex items-center gap-1 text-cyan-400">
                                <span>⚡</span>
                                <span>{{ number_format($user->xp ?? ($readCount * 15)) }} Qi (XP)</span>
                            </span>
                        </div>

                        {{-- Biographie courte si renseignée --}}
                        @if($user->bio)
                            <p class="text-xs text-mist/90 italic mt-2 line-clamp-2 max-w-xl">
                                « {{ $user->bio }} »
                            </p>
                        @endif
                    </div>
                </div>

                {{-- Droite: Boutons d'Action Rapide (Poseidon / Aniverse style) --}}
                <div class="flex items-center justify-center sm:justify-end gap-2.5 flex-shrink-0">
                    <button 
                        type="button" 
                        @click="openCardModal()" 
                        class="px-4 py-2.5 rounded-xl bg-gradient-to-r from-amber-500/20 via-amber-500/10 to-transparent border border-amber-500/40 hover:border-amber-400 hover:bg-amber-500/25 text-amber-300 hover:text-white text-xs font-black transition-all flex items-center gap-2 shadow-lg shadow-black/40 group"
                    >
                        <span class="text-sm group-hover:scale-110 transition-transform">🎴</span>
                        <span>Carte Martiale</span>
                    </button>

                    <button 
                        type="button" 
                        @click="switchTab('settings'); settingsSubtab = 'profile'" 
                        class="px-4 py-2.5 rounded-xl bg-[#161726] border border-white/10 hover:border-white/25 hover:bg-[#1d1f33] text-white text-xs font-bold transition flex items-center gap-2 shadow-lg shadow-black/30"
                    >
                        <svg class="w-4 h-4 text-mist" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        <span>Modifier profil</span>
                    </button>

                    <button 
                        type="button" 
                        @click="switchTab('settings'); settingsSubtab = 'security'" 
                        class="px-3 py-2.5 rounded-xl bg-[#161726] border border-white/10 hover:border-crimson/50 hover:bg-[#1d1f33] text-white text-xs font-bold transition flex items-center gap-1.5 shadow-lg shadow-black/30" 
                        title="Paramètres & Sécurité"
                    >
                        <svg class="w-4 h-4 text-[#ff2a55]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"></circle><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                        <span class="hidden sm:inline">Paramètres</span>
                    </button>
                </div>
            </div>
        </div>

        {{-- ═══════════════════════════════════════════════════════════
             2. GRILLE DES 5 STATISTIQUES CLÉS (Glassmorphism Cards)
             ═══════════════════════════════════════════════════════════ --}}
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3.5 mb-6">
            {{-- Stat 1 : Chapitres Lus --}}
            <div class="p-4 rounded-2xl bg-[#11121d] border border-white/[0.08] hover:border-white/20 transition-all flex items-center gap-3.5 shadow-lg group">
                <div class="w-12 h-12 rounded-xl bg-crimson/10 border border-crimson/25 flex items-center justify-center text-xl text-crimson group-hover:scale-110 transition-transform">
                    📖
                </div>
                <div class="min-w-0">
                    <span class="text-xl sm:text-2xl font-black text-white block tracking-tight">{{ number_format($readCount) }}</span>
                    <span class="text-[11px] font-bold text-mist uppercase tracking-wider block truncate">Chapitres Lus</span>
                </div>
            </div>

            {{-- Stat 2 : Favoris --}}
            <div class="p-4 rounded-2xl bg-[#11121d] border border-white/[0.08] hover:border-white/20 transition-all flex items-center gap-3.5 shadow-lg group">
                <div class="w-12 h-12 rounded-xl bg-pink-500/10 border border-pink-500/25 flex items-center justify-center text-xl text-pink-400 group-hover:scale-110 transition-transform">
                    ❤️
                </div>
                <div class="min-w-0">
                    <span class="text-xl sm:text-2xl font-black text-white block tracking-tight" x-text="favorites.length">{{ $favoritesCount }}</span>
                    <span class="text-[11px] font-bold text-mist uppercase tracking-wider block truncate">Favoris</span>
                </div>
            </div>

            {{-- Stat 3 : Rang Murim --}}
            <div class="p-4 rounded-2xl bg-[#11121d] border border-white/[0.08] hover:border-white/20 transition-all flex items-center gap-3.5 shadow-lg group">
                <div class="w-12 h-12 rounded-xl bg-amber-500/10 border border-amber-500/25 flex items-center justify-center text-xl text-amber-400 group-hover:scale-110 transition-transform">
                    🥋
                </div>
                <div class="min-w-0">
                    <span class="text-sm font-black text-amber-300 block truncate leading-tight">{{ $murimStatus['current']['title'] }}</span>
                    <span class="text-[11px] font-bold text-mist uppercase tracking-wider block truncate mt-0.5">
                        {{ $murimStatus['percent'] }}% de Qi
                    </span>
                </div>
            </div>

            {{-- Stat 4 : Flamme d'Assiduité --}}
            <div class="p-4 rounded-2xl bg-[#11121d] border border-white/[0.08] hover:border-white/20 transition-all flex items-center gap-3.5 shadow-lg group">
                <div class="w-12 h-12 rounded-xl bg-orange-500/10 border border-orange-500/25 flex items-center justify-center text-xl text-orange-400 group-hover:scale-110 transition-transform">
                    🔥
                </div>
                <div class="min-w-0">
                    <span class="text-xl sm:text-2xl font-black text-white block tracking-tight">{{ $user->streak ?? 0 }} jours</span>
                    <span class="text-[11px] font-bold text-mist uppercase tracking-wider block truncate">Flamme Martiale</span>
                </div>
            </div>

            {{-- Stat 5 : Commentaires & Échanges --}}
            <div class="col-span-2 sm:col-span-1 lg:col-span-1 p-4 rounded-2xl bg-[#11121d] border border-white/[0.08] hover:border-white/20 transition-all flex items-center gap-3.5 shadow-lg group">
                <div class="w-12 h-12 rounded-xl bg-indigo-500/10 border border-indigo-500/25 flex items-center justify-center text-xl text-indigo-400 group-hover:scale-110 transition-transform">
                    💬
                </div>
                <div class="min-w-0">
                    <span class="text-xl sm:text-2xl font-black text-white block tracking-tight">{{ $commentsCount }}</span>
                    <span class="text-[11px] font-bold text-mist uppercase tracking-wider block truncate">Commentaires ({{ $likesReceived }} 👍)</span>
                </div>
            </div>
        </div>

        {{-- ═══════════════════════════════════════════════════════════
             3. BARRE D'ONGLETS MODERNE (5 Onglets Épurés & Clairs)
             ═══════════════════════════════════════════════════════════ --}}
        <div class="flex items-center gap-1.5 p-1.5 rounded-2xl bg-[#11121d] border border-white/[0.08] mb-6 overflow-x-auto custom-scrollbar">
            {{-- Onglet 1 : Vue d'ensemble --}}
            <button 
                type="button" 
                @click="switchTab('overview')"
                class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs sm:text-sm font-extrabold transition-all whitespace-nowrap cursor-pointer"
                :class="currentTab === 'overview' ? 'bg-[#ff2a55] text-white shadow-lg shadow-red-500/25' : 'text-mist hover:text-white hover:bg-white/5'"
            >
                <span>👁️</span>
                <span>Vue d'ensemble</span>
            </button>

            {{-- Onglet 2 : Favoris & Historique --}}
            <button 
                type="button" 
                @click="switchTab('library')"
                class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs sm:text-sm font-extrabold transition-all whitespace-nowrap cursor-pointer"
                :class="currentTab === 'library' ? 'bg-[#ff2a55] text-white shadow-lg shadow-red-500/25' : 'text-mist hover:text-white hover:bg-white/5'"
            >
                <span>⭐</span>
                <span>Favoris & Historique</span>
                <span class="px-2 py-0.5 rounded-full text-[10px] bg-black/30 font-bold" x-text="favorites.length">{{ $favoritesCount }}</span>
            </button>

            {{-- Onglet 3 : Voie Martiale & Murim --}}
            <button 
                type="button" 
                @click="switchTab('murim')"
                class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs sm:text-sm font-extrabold transition-all whitespace-nowrap cursor-pointer"
                :class="currentTab === 'murim' ? 'bg-gradient-to-r from-amber-600 to-amber-500 text-black shadow-lg shadow-amber-500/25' : 'text-mist hover:text-white hover:bg-white/5'"
            >
                <span>🥋</span>
                <span>Voie Martiale & Murim</span>
            </button>

            {{-- Onglet 4 : Classement --}}
            <button 
                type="button" 
                @click="switchTab('leaderboard')"
                class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs sm:text-sm font-extrabold transition-all whitespace-nowrap cursor-pointer"
                :class="currentTab === 'leaderboard' ? 'bg-[#ff2a55] text-white shadow-lg shadow-red-500/25' : 'text-mist hover:text-white hover:bg-white/5'"
            >
                <span>🏆</span>
                <span>Classement</span>
            </button>

            {{-- Onglet 5 : Paramètres & Sécurité --}}
            <button 
                type="button" 
                @click="switchTab('settings')"
                class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs sm:text-sm font-extrabold transition-all whitespace-nowrap cursor-pointer ml-auto"
                :class="currentTab === 'settings' ? 'bg-[#ff2a55] text-white shadow-lg shadow-red-500/25' : 'text-mist hover:text-white hover:bg-white/5'"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"></circle><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                <span>Paramètres</span>
            </button>
        </div>

        {{-- ═══════════════════════════════════════════════════════════
             4. CONTENU PRINCIPAL DES 5 ONGLETS
             ═══════════════════════════════════════════════════════════ --}}

        {{-- ──────────────────────────────────────────────────────────
             ONGLET 1 : VUE D'ENSEMBLE (OVERVIEW)
             ────────────────────────────────────────────────────────── --}}
        <div x-show="currentTab === 'overview'" x-cloak class="space-y-6">

            {{-- 1.A. Encart Percée de Qi & Royaume Actuel --}}
            <div class="p-6 md:p-7 rounded-3xl bg-gradient-to-br from-[#151624] via-[#10111a] to-[#0c0d14] border border-amber-500/25 shadow-xl relative overflow-hidden">
                <div class="absolute -right-10 -bottom-10 w-56 h-56 bg-amber-500/10 rounded-full blur-3xl pointer-events-none"></div>

                <div class="flex flex-col md:flex-row md:items-center justify-between gap-5 mb-5 relative z-10">
                    <div class="flex items-center gap-4">
                        <div class="w-16 h-16 rounded-2xl bg-amber-500/15 border border-amber-500/30 flex items-center justify-center text-3xl shadow-inner flex-shrink-0">
                            {{ $murimStatus['current']['badge'] }}
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-black uppercase tracking-wider text-amber-400">Royaume Actuel</span>
                                <span class="text-[10px] text-mist font-bold">({{ $murimStatus['current']['hangeul'] }} / {{ $murimStatus['current']['pinyin'] }})</span>
                            </div>
                            <h2 class="text-2xl font-black text-white flex items-center gap-2.5 mt-0.5">
                                <span>{{ $murimStatus['current']['title'] }}</span>
                                <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-white/5 text-chalk border border-white/10">{{ $murimStatus['current']['aura'] }}</span>
                            </h2>
                            <p class="text-xs text-mist/80 italic mt-1">« {{ $murimStatus['current']['quote'] }} »</p>
                        </div>
                    </div>

                    <div class="text-left md:text-right">
                        <span class="text-xs text-mist font-semibold">Prochaine Percée :</span>
                        <div class="text-sm font-black text-amber-400">
                            @if($murimStatus['next'])
                                {{ $murimStatus['next']['badge'] }} {{ $murimStatus['next']['title'] }}
                            @else
                                👑 Sommet Absolu du Jianghu
                            @endif
                        </div>
                        <span class="text-[11px] text-mist font-medium">
                            @if($murimStatus['next'])
                                Encore <strong class="text-white">{{ $murimStatus['chapters_needed'] }}</strong> chapitres
                            @else
                                Aucun rival sous les cieux
                            @endif
                        </span>
                    </div>
                </div>

                {{-- Jauge de Qi --}}
                <div class="relative z-10 mb-4">
                    <div class="flex items-center justify-between text-xs font-bold mb-1.5">
                        <span class="text-chalk flex items-center gap-1.5">
                            <span>⚡ Concentration de Qi</span>
                            <span class="text-amber-400">({{ $murimStatus['percent'] }}%)</span>
                        </span>
                        <span class="text-mist">{{ number_format($readCount) }} / {{ $murimStatus['next'] ? $murimStatus['next']['min_chapters'] : number_format($readCount) }} chapitres</span>
                    </div>
                    <div class="w-full h-3 bg-black/60 rounded-full overflow-hidden border border-white/5 shadow-inner">
                        <div class="h-full bg-gradient-to-r from-amber-500 via-[#ff2a55] to-amber-300 rounded-full transition-all duration-1000 shadow-[0_0_15px_rgba(245,158,11,0.5)]" style="width: {{ $murimStatus['percent'] }}%"></div>
                    </div>
                </div>

                {{-- Raccourcis internes --}}
                <div class="pt-4 border-t border-white/10 flex flex-wrap items-center justify-between gap-3 relative z-10">
                    <div class="flex items-center gap-3">
                        <button type="button" @click="switchTab('murim'); murimSubtab = 'beast'" class="flex items-center gap-2 px-3 py-1.5 rounded-xl bg-white/5 hover:bg-white/10 border border-white/10 text-xs font-bold text-white transition">
                            <span x-text="userBeastStatus?.current_stage?.icon || '🦅'">🦅</span>
                            <span>Familier : <span class="text-amber-300" x-text="userBeastStatus?.beast?.name || 'Corbeau'">Corbeau</span></span>
                        </button>
                        <button type="button" @click="switchTab('murim'); murimSubtab = 'auras'" class="flex items-center gap-2 px-3 py-1.5 rounded-xl bg-white/5 hover:bg-white/10 border border-white/10 text-xs font-bold text-white transition">
                            <span>✨</span>
                            <span>Aura : <span class="text-indigo-300" x-text="avatarFrames[currentFrame]?.name || 'Disciple'"></span></span>
                        </button>
                    </div>

                    <button type="button" @click="openCardModal()" class="px-4 py-2 rounded-xl bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-400 hover:to-amber-500 text-black text-xs font-black transition flex items-center gap-2 shadow-lg shadow-amber-500/25">
                        <span>🎴</span>
                        <span>Ouvrir la Carte de Cultivation</span>
                    </button>
                </div>
            </div>

            {{-- 1.B. Calendrier & Heatmap d'Assiduité Mensuelle (Style Poseidon Scans) --}}
            <div class="p-6 md:p-7 rounded-3xl bg-[#11121d] border border-white/[0.08] shadow-xl">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
                    <div>
                        <h2 class="text-xl font-black text-white flex items-center gap-2.5">
                            <span>📅</span>
                            <span>Suivi d'Assiduité Martiale</span>
                            <span class="text-xs px-2.5 py-0.5 rounded-full bg-amber-500/10 text-amber-400 border border-amber-500/20 font-bold">
                                {{ $monthlyHeatmap['month_label'] }}
                            </span>
                        </h2>
                        <p class="text-xs text-mist mt-1">
                            Visualisez votre rythme quotidien de lecture. Chaque chapitre nourrit votre flamme intérieure !
                        </p>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl bg-[#161726] border border-white/10 text-xs font-bold text-chalk">
                            <span class="text-amber-400">🔥 Flamme active :</span>
                            <strong class="text-white">{{ $user->streak ?? 0 }} jours</strong>
                        </span>
                    </div>
                </div>

                {{-- 4 Métriques du Mois --}}
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3.5 mb-6">
                    <div class="p-4 rounded-2xl bg-[#161726] border border-white/5">
                        <span class="text-2xl font-black text-white block">{{ $monthlyHeatmap['total_chapters'] }}</span>
                        <span class="text-xs text-mist font-medium mt-0.5 block">Chapitres ce mois</span>
                    </div>
                    <div class="p-4 rounded-2xl bg-[#161726] border border-white/5">
                        <span class="text-2xl font-black text-amber-400 block">{{ $monthlyHeatmap['active_days'] }} j</span>
                        <span class="text-xs text-mist font-medium mt-0.5 block">Jours actifs</span>
                    </div>
                    <div class="p-4 rounded-2xl bg-[#161726] border border-white/5">
                        <span class="text-2xl font-black text-cyan-400 block">{{ $monthlyHeatmap['best_day_count'] }}</span>
                        <span class="text-xs text-mist font-medium mt-0.5 block">Record en 1 jour ({{ $monthlyHeatmap['best_day_label'] }})</span>
                    </div>
                    <div class="p-4 rounded-2xl bg-[#161726] border border-white/5">
                        <span class="text-2xl font-black text-emerald-400 block">{{ $monthlyHeatmap['assiduity_rate'] }}%</span>
                        <span class="text-xs text-mist font-medium mt-0.5 block">Taux d'assiduité</span>
                    </div>
                </div>

                {{-- Grille du Calendrier Mensuel --}}
                <div class="p-5 rounded-2xl bg-[#0d0e17] border border-white/10">
                    {{-- Jours de la semaine --}}
                    <div class="grid grid-cols-7 gap-2 text-center text-[11px] font-black tracking-wider text-mist mb-2">
                        <span>LUN</span>
                        <span>MAR</span>
                        <span>MER</span>
                        <span>JEU</span>
                        <span>VEN</span>
                        <span>SAM</span>
                        <span>DIM</span>
                    </div>

                    {{-- Cases des jours --}}
                    <div class="grid grid-cols-7 gap-2">
                        @for($i = 0; $i < $monthlyHeatmap['leading_empty_days']; $i++)
                            <div class="aspect-square rounded-xl bg-transparent border border-dashed border-white/[0.03]"></div>
                        @endfor

                        @foreach($monthlyHeatmap['days'] as $day)
                            @php
                                $bgClass = match($day['intensity']) {
                                    0 => 'bg-[#151624] border-white/5 text-mist/60 hover:border-white/20',
                                    1 => 'bg-indigo-950/70 border-indigo-700/50 text-indigo-200 hover:border-indigo-400',
                                    2 => 'bg-red-950/80 border-red-600/50 text-red-100 hover:border-red-400',
                                    3 => 'bg-red-800/90 border-red-500/80 text-white hover:border-red-300 shadow-[0_0_10px_rgba(239,68,68,0.3)]',
                                    4 => 'bg-gradient-to-tr from-amber-600 to-yellow-400 border-amber-300 text-black font-black shadow-[0_0_15px_rgba(245,158,11,0.6)] hover:scale-105',
                                    default => 'bg-[#151624] border-white/5 text-mist/60',
                                };
                            @endphp
                            <div 
                                class="aspect-square rounded-xl border p-1.5 flex flex-col justify-between transition relative group cursor-pointer {{ $bgClass }} {{ $day['is_today'] ? 'ring-2 ring-amber-400 ring-offset-2 ring-offset-black' : '' }}"
                                title="{{ $day['day_number'] }} {{ $monthlyHeatmap['month_label'] }} : {{ $day['chapters_read'] }} chapitre(s) lus"
                            >
                                <div class="flex items-center justify-between text-[11px] font-bold">
                                    <span>{{ $day['day_number'] }}</span>
                                    @if($day['is_today'])
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-400 animate-ping"></span>
                                    @endif
                                </div>

                                <div class="text-right">
                                    @if($day['chapters_read'] > 0)
                                        <span class="text-[10px] font-black {{ $day['intensity'] === 4 ? 'text-black' : 'text-amber-300' }}">
                                            ⚡{{ $day['chapters_read'] }}
                                        </span>
                                    @endif
                                </div>

                                {{-- Tooltip --}}
                                <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover:block z-30 px-2.5 py-1 rounded-lg bg-black text-white text-[10px] whitespace-nowrap shadow-xl border border-white/10 pointer-events-none">
                                    {{ $day['day_number'] }} {{ $monthlyHeatmap['month_label'] }} : <strong>{{ $day['chapters_read'] }}</strong> chap.
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- Légende --}}
                    <div class="flex items-center justify-end gap-2 mt-4 pt-4 border-t border-white/5 text-[11px] text-mist font-medium">
                        <span>Moins de Qi</span>
                        <span class="w-3.5 h-3.5 rounded bg-[#151624] border border-white/10" title="0 chap."></span>
                        <span class="w-3.5 h-3.5 rounded bg-indigo-950/70 border border-indigo-700/50" title="1-2 chap."></span>
                        <span class="w-3.5 h-3.5 rounded bg-red-950/80 border border-red-600/50" title="3-5 chap."></span>
                        <span class="w-3.5 h-3.5 rounded bg-red-800/90 border border-red-500/80" title="6-8 chap."></span>
                        <span class="w-3.5 h-3.5 rounded bg-gradient-to-tr from-amber-600 to-yellow-400 border border-amber-300" title="9+ chap."></span>
                        <span>Flamme Suprême</span>
                    </div>
                </div>
            </div>

            {{-- 1.C. Mes 5 Séries Incontournables (Top 5 Épinglé) --}}
            <div class="p-6 md:p-7 rounded-3xl bg-[#11121d] border border-white/[0.08] shadow-xl">
                <div class="flex items-center justify-between mb-5">
                    <div>
                        <h3 class="text-base font-black text-white flex items-center gap-2">
                            <span>⭐</span>
                            <span>Mes 5 Séries Incontournables</span>
                        </h3>
                        <p class="text-xs text-mist">Vos œuvres de référence affichées publiquement sur votre profil.</p>
                    </div>
                    <button type="button" @click="saveTopMangas()" class="px-3.5 py-1.5 rounded-xl bg-crimson hover:bg-crimson/80 text-white text-xs font-bold transition shadow-sm flex items-center gap-1.5">
                        <span>💾</span>
                        <span>Sauvegarder</span>
                    </button>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-3.5">
                    @for($slot = 0; $slot < 5; $slot++)
                        <div class="relative group rounded-2xl overflow-hidden border border-white/10 bg-[#0d0e17] aspect-[2/3] flex flex-col items-center justify-center text-center p-2 transition hover:border-amber-400/50">
                            <div class="absolute top-2 left-2 z-20 w-6 h-6 rounded-full bg-black/80 backdrop-blur-sm border border-white/20 flex items-center justify-center text-[10px] font-black text-amber-400 shadow">
                                #{{ $slot + 1 }}
                            </div>

                            <template x-if="topMangasIds[{{ $slot }}] && getManga(topMangasIds[{{ $slot }}])">
                                <div class="w-full h-full relative">
                                    <img :src="getManga(topMangasIds[{{ $slot }}]).cover || '/images/default-cover.jpg'" class="w-full h-full object-cover rounded-xl" alt="">
                                    <div class="absolute inset-0 bg-gradient-to-t from-black via-black/40 to-transparent flex flex-col justify-end p-2.5">
                                        <span class="text-xs font-bold text-white line-clamp-2 leading-tight" x-text="getManga(topMangasIds[{{ $slot }}]).title"></span>
                                    </div>
                                    <button type="button" @click.stop="removeTopSlot({{ $slot }})" class="absolute top-2 right-2 z-20 w-6 h-6 rounded-full bg-crimson/90 text-white flex items-center justify-center text-xs opacity-0 group-hover:opacity-100 transition shadow hover:scale-110" title="Retirer">
                                        ✕
                                    </button>
                                </div>
                            </template>

                            <template x-if="!topMangasIds[{{ $slot }}] || !getManga(topMangasIds[{{ $slot }}])">
                                <button type="button" @click="openSelector('top:{{ $slot }}')" class="w-full h-full flex flex-col items-center justify-center gap-2 border-2 border-dashed border-white/10 rounded-xl hover:border-crimson/60 hover:bg-white/[0.02] transition cursor-pointer p-4 group">
                                    <div class="w-10 h-10 rounded-full bg-white/5 flex items-center justify-center text-lg group-hover:scale-110 transition text-mist group-hover:text-crimson">
                                        +
                                    </div>
                                    <span class="text-[11px] font-bold text-mist group-hover:text-white transition">Choisir une série</span>
                                </button>
                            </template>
                        </div>
                    @endfor
                </div>
            </div>

            {{-- 1.D. Reprendre la lecture (Dernières lectures) --}}
            <div class="p-6 md:p-7 rounded-3xl bg-[#11121d] border border-white/[0.08] shadow-xl" x-show="history.length > 0">
                <div class="flex items-center justify-between mb-5">
                    <h3 class="text-base font-black text-white flex items-center gap-2">
                        <span>⚡</span>
                        <span>Reprendre la lecture</span>
                    </h3>
                    <button type="button" @click="switchTab('library'); libTab = 'history'" class="text-xs font-bold text-mist hover:text-white transition">
                        Voir tout l'historique →
                    </button>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                    <template x-for="item in history.slice(0, 3)" :key="item.mangaSlug + item.chapterSlug">
                        <a :href="'/lecture/' + item.mangaSlug + '/' + item.chapterSlug" class="flex items-center gap-3.5 p-3 rounded-2xl bg-[#161726] border border-white/5 hover:border-crimson/50 hover:bg-[#1d1f33] transition group">
                            <div class="w-14 h-20 rounded-xl overflow-hidden bg-black/50 flex-shrink-0">
                                <template x-if="item.cover">
                                    <img :src="item.cover" class="w-full h-full object-cover group-hover:scale-105 transition-transform" alt="">
                                </template>
                            </div>
                            <div class="min-w-0 flex-1">
                                <h4 class="text-sm font-bold text-white truncate group-hover:text-amber-400 transition" x-text="item.mangaTitle"></h4>
                                <span class="text-xs font-semibold text-crimson block mt-0.5 truncate" x-text="item.chapterTitle"></span>
                                <span class="text-[11px] text-mist mt-1 block">Reprendre la lecture →</span>
                            </div>
                        </a>
                    </template>
                </div>
            </div>

            {{-- 1.E. Mes Derniers Commentaires --}}
            <div class="p-6 md:p-7 rounded-3xl bg-[#11121d] border border-white/[0.08] shadow-xl">
                <h3 class="text-base font-black text-white flex items-center gap-2 mb-4">
                    <span>💬</span>
                    <span>Mes derniers commentaires</span>
                </h3>

                @if($recentComments->isNotEmpty())
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach($recentComments as $comment)
                            <div class="p-4 rounded-2xl bg-[#161726] border border-white/5 hover:border-white/15 transition">
                                <div class="flex items-center justify-between text-xs mb-2">
                                    <div class="font-bold text-white truncate">
                                        @if($comment->commentable instanceof \App\Models\Manga)
                                            Sur <a href="{{ route('manga.show', $comment->commentable->slug) }}" class="text-crimson hover:underline">{{ $comment->commentable->title }}</a>
                                        @elseif($comment->commentable instanceof \App\Models\Chapter)
                                            Sur <a href="{{ route('reader.show', [$comment->commentable->manga->slug, $comment->commentable->slug]) }}" class="text-crimson hover:underline">{{ $comment->commentable->manga->title }} — {{ $comment->commentable->title }}</a>
                                        @else
                                            Commentaire
                                        @endif
                                    </div>
                                    <span class="text-mist flex-shrink-0">{{ $comment->created_at->diffForHumans() }}</span>
                                </div>
                                <p class="text-xs text-mist/90 line-clamp-3 leading-relaxed">
                                    {{ \Illuminate\Support\Str::limit(strip_tags($comment->content), 180) }}
                                </p>
                                <div class="mt-2.5 text-[11px] text-mist flex items-center gap-2">
                                    <span>👍 {{ $comment->likes_count ?? 0 }} likes</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="py-8 text-center text-mist text-xs">
                        Vous n'avez pas encore posté de commentaire.
                    </div>
                @endif
            </div>

        </div>

        {{-- ──────────────────────────────────────────────────────────
             ONGLET 2 : FAVORIS & BIBLIOTHÈQUE (LIBRARY)
             ────────────────────────────────────────────────────────── --}}
        <div x-show="currentTab === 'library'" x-cloak class="space-y-6">

            {{-- Bannière Mode Rattrapage --}}
            <div class="p-5 rounded-3xl bg-gradient-to-r from-red-950/40 via-[#181520] to-[#12131f] border border-crimson/30 shadow-xl flex flex-col sm:flex-row items-center justify-between gap-4">
                <div class="flex items-center gap-3.5">
                    <div class="w-12 h-12 rounded-2xl bg-crimson/20 border border-crimson/40 flex items-center justify-center text-2xl text-crimson flex-shrink-0 shadow-lg">
                        ⚡
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-black text-white">Mode Rattrapage</span>
                            <span class="text-[9px] font-black uppercase px-2 py-0.5 rounded-full bg-crimson text-white">Nouveau</span>
                        </div>
                        <p class="text-xs text-mist mt-0.5">
                            Retrouvez tous les chapitres non lus de vos séries favorites classés par date !
                        </p>
                    </div>
                </div>

                <a href="{{ route('library', ['tab' => 'rattrapage']) }}" class="px-4 py-2.5 rounded-xl bg-crimson hover:bg-crimson/90 text-white text-xs font-bold transition flex items-center gap-1.5 shadow-lg shadow-crimson/20 flex-shrink-0">
                    <span>Accéder au Rattrapage</span>
                    <span>→</span>
                </a>
            </div>

            {{-- Sous-onglets : Favoris vs Historique --}}
            <div class="flex items-center gap-2">
                <button 
                    type="button" 
                    @click="libTab = 'favorites'"
                    class="px-4 py-2 rounded-xl text-xs font-bold transition"
                    :class="libTab === 'favorites' ? 'bg-white text-black' : 'bg-[#161726] text-mist hover:text-white'"
                >
                    Mes Favoris (<span x-text="favorites.length">0</span>)
                </button>
                <button 
                    type="button" 
                    @click="libTab = 'history'"
                    class="px-4 py-2 rounded-xl text-xs font-bold transition"
                    :class="libTab === 'history' ? 'bg-white text-black' : 'bg-[#161726] text-mist hover:text-white'"
                >
                    Historique de lecture (<span x-text="history.length">0</span>)
                </button>
            </div>

            {{-- Grille des Favoris --}}
            <div x-show="libTab === 'favorites'">
                <template x-if="favorites.length === 0">
                    <div class="p-12 text-center rounded-3xl bg-[#11121d] border border-white/[0.08]">
                        <span class="text-4xl block mb-2">⭐</span>
                        <h4 class="text-base font-bold text-white mb-1">Aucun favori pour le moment</h4>
                        <p class="text-xs text-mist max-w-sm mx-auto mb-4">Ajoutez des mangas ou webtoons à vos favoris en cliquant sur le cœur sur leur fiche !</p>
                        <a href="{{ route('manga.index') }}" class="px-4 py-2 rounded-xl bg-crimson text-white text-xs font-bold inline-block">Explorer le Catalogue</a>
                    </div>
                </template>

                <div x-show="favorites.length > 0" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3.5">
                    <template x-for="fav in favorites" :key="fav.slug">
                        <div class="group relative rounded-2xl overflow-hidden bg-[#161726] border border-white/5 hover:border-amber-400/50 transition-all flex flex-col shadow-lg">
                            <a :href="'/manga/' + fav.slug" class="aspect-[2/3] w-full overflow-hidden relative block bg-black/40">
                                <img :src="fav.cover || '/images/default-cover.jpg'" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" alt="">
                                <div class="absolute inset-0 bg-gradient-to-t from-black via-black/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity flex flex-col justify-end p-2.5">
                                    <span class="text-xs font-bold text-white leading-tight" x-text="fav.title"></span>
                                </div>
                            </a>

                            <div class="p-2.5 flex items-center justify-between gap-1">
                                <a :href="'/manga/' + fav.slug" class="text-xs font-bold text-white truncate hover:text-amber-400 transition" x-text="fav.title"></a>
                                <button type="button" @click="removeFavorite(fav.slug)" class="w-6 h-6 rounded-lg text-mist hover:text-crimson hover:bg-white/5 flex items-center justify-center text-xs transition" title="Retirer des favoris">
                                    ✕
                                </button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            {{-- Liste de l'Historique de Lecture --}}
            <div x-show="libTab === 'history'">
                <template x-if="history.length === 0">
                    <div class="p-12 text-center rounded-3xl bg-[#11121d] border border-white/[0.08]">
                        <span class="text-4xl block mb-2">📜</span>
                        <h4 class="text-base font-bold text-white mb-1">Votre historique est vide</h4>
                        <p class="text-xs text-mist max-w-sm mx-auto mb-4">Commencez la lecture d'un chapitre pour le voir apparaître ici automatiquement !</p>
                        <a href="{{ route('manga.index') }}" class="px-4 py-2 rounded-xl bg-crimson text-white text-xs font-bold inline-block">Découvrir les Séries</a>
                    </div>
                </template>

                <div x-show="history.length > 0" class="divide-y divide-white/5 rounded-3xl bg-[#11121d] border border-white/[0.08] overflow-hidden">
                    <template x-for="item in history" :key="item.mangaSlug + item.chapterSlug">
                        <div class="p-4 flex items-center justify-between gap-4 hover:bg-white/[0.02] transition">
                            <div class="flex items-center gap-3.5 min-w-0">
                                <div class="w-12 h-16 rounded-xl overflow-hidden bg-black/40 flex-shrink-0">
                                    <img :src="item.cover || '/images/default-cover.jpg'" class="w-full h-full object-cover" alt="">
                                </div>
                                <div class="min-w-0">
                                    <h4 class="text-sm font-bold text-white truncate" x-text="item.mangaTitle"></h4>
                                    <span class="text-xs font-semibold text-crimson block mt-0.5 truncate" x-text="item.chapterTitle"></span>
                                    <span class="text-[10px] text-mist mt-0.5 block" x-text="new Date(item.readAt).toLocaleDateString('fr-FR', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' })"></span>
                                </div>
                            </div>

                            <a :href="'/lecture/' + item.mangaSlug + '/' + item.chapterSlug" class="px-3.5 py-1.5 rounded-xl bg-[#1d1f33] hover:bg-crimson text-white text-xs font-bold transition flex-shrink-0">
                                Reprendre →
                            </a>
                        </div>
                    </template>
                </div>
            </div>

        </div>

        {{-- ──────────────────────────────────────────────────────────
             ONGLET 3 : VOIE MARTIALE & MURIM
             ────────────────────────────────────────────────────────── --}}
        <div x-show="currentTab === 'murim'" x-cloak class="space-y-6">

            {{-- Sous-onglets de la Voie Martiale --}}
            <div class="flex items-center gap-2 p-1.5 rounded-2xl bg-[#11121d] border border-white/[0.08] overflow-x-auto custom-scrollbar">
                <button 
                    type="button" 
                    @click="murimSubtab = 'guide'"
                    class="px-4 py-2 rounded-xl text-xs font-bold transition whitespace-nowrap"
                    :class="murimSubtab === 'guide' ? 'bg-amber-500 text-black shadow-md' : 'text-mist hover:text-white'"
                >
                    ⛩️ Guide des 10 Royaumes
                </button>
                <button 
                    type="button" 
                    @click="murimSubtab = 'beast'"
                    class="px-4 py-2 rounded-xl text-xs font-bold transition whitespace-nowrap"
                    :class="murimSubtab === 'beast' ? 'bg-amber-500 text-black shadow-md' : 'text-mist hover:text-white'"
                >
                    🐾 Bête Spirituelle de l'Ombre
                </button>
                <button 
                    type="button" 
                    @click="murimSubtab = 'auras'"
                    class="px-4 py-2 rounded-xl text-xs font-bold transition whitespace-nowrap"
                    :class="murimSubtab === 'auras' ? 'bg-amber-500 text-black shadow-md' : 'text-mist hover:text-white'"
                >
                    ✨ Auras & Cadres d'Avatar
                </button>
                <button 
                    type="button" 
                    @click="murimSubtab = 'tierlist'"
                    class="px-4 py-2 rounded-xl text-xs font-bold transition whitespace-nowrap"
                    :class="murimSubtab === 'tierlist' ? 'bg-amber-500 text-black shadow-md' : 'text-mist hover:text-white'"
                >
                    👑 Tier List S à F
                </button>
            </div>

            {{-- 3.A. Guide des 10 Royaumes Martiaux --}}
            <div x-show="murimSubtab === 'guide'" class="p-6 md:p-7 rounded-3xl bg-[#11121d] border border-white/[0.08] shadow-xl space-y-6">
                <div>
                    <h3 class="text-xl font-black text-white flex items-center gap-2">
                        <span>⛩️</span>
                        <span>Guide des 10 Royaumes de Cultivation du Jianghu</span>
                    </h3>
                    <p class="text-xs text-mist mt-1">
                        Chaque chapitre maîtrisé sur Hidden Scan affine votre Qi. Gravissez les 10 échelons légendaires jusqu'au rang ultime !
                    </p>
                </div>

                @php
                    $allRanks = \App\Services\MurimRankService::getRanks();
                @endphp

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach($allRanks as $rId => $r)
                        @php
                            $isCurrentRank = ($murimStatus['current']['id'] === $r['id']);
                            $isPassed = ($readCount >= $r['min_chapters']);
                        @endphp
                        <div class="p-5 rounded-2xl border transition-all relative flex flex-col justify-between {{ $isCurrentRank ? 'bg-amber-950/30 border-amber-400 shadow-[0_0_20px_rgba(245,158,11,0.25)]' : ($isPassed ? 'bg-[#141524] border-white/15' : 'bg-[#0e0f17] border-white/5 opacity-60') }}">
                            <div>
                                <div class="flex items-center justify-between gap-3 mb-2">
                                    <div class="flex items-center gap-2.5">
                                        <span class="text-2xl">{{ $r['badge'] }}</span>
                                        <div>
                                            <h4 class="text-base font-black text-white flex items-center gap-2">
                                                <span>{{ $r['title'] }}</span>
                                                @if($isCurrentRank)
                                                    <span class="text-[9px] font-black uppercase px-2 py-0.5 rounded-full bg-amber-400 text-black">Votre Rang</span>
                                                @endif
                                            </h4>
                                            <span class="text-[11px] text-mist">{{ $r['hangeul'] }} • {{ $r['pinyin'] }}</span>
                                        </div>
                                    </div>
                                    <span class="text-xs font-bold px-2.5 py-1 rounded-xl bg-white/5 border border-white/10" style="color: {{ $r['color'] }}">
                                        {{ $r['min_chapters'] }}+ chapitres
                                    </span>
                                </div>
                                <p class="text-xs text-mist leading-relaxed mt-2">{{ $r['description'] }}</p>
                                <p class="text-[11px] italic text-amber-300/80 mt-1.5">« {{ $r['quote'] }} »</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- 3.B. Bête Spirituelle de l'Ombre --}}
            <div x-show="murimSubtab === 'beast'" class="space-y-6">
                {{-- Bête Active --}}
                <div class="p-6 md:p-8 rounded-3xl bg-gradient-to-br from-[#121124] via-[#0d0c18] to-[#161228] border border-indigo-500/30 shadow-2xl relative overflow-hidden">
                    <div class="flex flex-col md:flex-row items-center gap-8 relative z-10">
                        <div class="w-28 h-28 md:w-32 md:h-32 rounded-3xl bg-gradient-to-tr from-indigo-950 to-purple-900/60 border-2 border-indigo-400/50 shadow-[0_0_35px_rgba(99,102,241,0.35)] flex items-center justify-center text-6xl md:text-7xl select-none flex-shrink-0">
                            <span x-text="userBeastStatus?.current_stage?.icon || '🦅'">🦅</span>
                        </div>

                        <div class="flex-1 text-center md:text-left min-w-0">
                            <div class="flex flex-wrap items-center justify-center md:justify-start gap-2.5 mb-1.5">
                                <h3 class="text-2xl font-black text-white" x-text="userBeastStatus?.beast?.name || 'Corbeau des Ombres'"></h3>
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-white/5 border border-white/10 text-mist" x-text="userBeastStatus?.beast?.element || 'Ténèbres'"></span>
                            </div>

                            <div class="text-sm font-bold text-amber-300 mb-2">
                                <span class="text-mist font-normal">Stade Actuel :</span> 
                                <span x-text="userBeastStatus?.current_stage?.name || 'Éclaireur'"></span>
                            </div>

                            <p class="text-xs text-mist leading-relaxed mb-4 max-w-xl" x-text="userBeastStatus?.current_stage?.description || userBeastStatus?.beast?.desc"></p>

                            {{-- Progression --}}
                            <div class="max-w-xl bg-black/40 p-4 rounded-2xl border border-white/10">
                                <div class="flex items-center justify-between text-xs mb-2">
                                    <span class="font-bold text-white">⚡ Éveil Spirituel</span>
                                    <span class="text-indigo-400 font-extrabold" x-text="(userBeastStatus?.read_count || 0) + ' / ' + (userBeastStatus?.next_stage ? userBeastStatus?.next_stage?.min_chapters + ' ch.' : 'MAX')"></span>
                                </div>
                                <div class="w-full h-3 rounded-full bg-white/5 border border-white/10 overflow-hidden">
                                    <div class="h-full rounded-full bg-gradient-to-r from-indigo-500 via-purple-500 to-amber-400 transition-all duration-700" :style="'width: ' + (userBeastStatus?.progress_percent || 0) + '%'"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Les 4 Totems Spirituels --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <template x-for="(beast, bKey) in spiritualBeasts" :key="bKey">
                        <div 
                            class="p-5 rounded-2xl border transition-all duration-300 relative flex flex-col justify-between"
                            :class="spiritualBeast === bKey ? 'bg-indigo-950/40 border-indigo-500 shadow-[0_0_20px_rgba(99,102,241,0.2)]' : 'bg-[#111118] border-white/10 hover:border-white/20'"
                        >
                            <div>
                                <div class="flex items-start justify-between gap-3 mb-3">
                                    <div class="flex items-center gap-3">
                                        <div class="w-12 h-12 rounded-xl bg-white/5 border border-white/10 flex items-center justify-center text-3xl">
                                            <span x-text="beast.stages[0]?.icon || '🐾'"></span>
                                        </div>
                                        <div>
                                            <h4 class="text-base font-black text-white" x-text="beast.name"></h4>
                                            <span class="text-[11px] text-indigo-400 font-bold" x-text="beast.element"></span>
                                        </div>
                                    </div>
                                    <template x-if="spiritualBeast === bKey">
                                        <span class="px-2.5 py-1 rounded-full bg-emerald-500/20 text-emerald-400 border border-emerald-500/40 text-[10px] font-black uppercase tracking-wider">
                                            ✓ Scellé
                                        </span>
                                    </template>
                                </div>
                                <p class="text-xs text-mist leading-relaxed mb-4" x-text="beast.desc"></p>
                            </div>

                            <button 
                                type="button" 
                                @click="chooseBeast(bKey)"
                                class="w-full py-2.5 rounded-xl text-xs font-black transition-all flex items-center justify-center gap-2"
                                :class="spiritualBeast === bKey ? 'bg-indigo-600/30 text-indigo-300 border border-indigo-500/40 cursor-default' : 'bg-[#161726] border border-white/10 hover:border-indigo-400 text-white'"
                                :disabled="spiritualBeast === bKey"
                            >
                                <span x-text="spiritualBeast === bKey ? 'Pacte Actif' : 'Sceller le Pacte'"></span>
                            </button>
                        </div>
                    </template>
                </div>
            </div>

            {{-- 3.C. Auras & Cadres d'Avatar --}}
            <div x-show="murimSubtab === 'auras'" class="p-6 md:p-7 rounded-3xl bg-[#11121d] border border-white/[0.08] shadow-xl space-y-6">
                <div>
                    <h3 class="text-xl font-black text-white flex items-center gap-2">
                        <span>✨</span>
                        <span>Auras de Qi & Cadres d'Avatar</span>
                    </h3>
                    <p class="text-xs text-mist mt-1">
                        Équipez l'aura correspondant à vos prouesses martiales pour briller sur tout le site !
                    </p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                    <template x-for="(frame, fKey) in avatarFrames" :key="fKey">
                        <div 
                            class="p-5 rounded-2xl border transition-all duration-300 relative flex flex-col items-center text-center justify-between"
                            :class="currentFrame === fKey ? 'bg-amber-950/20 border-amber-400 shadow-[0_0_20px_rgba(251,191,36,0.25)]' : (frame.unlocked ? 'bg-[#141524] border-white/10 hover:border-white/20' : 'bg-[#0d0d14]/70 border-white/5 opacity-60')"
                        >
                            <div class="w-full flex flex-col items-center">
                                <div class="my-3">
                                    <div class="w-20 h-20 rounded-full flex items-center justify-center relative transition-all duration-300" :class="getFrameClass(fKey)">
                                        <div class="w-full h-full rounded-full overflow-hidden bg-[#181926] border-2 border-white/10 flex items-center justify-center">
                                            <template x-if="!avatarPreview">
                                                <span class="font-black text-xl text-amber-300">{{ $initials }}</span>
                                            </template>
                                            <template x-if="avatarPreview">
                                                <img :src="avatarPreview" class="w-full h-full object-cover" alt="">
                                            </template>
                                        </div>
                                    </div>
                                </div>

                                <h4 class="text-base font-black text-white mt-2" x-text="frame.name"></h4>
                                <p class="text-[11px] text-mist mt-1 mb-3 line-clamp-2" x-text="frame.description"></p>

                                <div class="mb-4">
                                    <template x-if="frame.unlocked">
                                        <span class="px-2.5 py-0.5 rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 text-[10px] font-extrabold uppercase tracking-wider">
                                            ✓ Débloqué
                                        </span>
                                    </template>
                                    <template x-if="!frame.unlocked">
                                        <span class="px-2.5 py-0.5 rounded-full bg-white/5 text-mist border border-white/10 text-[10px] font-bold">
                                            🔒 Requiert <strong class="text-amber-400" x-text="frame.min_chapters"></strong> chapitres
                                        </span>
                                    </template>
                                </div>
                            </div>

                            <button 
                                type="button" 
                                @click="frame.unlocked ? equipFrame(fKey) : null"
                                class="w-full py-2.5 rounded-xl text-xs font-black transition-all flex items-center justify-center gap-2"
                                :class="currentFrame === fKey ? 'bg-amber-400/20 text-amber-300 border border-amber-400/40 cursor-default' : (frame.unlocked ? 'bg-[#181926] border border-white/10 hover:border-amber-400 text-white' : 'bg-white/[0.02] border border-white/5 text-mist/40 cursor-not-allowed')"
                                :disabled="!frame.unlocked || currentFrame === fKey"
                            >
                                <template x-if="currentFrame === fKey">
                                    <span>✨ Aura Équipée</span>
                                </template>
                                <template x-if="currentFrame !== fKey && frame.unlocked">
                                    <span>Équiper cette Aura</span>
                                </template>
                                <template x-if="!frame.unlocked">
                                    <span>Aura Scellée</span>
                                </template>
                            </button>
                        </div>
                    </template>
                </div>
            </div>

            {{-- 3.D. Tier List S/A/B/C/D/F --}}
            <div x-show="murimSubtab === 'tierlist'" class="p-6 md:p-7 rounded-3xl bg-[#11121d] border border-white/[0.08] shadow-xl space-y-6">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <h3 class="text-xl font-black text-white flex items-center gap-2">
                            <span>👑</span>
                            <span>Ma Tier List Webtoons (S à F)</span>
                        </h3>
                        <p class="text-xs text-mist mt-1">
                            Classez vos séries fétiches selon leur prestige martial.
                        </p>
                    </div>
                    <button type="button" @click="saveTierList()" class="px-4 py-2.5 rounded-xl bg-crimson hover:bg-crimson/90 text-white text-xs font-bold transition flex items-center gap-2 shadow-lg shadow-crimson/25 flex-shrink-0">
                        <span>💾</span>
                        <span>Enregistrer ma Tier List</span>
                    </button>
                </div>

                @php
                    $tiersMeta = [
                        'S' => ['label' => 'Divin & Suprême', 'bg' => 'bg-red-600 text-white'],
                        'A' => ['label' => 'Grand Maître', 'bg' => 'bg-purple-600 text-white'],
                        'B' => ['label' => 'Élite du Qi', 'bg' => 'bg-blue-600 text-white'],
                        'C' => ['label' => 'Disciple Prometteur', 'bg' => 'bg-emerald-600 text-white'],
                        'D' => ['label' => 'Guerrier Moyen', 'bg' => 'bg-amber-600 text-white'],
                        'F' => ['label' => 'Abysses Martiales', 'bg' => 'bg-zinc-700 text-white'],
                    ];
                @endphp

                <div class="space-y-3">
                    @foreach(['S', 'A', 'B', 'C', 'D', 'F'] as $tierKey)
                        @php $meta = $tiersMeta[$tierKey]; @endphp
                        <div class="flex flex-col sm:flex-row items-stretch rounded-2xl bg-[#0d0e17] border border-white/10 overflow-hidden shadow-md">
                            <div class="w-full sm:w-28 sm:min-w-[7rem] flex sm:flex-col items-center justify-between sm:justify-center p-3 text-center {{ $meta['bg'] }} font-black">
                                <span class="text-2xl">{{ $tierKey }}</span>
                                <span class="text-[9px] uppercase tracking-wider opacity-90 leading-tight">{{ $meta['label'] }}</span>
                                <button type="button" @click="openSelector('tier:{{ $tierKey }}')" class="sm:hidden px-2 py-1 rounded bg-black/30 text-white text-[10px] font-bold">
                                    + Ajouter
                                </button>
                            </div>

                            <div class="flex-1 p-3 flex items-center gap-3 overflow-x-auto min-h-[110px] bg-[#12131f] custom-scrollbar">
                                <template x-for="mId in (tierList['{{ $tierKey }}'] || [])" :key="mId">
                                    <div class="relative group/manga flex-shrink-0 w-20 aspect-[2/3] rounded-xl overflow-hidden border border-white/10 bg-black/60 shadow-md">
                                        <template x-if="getManga(mId)">
                                            <div class="w-full h-full relative">
                                                <img :src="getManga(mId).cover || '/images/default-cover.jpg'" class="w-full h-full object-cover" alt="">
                                                <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/30 to-transparent p-1 flex flex-col justify-end">
                                                    <span class="text-[10px] font-bold text-white line-clamp-2 leading-tight" x-text="getManga(mId).title"></span>
                                                </div>
                                                <button type="button" @click.stop="removeFromTier('{{ $tierKey }}', mId)" class="absolute top-1 right-1 w-5 h-5 rounded-full bg-crimson text-white flex items-center justify-center text-[10px] opacity-0 group-hover/manga:opacity-100 transition shadow hover:scale-110" title="Retirer">
                                                    ✕
                                                </button>
                                            </div>
                                        </template>
                                    </div>
                                </template>

                                <button type="button" @click="openSelector('tier:{{ $tierKey }}')" class="flex-shrink-0 w-20 aspect-[2/3] rounded-xl border-2 border-dashed border-white/10 hover:border-amber-400/60 hover:bg-white/[0.02] transition flex flex-col items-center justify-center gap-1 text-mist hover:text-white cursor-pointer group">
                                    <span class="text-xl group-hover:scale-110 transition">+</span>
                                    <span class="text-[9px] font-bold uppercase tracking-wider">Ajouter</span>
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

        </div>

        {{-- ──────────────────────────────────────────────────────────
             ONGLET 4 : CLASSEMENT DES LECTEURS (LEADERBOARD)
             ────────────────────────────────────────────────────────── --}}
        <div x-show="currentTab === 'leaderboard'" x-cloak class="p-6 md:p-7 rounded-3xl bg-[#11121d] border border-white/[0.08] shadow-xl space-y-6">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h2 class="text-xl font-black text-white flex items-center gap-2.5">
                        <span>🏆</span>
                        <span>Panthéon des Cultivateurs</span>
                        <span class="text-xs px-2.5 py-0.5 rounded-full bg-amber-500/10 text-amber-400 border border-amber-500/20 font-bold">
                            Top Jianghu
                        </span>
                    </h2>
                    <p class="text-xs text-mist mt-1">
                        Le classement officiel des lecteurs selon leur accumulation de Qi et chapitres conquis.
                    </p>
                </div>
                <a href="{{ route('leaderboard') }}" class="px-4 py-2 rounded-xl bg-white/5 hover:bg-white/10 border border-white/10 text-xs font-bold text-white transition flex items-center gap-2 flex-shrink-0">
                    <span>Voir le Panthéon Public</span>
                    <span>→</span>
                </a>
            </div>

            {{-- Statut personnel dans le classement --}}
            <div class="p-4 rounded-2xl bg-gradient-to-r from-amber-500/10 via-[#161726] to-[#161726] border border-amber-500/30 flex items-center justify-between gap-4 flex-wrap">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-amber-500/20 border border-amber-500/40 flex items-center justify-center text-lg font-black text-amber-400">
                        #{{ $leaderboard['current_user_rank'] }}
                    </div>
                    <div>
                        <span class="text-xs text-mist font-semibold">Votre rang actuel</span>
                        <div class="text-sm font-bold text-white flex items-center gap-2">
                            <span>{{ $user->name }}</span>
                            <span class="text-xs text-amber-400">{{ $murimStatus['current']['badge'] }} {{ $murimStatus['current']['title'] }}</span>
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-4 text-xs font-bold text-mist">
                    <span>📖 <strong>{{ number_format($readCount) }}</strong> chap.</span>
                    <span>⚡ <strong>{{ number_format($user->xp ?? ($readCount * 15)) }}</strong> Qi</span>
                    <span>🔥 <strong>{{ $user->streak ?? 0 }}j</strong></span>
                </div>
            </div>

            {{-- Tableau Top 15 --}}
            <div class="rounded-2xl bg-[#0d0e17] border border-white/10 overflow-hidden">
                <div class="grid grid-cols-12 gap-3 p-3.5 bg-black/40 text-[11px] font-black uppercase tracking-wider text-mist border-b border-white/5">
                    <span class="col-span-2 sm:col-span-1 text-center">Rang</span>
                    <span class="col-span-6 sm:col-span-5">Cultivateur</span>
                    <span class="hidden sm:block sm:col-span-3">Royaume Martial</span>
                    <span class="col-span-4 sm:col-span-3 text-right">Chapitres / Qi</span>
                </div>

                <div class="divide-y divide-white/5">
                    @foreach($leaderboard['all'] as $rankNum => $cultivator)
                        @php $isMe = ($cultivator['id'] === $user->id); @endphp
                        <div class="grid grid-cols-12 gap-3 p-3.5 items-center transition {{ $isMe ? 'bg-amber-500/10 border-l-4 border-amber-400' : 'hover:bg-white/[0.02]' }}">
                            <div class="col-span-2 sm:col-span-1 text-center font-black">
                                @if($rankNum === 0)
                                    <span class="text-lg">🥇</span>
                                @elseif($rankNum === 1)
                                    <span class="text-lg">🥈</span>
                                @elseif($rankNum === 2)
                                    <span class="text-lg">🥉</span>
                                @else
                                    <span class="text-xs text-mist">#{{ $rankNum + 1 }}</span>
                                @endif
                            </div>

                            <div class="col-span-6 sm:col-span-5 flex items-center gap-3 min-w-0">
                                <div class="w-8 h-8 rounded-full bg-[#181926] border border-white/10 overflow-hidden flex-shrink-0 flex items-center justify-center text-xs font-bold text-white">
                                    @if(!empty($cultivator['avatar_url']))
                                        <img src="{{ $cultivator['avatar_url'] }}" class="w-full h-full object-cover" alt="">
                                    @else
                                        {{ strtoupper(substr($cultivator['name'], 0, 2)) }}
                                    @endif
                                </div>
                                <div class="min-w-0">
                                    <span class="text-xs font-bold text-white truncate block {{ $isMe ? 'text-amber-400' : '' }}">
                                        {{ $cultivator['name'] }}
                                        @if($isMe) <span class="text-[10px] text-amber-300 font-normal">(Vous)</span> @endif
                                    </span>
                                </div>
                            </div>

                            <div class="hidden sm:block sm:col-span-3">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold" style="background-color: {{ $cultivator['murim_color'] }}15; color: {{ $cultivator['murim_color'] }}; border: 1px solid {{ $cultivator['murim_color'] }}40;">
                                    <span>{{ $cultivator['murim_badge'] }}</span>
                                    <span class="truncate">{{ $cultivator['murim_title'] }}</span>
                                </span>
                            </div>

                            <div class="col-span-4 sm:col-span-3 text-right">
                                <span class="text-xs font-black text-white block">{{ number_format($cultivator['read_count']) }} chap.</span>
                                <span class="text-[10px] text-amber-400 font-bold block">⚡ {{ number_format($cultivator['xp'] ?? ($cultivator['read_count'] * 15)) }} XP</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- ──────────────────────────────────────────────────────────
             ONGLET 5 : PARAMÈTRES & COMPTE (SETTINGS)
             ────────────────────────────────────────────────────────── --}}
        <div x-show="currentTab === 'settings'" x-cloak class="space-y-6">

            {{-- Sous-onglets Paramètres --}}
            <div class="flex items-center gap-2 p-1.5 rounded-2xl bg-[#11121d] border border-white/[0.08] overflow-x-auto custom-scrollbar">
                <button 
                    type="button" 
                    @click="settingsSubtab = 'profile'"
                    class="px-4 py-2 rounded-xl text-xs font-bold transition whitespace-nowrap"
                    :class="settingsSubtab === 'profile' ? 'bg-white text-black' : 'text-mist hover:text-white'"
                >
                    ✏️ Mon Profil
                </button>
                <button 
                    type="button" 
                    @click="settingsSubtab = 'security'"
                    class="px-4 py-2 rounded-xl text-xs font-bold transition whitespace-nowrap"
                    :class="settingsSubtab === 'security' ? 'bg-white text-black' : 'text-mist hover:text-white'"
                >
                    🔐 Sécurité & Pass
                </button>
                <button 
                    type="button" 
                    @click="settingsSubtab = 'danger'"
                    class="px-4 py-2 rounded-xl text-xs font-bold transition whitespace-nowrap text-red-400"
                    :class="settingsSubtab === 'danger' ? 'bg-red-500 text-white' : 'hover:bg-red-500/10'"
                >
                    ⚠️ Zone de danger
                </button>
            </div>

            {{-- 5.A. Édition du profil --}}
            <div x-show="settingsSubtab === 'profile'" class="p-6 md:p-8 rounded-3xl bg-[#11121d] border border-white/[0.08] shadow-xl">
                <div class="mb-6">
                    <h2 class="text-xl font-black text-white flex items-center gap-2">
                        <svg class="w-5 h-5 text-crimson" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        <span>Personnaliser mon profil public</span>
                    </h2>
                    <p class="text-xs text-mist mt-1">Modifiez votre avatar, votre bannière et vos préférences de lecture.</p>
                </div>

                <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="space-y-6">
                    @csrf
                    @method('patch')
                    <input type="hidden" name="avatar_data" :value="avatarData">
                    <input type="hidden" name="banner_data" :value="bannerData">

                    {{-- Photo de profil --}}
                    <div class="p-5 rounded-2xl bg-[#161726] border border-white/5 space-y-4">
                        <label class="text-xs font-black uppercase tracking-wider text-mist block">Photo de Profil</label>
                        <div class="flex flex-col sm:flex-row items-center gap-6">
                            <div class="w-24 h-24 rounded-full overflow-hidden bg-[#0d0e17] border-2 border-crimson flex items-center justify-center flex-shrink-0 shadow-lg">
                                <template x-if="!avatarPreview">
                                    <span class="text-2xl font-black text-amber-300">{{ $initials }}</span>
                                </template>
                                <template x-if="avatarPreview">
                                    <img :src="avatarPreview" class="w-full h-full object-cover" alt="">
                                </template>
                            </div>
                            <div class="flex-1 space-y-2 w-full">
                                <label class="px-4 py-2.5 rounded-xl bg-[#202236] hover:bg-[#282a44] text-white text-xs font-bold transition flex items-center justify-center sm:justify-start gap-2 cursor-pointer w-fit">
                                    <svg class="w-4 h-4 text-crimson" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                                    <span>Téléverser une image (PNG, JPG, WEBP)</span>
                                    <input type="file" name="avatar_file" accept="image/*" class="hidden" @change="handleAvatar($event)">
                                </label>
                                <input type="text" name="avatar_url" class="w-full px-4 py-2.5 rounded-xl bg-[#0d0e17] border border-white/10 text-white placeholder-mist/50 text-xs focus:outline-none focus:border-crimson" placeholder="Ou collez un lien direct d'image (https://...)" @input="if ($el.value.startsWith('http')) { avatarPreview = $el.value; }">
                            </div>
                        </div>
                    </div>

                    {{-- Bannière de profil --}}
                    <div class="p-5 rounded-2xl bg-[#161726] border border-white/5 space-y-4">
                        <label class="text-xs font-black uppercase tracking-wider text-mist block">Bannière de Profil</label>
                        <div class="h-32 rounded-2xl overflow-hidden bg-[#0d0e17] border border-white/10 relative">
                            <template x-if="!bannerPreview">
                                <div class="w-full h-full bg-gradient-to-r from-red-950/40 via-purple-950/30 to-[#0d0e17] flex items-center justify-center text-xs text-mist font-bold">
                                    Bannière Céleste par défaut
                                </div>
                            </template>
                            <template x-if="bannerPreview">
                                <img :src="bannerPreview" class="w-full h-full object-cover" alt="">
                            </template>
                        </div>
                        <div class="flex flex-col sm:flex-row gap-2">
                            <label class="px-4 py-2.5 rounded-xl bg-[#202236] hover:bg-[#282a44] text-white text-xs font-bold transition flex items-center justify-center gap-2 cursor-pointer flex-shrink-0">
                                <svg class="w-4 h-4 text-crimson" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                                <span>Téléverser une bannière</span>
                                <input type="file" name="banner_file" accept="image/*" class="hidden" @change="handleBanner($event)">
                            </label>
                            <input type="text" name="banner_url" class="flex-1 px-4 py-2.5 rounded-xl bg-[#0d0e17] border border-white/10 text-white placeholder-mist/50 text-xs focus:outline-none focus:border-crimson" placeholder="Ou lien direct de bannière (https://...)" @input="if ($el.value.startsWith('http')) { bannerPreview = $el.value; }">
                        </div>
                    </div>

                    {{-- Nom, Email & Bio --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="text-xs font-bold text-mist block mb-1">Pseudo public</label>
                            <input type="text" name="name" value="{{ old('name', $user->name) }}" required class="w-full px-4 py-2.5 rounded-xl bg-[#161726] border border-white/10 text-white text-xs focus:outline-none focus:border-crimson">
                        </div>

                        @if(!$user->pass_code)
                            <div>
                                <label class="text-xs font-bold text-mist block mb-1">Adresse Email</label>
                                <input type="email" name="email" value="{{ old('email', $user->email) }}" required class="w-full px-4 py-2.5 rounded-xl bg-[#161726] border border-white/10 text-white text-xs focus:outline-none focus:border-crimson">
                            </div>
                        @endif
                    </div>

                    <div>
                        <label class="text-xs font-bold text-mist block mb-1">Biographie / À propos</label>
                        <textarea name="bio" rows="3" class="w-full px-4 py-2.5 rounded-xl bg-[#161726] border border-white/10 text-white placeholder-mist/50 text-xs focus:outline-none focus:border-crimson resize-none" placeholder="Partagez vos goûts manhwa ou votre devise martiale...">{{ old('bio', $user->bio) }}</textarea>
                    </div>

                    {{-- Préférences de lecture --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="text-xs font-bold text-mist block mb-1">Genre favori</label>
                            <select name="favorite_genre" class="w-full px-4 py-2.5 rounded-xl bg-[#161726] border border-white/10 text-white text-xs focus:outline-none focus:border-crimson">
                                <option value="">— Aucun sélectionné —</option>
                                @foreach($genres as $genre)
                                    <option value="{{ $genre->name }}" {{ old('favorite_genre', $user->favorite_genre) === $genre->name ? 'selected' : '' }}>
                                        {{ $genre->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="text-xs font-bold text-mist block mb-1">Mode de lecture par défaut</label>
                            <select name="reader_mode" class="w-full px-4 py-2.5 rounded-xl bg-[#161726] border border-white/10 text-white text-xs focus:outline-none focus:border-crimson">
                                <option value="vertical" {{ old('reader_mode', $user->reader_mode) === 'vertical' ? 'selected' : '' }}>Cascade verticale (Webtoon)</option>
                                <option value="single" {{ old('reader_mode', $user->reader_mode) === 'single' ? 'selected' : '' }}>Page par page (Manga classique)</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex items-center justify-between pt-4 border-t border-white/10 flex-wrap gap-3">
                        <div>
                            @if (session('status') === 'profile-updated')
                                <span class="text-xs text-emerald-400 font-bold">✓ Modifications enregistrées avec succès !</span>
                            @endif
                        </div>
                        <button type="submit" class="px-6 py-2.5 rounded-xl bg-crimson hover:bg-crimson/90 text-white text-xs font-extrabold transition shadow-lg shadow-crimson/25">
                            Enregistrer les modifications
                        </button>
                    </div>
                </form>
            </div>

            {{-- 5.B. Sécurité & Pass Secret --}}
            <div x-show="settingsSubtab === 'security'" class="p-6 md:p-8 rounded-3xl bg-[#11121d] border border-white/[0.08] shadow-xl">
                <div class="mb-6">
                    <h2 class="text-xl font-black text-white flex items-center gap-2">
                        <svg class="w-5 h-5 text-crimson" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                        <span>Sécurité & Clés d'Accès</span>
                    </h2>
                    <p class="text-xs text-mist mt-1">
                        @if($user->pass_code)
                            Gérez votre Pass Secret pour vous reconnecter en toute confidentialité.
                        @else
                            Modifiez votre mot de passe pour sécuriser votre compte.
                        @endif
                    </p>
                </div>

                @if($user->pass_code)
                    @include('profile.partials.pass-secret-manager')
                @else
                    @include('profile.partials.update-password-form')
                @endif
            </div>

            {{-- 5.C. Zone de danger --}}
            <div x-show="settingsSubtab === 'danger'" class="p-6 md:p-8 rounded-3xl bg-[#1a1215] border border-red-500/20 shadow-xl">
                <div class="mb-6">
                    <h2 class="text-xl font-black text-red-400 flex items-center gap-2">
                        <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        <span>Zone de Danger</span>
                    </h2>
                    <p class="text-xs text-mist mt-1">Suppression définitive de votre compte et de toutes vos données de lecture.</p>
                </div>

                @include('profile.partials.delete-user-form')
            </div>

        </div>

        {{-- ═══════════════════════════════════════════════════════════
             MODALE DE SÉLECTION D'ŒUVRE (TOP 5 & TIER LIST)
             ═══════════════════════════════════════════════════════════ --}}
        <div 
            x-show="selectorOpen" 
            x-transition.opacity
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/85 backdrop-blur-md"
            style="display: none;"
            @keydown.escape.window="closeSelector()"
        >
            <div 
                class="w-full max-w-2xl bg-[#11121d] border border-white/10 rounded-3xl shadow-2xl overflow-hidden flex flex-col max-h-[85vh]"
                @click.outside="closeSelector()"
            >
                <div class="p-5 border-b border-white/10 flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-black text-white flex items-center gap-2">
                            <span>📚</span>
                            <span>Choisir une série</span>
                        </h3>
                        <p class="text-xs text-mist mt-0.5">Sélectionnez le webtoon à ajouter.</p>
                    </div>
                    <button type="button" @click="closeSelector()" class="w-8 h-8 rounded-full bg-white/5 hover:bg-white/10 text-mist hover:text-white flex items-center justify-center transition">
                        ✕
                    </button>
                </div>

                <div class="p-4 border-b border-white/5 bg-[#0d0e17]">
                    <div class="relative">
                        <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-mist" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        <input 
                            type="text" 
                            x-model="selectorSearch" 
                            placeholder="Rechercher une série par son titre..." 
                            class="w-full pl-10 pr-4 py-2.5 rounded-xl bg-[#161726] border border-white/10 text-white placeholder-mist/60 text-xs focus:outline-none focus:border-crimson"
                            x-ref="searchInput"
                        >
                    </div>
                </div>

                <div class="p-4 overflow-y-auto flex-1 grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3 custom-scrollbar">
                    <template x-for="manga in filteredMangas" :key="manga.id">
                        <div 
                            @click="selectMangaForTarget(manga)"
                            class="group rounded-2xl overflow-hidden border border-white/10 bg-[#161726] hover:border-amber-400 hover:scale-[1.02] transition cursor-pointer p-2 flex flex-col items-center text-center shadow-md"
                        >
                            <div class="w-full aspect-[2/3] rounded-xl overflow-hidden bg-black/40 mb-2">
                                <img :src="manga.cover || '/images/default-cover.jpg'" class="w-full h-full object-cover group-hover:scale-105 transition duration-300" alt="">
                            </div>
                            <span class="text-xs font-bold text-white line-clamp-2 leading-tight group-hover:text-amber-400 transition" x-text="manga.title"></span>
                            <span class="text-[10px] text-mist font-semibold uppercase mt-1" x-text="manga.type || 'Webtoon'"></span>
                        </div>
                    </template>

                    <div x-show="filteredMangas.length === 0" class="col-span-full py-12 text-center text-mist text-xs">
                        Aucune série ne correspond à votre recherche.
                    </div>
                </div>
            </div>
        </div>

        {{-- ═══════════════════════════════════════════════════════════
             MODALE CARTE DE DISCIPLE HOLOGRAPHIQUE
             ═══════════════════════════════════════════════════════════ --}}
        <div 
            x-show="cardModalOpen" 
            x-transition.opacity
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/85 backdrop-blur-md"
            style="display: none;"
            @keydown.escape.window="closeCardModal()"
        >
            <div 
                @click.outside="closeCardModal()"
                class="relative w-full max-w-sm rounded-3xl bg-gradient-to-b from-[#181628] via-[#100f1c] to-[#0a0914] border border-amber-400/40 p-6 shadow-[0_0_50px_rgba(251,191,36,0.2)] overflow-hidden"
            >
                <div class="flex items-center justify-between pb-3 mb-4 border-b border-white/10 relative z-10">
                    <div class="flex items-center gap-2">
                        <span class="text-xl">🎴</span>
                        <h3 class="text-sm font-black text-white uppercase tracking-wider">Carte de Cultivation</h3>
                    </div>
                    <button type="button" @click="closeCardModal()" class="w-7 h-7 rounded-lg bg-white/5 hover:bg-white/10 text-mist hover:text-white flex items-center justify-center transition">
                        ✕
                    </button>
                </div>

                {{-- Corps de la Carte --}}
                <div class="rounded-2xl p-5 bg-gradient-to-br from-[#1b192e] via-[#121122] to-[#0c0b16] border-2 border-amber-400/50 shadow-2xl relative overflow-hidden text-center">
                    <div class="flex items-center justify-between text-[10px] font-black tracking-widest text-amber-400/80 uppercase pb-2 mb-3 border-b border-amber-400/20">
                        <span>HIDDEN SCAN</span>
                        <span>ORDRE MARTIAL</span>
                    </div>

                    <div class="flex justify-center my-3">
                        <div class="w-20 h-20 rounded-full flex items-center justify-center relative" :class="getFrameClass(currentFrame)">
                            <div class="w-full h-full rounded-full overflow-hidden bg-[#161726] border-2 border-white/10 flex items-center justify-center">
                                <template x-if="!avatarPreview">
                                    <span class="font-black text-2xl text-amber-300">{{ $initials }}</span>
                                </template>
                                <template x-if="avatarPreview">
                                    <img :src="avatarPreview" class="w-full h-full object-cover" alt="">
                                </template>
                            </div>
                        </div>
                    </div>

                    <h4 class="text-lg font-black text-white tracking-wide mb-1">{{ $user->name }}</h4>
                    <div class="inline-flex items-center gap-1 px-3 py-0.5 rounded-full text-xs font-black mb-4 shadow-sm" style="background-color: {{ $murimStatus['current']['color'] }}25; color: {{ $murimStatus['current']['color'] }}; border: 1px solid {{ $murimStatus['current']['color'] }}60;">
                        <span>{{ $murimStatus['current']['badge'] }}</span>
                        <span>{{ $murimStatus['current']['title'] }}</span>
                    </div>

                    <div class="grid grid-cols-2 gap-2 text-left bg-black/40 p-3 rounded-xl border border-white/5 mb-3">
                        <div class="p-2 rounded-lg bg-white/[0.02]">
                            <span class="text-[10px] text-mist block">📖 Chapitres Lus</span>
                            <span class="text-xs font-black text-white">{{ number_format($readCount) }}</span>
                        </div>
                        <div class="p-2 rounded-lg bg-white/[0.02]">
                            <span class="text-[10px] text-mist block">🔥 Assiduité</span>
                            <span class="text-xs font-black text-amber-400">{{ $user->streak ?? 0 }} jours</span>
                        </div>
                        <div class="p-2 rounded-lg bg-white/[0.02]">
                            <span class="text-[10px] text-mist block">🛡️ Boucliers Qi</span>
                            <span class="text-xs font-black text-emerald-400">{{ $qiShields }}/2 Actifs</span>
                        </div>
                        <div class="p-2 rounded-lg bg-white/[0.02]">
                            <span class="text-[10px] text-mist block">⚡ Réserve Qi</span>
                            <span class="text-xs font-black text-cyan-400">{{ number_format($user->xp ?? ($readCount * 15)) }}</span>
                        </div>
                    </div>

                    <div class="p-2.5 rounded-xl bg-indigo-950/30 border border-indigo-500/20 text-left flex items-center justify-between mb-3">
                        <div class="flex items-center gap-2">
                            <span class="text-2xl" x-text="userBeastStatus?.current_stage?.icon || '🦅'">🦅</span>
                            <div>
                                <span class="text-[10px] text-indigo-300 font-bold block uppercase tracking-wider">Familier Spirituel</span>
                                <span class="text-xs font-black text-white" x-text="userBeastStatus?.beast?.name || 'Corbeau des Ombres'"></span>
                            </div>
                        </div>
                        <span class="text-[10px] font-extrabold text-amber-300 px-2 py-0.5 rounded-md bg-amber-400/10 border border-amber-400/20" x-text="'Stade ' + (userBeastStatus?.current_stage?.stage || 1)"></span>
                    </div>

                    <p class="text-[10px] italic text-mist/80 mb-2">
                        "{{ $murimStatus['current']['quote'] }}"
                    </p>
                    <div class="text-[9px] text-mist/60 uppercase tracking-widest pt-2 border-t border-white/5 flex items-center justify-between">
                        <span>Adoubé en {{ $user->created_at ? $user->created_at->translatedFormat('M Y') : '2024' }}</span>
                        <span class="text-amber-400/80">#SEAL-{{ str_pad($user->id, 5, '0', STR_PAD_LEFT) }}</span>
                    </div>
                </div>

                <div class="mt-4 flex items-center gap-2 relative z-10">
                    <button 
                        type="button" 
                        @click="copyProfileCardLink()" 
                        class="flex-1 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-black text-xs font-black transition flex items-center justify-center gap-1.5 shadow-lg shadow-amber-500/20"
                    >
                        <span>📋</span>
                        <span>Copier mon Statut</span>
                    </button>
                    <button 
                        type="button" 
                        @click="closeCardModal()" 
                        class="px-4 py-2.5 rounded-xl bg-[#161726] hover:bg-white/10 text-mist hover:text-white text-xs font-bold transition border border-white/10"
                    >
                        Fermer
                    </button>
                </div>
            </div>
        </div>

        {{-- Toast Notification --}}
        <div 
            x-show="toastVisible" 
            x-transition.opacity
            class="fixed bottom-6 right-6 z-50 px-4 py-3 rounded-2xl bg-[#161726] border border-amber-400/50 text-white text-xs font-bold shadow-2xl flex items-center gap-2.5 backdrop-blur-md"
            style="display: none;"
        >
            <span class="text-amber-400 text-sm">✓</span>
            <span x-text="toastMessage"></span>
        </div>

    </div>
</x-layouts.public>