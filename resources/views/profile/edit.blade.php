<x-layouts.public title="Profil — Hidden Scan">
    @php
        $initials = strtoupper(substr($user->name, 0, 2));
    @endphp

    <style>
        /* ── Structure Globale Pleine Page ── */
        .profile-page-wrapper {
            width: 100%;
            margin: 0 auto;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            color: #e8e8f0;
            font-size: 14px;
        }

        /* ── Hero Banner Pleine Largeur ── */
        .profile-hero-card {
            position: relative;
            width: 100%;
            min-height: 200px;
            border-radius: 16px;
            overflow: hidden;
            background: #0a0010;
            border: 1px solid #1e1e2e;
            box-shadow: 0 12px 36px rgba(0, 0, 0, 0.6);
        }

        .profile-hero-bg {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
        }

        .profile-hero-svg, .profile-hero-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .profile-hero-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(to right, rgba(8,8,13,0.92) 0%, rgba(8,8,13,0.5) 45%, rgba(8,8,13,0.92) 100%);
            pointer-events: none;
            z-index: 1;
        }

        .profile-hero-content {
            position: relative;
            z-index: 2;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 28px 36px;
            gap: 24px;
            min-height: 200px;
        }

        .profile-hero-left {
            display: flex;
            align-items: center;
            gap: 20px;
            min-width: 0;
        }

        /* Avatar */
        .profile-avatar-box {
            position: relative;
            width: 88px;
            height: 88px;
            border-radius: 50%;
            background-color: #1e2235;
            border: 3px solid #dc2626;
            box-shadow: 0 0 20px rgba(220, 38, 38, 0.35);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .profile-avatar-img-wrap {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #161824;
        }

        .profile-avatar-initials {
            font-size: 28px;
            font-weight: 800;
            color: #6880c0;
            user-select: none;
        }

        .profile-avatar-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        /* Détails Utilisateur */
        .profile-user-details {
            display: flex;
            flex-direction: column;
            gap: 6px;
            min-width: 0;
        }

        .profile-username-row {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .profile-username {
            font-size: 24px;
            font-weight: 800;
            color: #ffffff;
            line-height: 1.2;
            text-shadow: 0 2px 8px rgba(0,0,0,0.9);
            word-break: break-word;
        }

        .profile-meta-info {
            font-size: 13px;
            color: rgba(220,220,240,.85);
            text-shadow: 0 1px 4px rgba(0,0,0,0.9);
        }

        /* Zone droite du Hero */
        .profile-hero-right {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 16px;
            flex-shrink: 0;
        }

        .profile-hero-buttons {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .btn-profile-ghost {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #141420;
            border: 1px solid #262638;
            border-radius: 8px;
            color: #ffffff;
            font-size: 13px;
            font-weight: 600;
            padding: 9px 16px;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            box-shadow: 0 4px 12px rgba(0,0,0,0.5);
        }

        .btn-profile-ghost:hover {
            background: #1e1e30;
            border-color: #3b3b55;
            transform: translateY(-1px);
        }

        .btn-profile-ghost svg {
            width: 15px;
            height: 15px;
            stroke: currentColor;
            fill: none;
            flex-shrink: 0;
        }

        /* ── Barre d'onglets Pleine Page ── */
        .profile-tabs-bar {
            display: flex;
            align-items: center;
            background: #111118;
            border: 1px solid #1e1e2e;
            border-radius: 12px;
            padding: 6px;
            margin: 20px 0 24px;
            gap: 6px;
            overflow-x: auto;
            white-space: nowrap;
            scrollbar-width: none;
            -ms-overflow-style: none;
        }

        .profile-tabs-bar::-webkit-scrollbar {
            display: none !important;
            width: 0 !important;
            height: 0 !important;
        }

        .profile-tab-item {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 11px 20px;
            border-radius: 8px;
            background: transparent;
            border: 1px solid transparent;
            color: #8a8aa8;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            flex-shrink: 0;
        }

        .profile-tab-item:hover {
            color: #ffffff;
            background: #161622;
        }

        .profile-tab-item.active {
            background: #dc2626;
            color: #ffffff;
            border-color: #dc2626;
            box-shadow: 0 4px 14px rgba(220, 38, 38, 0.35);
        }

        .profile-tab-item svg {
            width: 16px;
            height: 16px;
            stroke: currentColor;
            fill: none;
            flex-shrink: 0;
        }

        /* ── Contenu d'onglets ── */
        .profile-content-section {
            width: 100%;
        }

        .profile-tab-panel {
            display: none;
        }

        .profile-tab-panel.active {
            display: block;
        }

        /* ── Grille de statistiques ── */
        .profile-stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            margin-bottom: 28px;
        }

        .profile-stat-box {
            background: #111118;
            border: 1px solid #1e1e2e;
            border-radius: 12px;
            padding: 20px 22px;
            display: flex;
            flex-direction: column;
            gap: 6px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.2);
            transition: transform 0.2s, border-color 0.2s;
        }

        .profile-stat-box:hover {
            transform: translateY(-2px);
            border-color: #2e2e42;
        }

        .profile-stat-num {
            font-size: 26px;
            font-weight: 800;
            color: #ffffff;
            line-height: 1.1;
        }

        .profile-stat-label {
            font-size: 13px;
            color: #7070a0;
            font-weight: 500;
        }

        /* En-tête de section */
        .profile-section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
        }

        .profile-section-heading {
            font-size: 17px;
            font-weight: 700;
            color: #ffffff;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* ── Cartes Reprendre la lecture ── */
        .profile-resume-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 16px;
            margin-bottom: 32px;
        }

        .profile-resume-card {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 14px 16px;
            background: #111118;
            border: 1px solid #1e1e2e;
            border-radius: 12px;
            text-decoration: none;
            transition: all 0.2s ease;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }

        .profile-resume-card:hover {
            border-color: #dc2626;
            transform: translateY(-2px);
            background: #161622;
        }

        .profile-resume-thumb {
            width: 56px;
            height: 78px;
            border-radius: 8px;
            overflow: hidden;
            background: #0e0e16;
            border: 1px solid #1e1e2e;
            flex-shrink: 0;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .profile-resume-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .profile-resume-placeholder {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #161824;
        }

        /* ── Commentaires ── */
        .profile-comment-card {
            padding: 16px 20px;
            background-color: #111118;
            border: 1px solid #1e1e2e;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            transition: border-color 0.2s;
        }

        .profile-comment-card:hover {
            border-color: #2e2e42;
        }

        /* ── Bibliothèque Manga ── */
        .profile-lib-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(190px, 1fr));
            gap: 20px;
        }

        .profile-manga-card {
            background-color: #111118;
            border: 1px solid #1e1e2e;
            border-radius: 12px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            transition: transform 0.2s, border-color 0.2s;
            box-shadow: 0 4px 16px rgba(0,0,0,0.25);
        }

        .profile-manga-card:hover {
            transform: translateY(-4px);
            border-color: #dc2626;
        }

        .profile-manga-cover-wrap {
            width: 100%;
            aspect-ratio: 2/3;
            min-height: 220px;
            background-color: #0e0e16;
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .profile-manga-cover {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .profile-manga-info {
            padding: 14px;
            display: flex;
            flex-direction: column;
            gap: 6px;
            flex: 1;
        }

        .profile-manga-title {
            font-size: 14px;
            font-weight: 700;
            color: #ffffff;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .profile-manga-meta {
            font-size: 12px;
            color: #7070a0;
        }

        .profile-manga-btn {
            margin-top: auto;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 8px 12px;
            background-color: #1e1e2e;
            border-radius: 6px;
            color: #e8e8f0;
            font-size: 12px;
            font-weight: 600;
            text-decoration: none;
            transition: background 0.2s;
        }

        .profile-manga-btn:hover {
            background-color: #dc2626;
            color: #ffffff;
        }

        /* ── Panneaux de formulaire (Édition & Paramètres) ── */
        .profile-card-panel {
            background: #111118;
            border: 1px solid #1e1e2e;
            border-radius: 12px;
            padding: 28px;
            box-shadow: 0 6px 24px rgba(0,0,0,0.3);
            margin-bottom: 24px;
        }

        .avatar-edit-box {
            display: flex;
            align-items: center;
            gap: 24px;
        }

        .avatar-prev-circle {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background-color: #1e2235;
            border: 2px solid #1e1e2e;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            overflow: hidden;
        }

        .banner-prev-rect {
            width: 100%;
            height: 140px;
            border-radius: 10px;
            overflow: hidden;
            background: #0a0010;
            border: 1px solid #1e1e2e;
            margin-bottom: 14px;
            position: relative;
        }

        .btn-upload-file {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 9px 18px;
            background-color: #16161f;
            border: 1px solid #1e1e2e;
            border-radius: 8px;
            color: #e8e8f0;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            width: fit-content;
        }

        .btn-upload-file:hover {
            background-color: #1e1e2e;
            border-color: #2e2e42;
        }

        .btn-upload-file svg {
            width: 16px;
            height: 16px;
            stroke: #dc2626;
            fill: none;
        }

        .input-dark {
            width: 100%;
            background-color: #16161f;
            border: 1px solid #1e1e2e;
            border-radius: 8px;
            padding: 11px 16px;
            color: #e8e8f0;
            font-size: 14px;
            outline: none;
            transition: border-color 0.2s;
        }

        .input-dark:focus {
            border-color: #dc2626;
        }

        .input-dark::placeholder {
            color: #505068;
        }

        .line-divider {
            height: 1px;
            background-color: #1e1e2e;
            margin: 24px 0;
        }

        .btn-save-red {
            background-color: #dc2626;
            color: #ffffff;
            border: none;
            border-radius: 8px;
            padding: 11px 26px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.2s, transform 0.1s;
        }

        .btn-save-red:hover {
            background-color: #b91c1c;
        }

        .btn-save-red:active {
            transform: scale(0.98);
        }

        .empty-box {
            text-align: center;
            padding: 40px 20px;
            background-color: #111118;
            border: 1px dashed #1e1e2e;
            border-radius: 12px;
            color: #7070a0;
        }

        /* Sub-tab pills */
        .subtab-pill {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            background: #111118;
            border: 1px solid #1e1e2e;
            color: #7070a0;
            cursor: pointer;
            transition: all 0.2s;
        }

        .subtab-pill.active {
            background: #dc2626;
            border-color: #dc2626;
            color: #ffffff;
        }

        /* ── Adaptations Mobiles (< 768px) ── */
        @media (max-width: 768px) {
            .profile-hero-card {
                border-radius: 12px;
                min-height: auto;
            }

            .profile-hero-content {
                flex-direction: column;
                align-items: stretch;
                padding: 18px 16px;
                gap: 16px;
                min-height: auto;
            }

            .profile-hero-left {
                display: flex;
                align-items: center;
                gap: 14px;
                width: 100%;
            }

            .profile-hero-overlay {
                background: linear-gradient(to bottom, rgba(8,8,13,0.5) 0%, rgba(8,8,13,0.85) 50%, rgba(8,8,13,0.96) 100%);
            }

            .profile-avatar-box {
                width: 68px;
                height: 68px;
            }

            .profile-avatar-initials {
                font-size: 20px;
            }

            .profile-username {
                font-size: 19px;
            }

            .profile-hero-right {
                width: 100%;
                align-items: stretch;
                gap: 12px;
            }

            .profile-hero-buttons {
                display: grid;
                grid-template-columns: 1fr 1fr;
                width: 100%;
                gap: 8px;
            }

            .btn-profile-ghost {
                justify-content: center;
                padding: 9px 8px;
                font-size: 12px;
                text-align: center;
            }

            .profile-tabs-bar {
                margin: 14px 0 18px;
                padding: 4px;
                gap: 4px;
            }

            .profile-tab-item {
                padding: 8px 14px;
                font-size: 12px;
                gap: 6px;
            }

            .profile-stats-grid {
                grid-template-columns: repeat(3, 1fr);
                gap: 10px;
                margin-bottom: 20px;
            }

            .profile-stat-box {
                padding: 14px 16px;
            }

            .profile-stat-num {
                font-size: 20px;
            }

            .profile-resume-grid {
                grid-template-columns: 1fr;
                gap: 10px;
            }

            .profile-lib-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 10px;
            }

            .profile-card-panel {
                padding: 20px 16px;
            }

            .avatar-edit-box {
                flex-direction: column;
                align-items: flex-start;
                gap: 14px;
            }

            .btn-save-red {
                width: 100%;
                text-align: center;
            }
        }
    </style>

    <script>
        function profileComponent() {
            return {
                currentTab: '{{ $defaultTab ?? 'overview' }}',
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
                selectorTarget: null, // 'top:0', 'tier:S', etc.
                selectorSearch: '',
                toastMessage: '',
                toastVisible: false,

                showToast(msg) {
                    this.toastMessage = msg;
                    this.toastVisible = true;
                    setTimeout(() => { this.toastVisible = false; }, 3500);
                },

                openSelector(target) {
                    this.selectorTarget = target;
                    this.selectorSearch = '';
                    this.selectorOpen = true;
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
                            this.showToast('Top 5 séries mis à jour !');
                        }
                    } catch(e) {
                        this.showToast('Erreur lors de l\'enregistrement.');
                    }
                },

                init() {
                    this.loadData();
                    this.fetchProgress();

                    // Restauration résiliente si le serveur n'a pas encore l'image
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
                    }
                },
                switchTab(name) {
                    this.currentTab = name;
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

    <div class="profile-page-wrapper" x-data="profileComponent()">

        {{-- ═══════════════════════════════════════════
             BLOC 1 — HERO BANNER (Pleine Largeur)
             ═══════════════════════════════════════════ --}}
        <div class="profile-hero-card">
            <div class="profile-hero-bg">
                <template x-if="!bannerPreview">
                    <svg class="profile-hero-svg" viewBox="0 0 1200 220" preserveAspectRatio="xMidYMid slice" xmlns="http://www.w3.org/2000/svg">
                        <defs>
                            <radialGradient id="spaceGradApp" cx="75%" cy="30%" r="85%">
                                <stop offset="0%" stop-color="#8b0000" stop-opacity="0.85"/>
                                <stop offset="45%" stop-color="#2a001a" stop-opacity="0.9"/>
                                <stop offset="100%" stop-color="#0a0010" stop-opacity="1"/>
                            </radialGradient>
                            <radialGradient id="redGlowApp" cx="85%" cy="40%" r="40%">
                                <stop offset="0%" stop-color="#ff1a40" stop-opacity="0.45"/>
                                <stop offset="100%" stop-color="#ff1a40" stop-opacity="0"/>
                            </radialGradient>
                            <radialGradient id="smallPlanetGradApp" cx="35%" cy="35%" r="65%">
                                <stop offset="0%" stop-color="#ff4d4d"/>
                                <stop offset="60%" stop-color="#8b0000"/>
                                <stop offset="100%" stop-color="#2a0008"/>
                            </radialGradient>
                            <radialGradient id="moonGradApp" cx="40%" cy="35%" r="65%">
                                <stop offset="0%" stop-color="#cbd5e1"/>
                                <stop offset="50%" stop-color="#64748b"/>
                                <stop offset="90%" stop-color="#1e293b"/>
                                <stop offset="100%" stop-color="#0f172a"/>
                            </radialGradient>
                        </defs>
                        <rect width="1200" height="220" fill="url(#spaceGradApp)" />
                        <rect width="1200" height="220" fill="url(#redGlowApp)" />
                        
                        <circle cx="50" cy="30" r="1.2" fill="#ffffff" opacity="0.8" />
                        <circle cx="120" cy="115" r="0.9" fill="#ffffff" opacity="0.6" />
                        <circle cx="210" cy="35" r="1.4" fill="#ffffff" opacity="0.9" />
                        <circle cx="310" cy="90" r="0.8" fill="#ffffff" opacity="0.5" />
                        <circle cx="430" cy="30" r="1.1" fill="#ffffff" opacity="0.7" />
                        <circle cx="540" cy="150" r="0.9" fill="#ffffff" opacity="0.6" />
                        <circle cx="670" cy="45" r="1.5" fill="#ffffff" opacity="0.8" />
                        <circle cx="780" cy="120" r="0.8" fill="#ffffff" opacity="0.7" />
                        <circle cx="920" cy="40" r="1.3" fill="#ffffff" opacity="0.9" />
                        <circle cx="1080" cy="85" r="1.0" fill="#ffffff" opacity="0.7" />

                        <circle cx="240" cy="70" r="18" fill="url(#smallPlanetGradApp)" />
                        <circle cx="640" cy="105" r="54" fill="url(#moonGradApp)" />
                        <circle cx="625" cy="88" r="9" fill="#1e293b" opacity="0.25" />
                        <circle cx="660" cy="118" r="11" fill="#1e293b" opacity="0.2" />
                    </svg>
                </template>
                <template x-if="bannerPreview">
                    <img :src="bannerPreview" class="profile-hero-img" alt="Bannière de profil">
                </template>
                <div class="profile-hero-overlay"></div>
            </div>

            <div class="profile-hero-content">
                <div class="profile-hero-left">
                    <div class="profile-avatar-box">
                        <div class="profile-avatar-img-wrap">
                            <template x-if="!avatarPreview">
                                <span class="profile-avatar-initials">{{ $initials }}</span>
                            </template>
                            <template x-if="avatarPreview">
                                <img :src="avatarPreview" class="profile-avatar-img" alt="Avatar">
                            </template>
                        </div>
                    </div>
                    <div class="profile-user-details">
                        <div class="profile-username-row">
                            <span class="profile-username">{{ $user->name }}</span>
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-black shadow-sm" style="background-color: {{ $murimStatus['current']['color'] }}20; color: {{ $murimStatus['current']['color'] }}; border: 1px solid {{ $murimStatus['current']['color'] }}50;">
                                <span>{{ $murimStatus['current']['badge'] }}</span>
                                <span>{{ $murimStatus['current']['title'] }}</span>
                            </span>
                            @if($user->staff_badge)
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wider border {{ $user->staff_badge['bg'] }} {{ $user->staff_badge['text'] }} {{ $user->staff_badge['border'] }} shadow-sm">
                                    {{ $user->staff_badge['name'] }}
                                </span>
                            @endif
                        </div>
                        <div class="profile-meta-info flex items-center gap-2.5 mt-1 text-xs text-mist flex-wrap">
                            <span>Niveau {{ $user->level }}</span>
                            <span>•</span>
                            <span class="text-amber-400 font-bold">🔥 {{ $user->streak ?? 0 }}j d'assiduité</span>
                            <span>•</span>
                            <span class="text-cyan-400 font-bold">⚡ {{ number_format($user->xp ?? ($readCount * 15)) }} Qi (XP)</span>
                            <span>•</span>
                            <span>Membre depuis {{ $user->created_at->translatedFormat('F Y') }}</span>
                        </div>
                    </div>
                </div>

                <div class="profile-hero-right">
                    <div class="profile-hero-buttons">
                        <button type="button" class="btn-profile-ghost" @click="switchTab('edit')">
                            <svg viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                            Modifier profil
                        </button>
                        <button type="button" class="btn-profile-ghost" @click="switchTab('settings')">
                            <svg viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
                            Paramètres
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- ═══════════════════════════════════════════
             BLOC 2 — BARRE D'ONGLETS DE L'ÉCOSYSTÈME
             ═══════════════════════════════════════════ --}}
        <div class="profile-tabs-bar">
            <button class="profile-tab-item" :class="currentTab === 'overview' ? 'active' : ''" @click="switchTab('overview')">
                <span>🥋</span>
                <span>Aperçu & Voie Martiale</span>
            </button>
            <button class="profile-tab-item" :class="currentTab === 'activity' ? 'active' : ''" @click="switchTab('activity')">
                <span>📅</span>
                <span>Suivi d'Assiduité</span>
            </button>
            <button class="profile-tab-item" :class="currentTab === 'tierlist' ? 'active' : ''" @click="switchTab('tierlist')">
                <span>👑</span>
                <span>Tier List Webtoons</span>
            </button>
            <button class="profile-tab-item" :class="currentTab === 'leaderboard' ? 'active' : ''" @click="switchTab('leaderboard')">
                <span>🏆</span>
                <span>Classement</span>
            </button>
            <button class="profile-tab-item" :class="currentTab === 'library' ? 'active' : ''" @click="switchTab('library')">
                <svg viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg>
                <span>Bibliothèque (<span x-text="favorites.length">0</span>)</span>
            </button>
            <button class="profile-tab-item" :class="currentTab === 'edit' ? 'active' : ''" @click="switchTab('edit')">
                <svg viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4"><path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>
                <span>Modifier profil</span>
            </button>
            <button class="profile-tab-item" :class="currentTab === 'settings' ? 'active' : ''" @click="switchTab('settings')">
                <svg viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
                <span>Sécurité</span>
            </button>
        </div>

        {{-- ═══════════════════════════════════════════
             BLOC 3 — CONTENU PRINCIPAL
             ═══════════════════════════════════════════ --}}
        <div class="profile-content-section">

            {{-- ── Panel 1 : Aperçu ── --}}
            <div class="profile-tab-panel" :class="currentTab === 'overview' ? 'active' : ''">


                {{-- Encart de Percée de Qi & Voie Martiale --}}
                <div class="mb-8 p-6 rounded-2xl bg-gradient-to-br from-ink-deep via-panel to-panel border border-white/10 shadow-xl relative overflow-hidden">
                    <div class="absolute -right-10 -bottom-10 w-48 h-48 bg-amber-500/10 rounded-full blur-3xl pointer-events-none"></div>
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-4 relative z-10">
                        <div class="flex items-center gap-3.5">
                            <div class="w-14 h-14 rounded-2xl bg-white/[0.04] border border-white/10 flex items-center justify-center text-3xl shadow-inner">
                                {{ $murimStatus['current']['badge'] }}
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-black uppercase tracking-wider text-amber-400">Royaume Actuel</span>
                                    <span class="text-[10px] text-mist font-bold">({{ $murimStatus['current']['hangeul'] }} / {{ $murimStatus['current']['pinyin'] }})</span>
                                </div>
                                <h2 class="text-xl font-black text-white flex items-center gap-2">
                                    {{ $murimStatus['current']['title'] }}
                                    <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-white/5 text-chalk border border-white/10">{{ $murimStatus['current']['aura'] }}</span>
                                </h2>
                                <p class="text-xs text-mist italic mt-0.5">« {{ $murimStatus['current']['quote'] }} »</p>
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

                    {{-- Barre d'Essence de Qi --}}
                    <div class="relative z-10">
                        <div class="flex items-center justify-between text-xs font-bold mb-1.5">
                            <span class="text-chalk flex items-center gap-1.5">
                                <span>⚡ Concentration de Qi</span>
                                <span class="text-amber-400">({{ $murimStatus['percent'] }}%)</span>
                            </span>
                            <span class="text-mist">{{ number_format($readCount) }} / {{ $murimStatus['next'] ? $murimStatus['next']['min_chapters'] : number_format($readCount) }} chapitres</span>
                        </div>
                        <div class="w-full h-3 bg-black/50 rounded-full overflow-hidden border border-white/5 shadow-inner">
                            <div class="h-full bg-gradient-to-r from-amber-500 via-crimson to-amber-300 rounded-full transition-all duration-1000 shadow-[0_0_12px_rgba(245,158,11,0.5)]" style="width: {{ $murimStatus['percent'] }}%"></div>
                        </div>
                    </div>
                </div>

                {{-- Grille Statistiques Clés --}}
                <div class="profile-stats-grid mb-8">
                    <div class="profile-stat-box">
                        <span class="profile-stat-num">{{ number_format($readCount) }}</span>
                        <span class="profile-stat-label">📖 Chapitres lus</span>
                    </div>
                    <div class="profile-stat-box">
                        <span class="profile-stat-num" x-text="favorites.length">{{ $favoritesCount }}</span>
                        <span class="profile-stat-label">❤️ Favoris</span>
                    </div>
                    <div class="profile-stat-box">
                        <span class="profile-stat-num">{{ $commentsCount }}</span>
                        <span class="profile-stat-label">💬 Commentaires</span>
                    </div>
                    <div class="profile-stat-box">
                        <span class="profile-stat-num text-amber-400">{{ $user->streak ?? 0 }}j</span>
                        <span class="profile-stat-label">🔥 Flamme active</span>
                    </div>
                </div>

                {{-- Section Top 5 Séries Représentatives --}}
                <div class="mb-8 p-6 rounded-2xl bg-panel border border-white/10 shadow-lg">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="text-base font-black text-white flex items-center gap-2">
                                <span>⭐</span>
                                <span>Mes 5 Séries Incontournables</span>
                            </h3>
                            <p class="text-xs text-mist">Les 5 œuvres majeures qui illustrent votre voie dans le Jianghu.</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button" @click="saveTopMangas()" class="px-3 py-1.5 rounded-xl bg-crimson hover:bg-crimson/80 text-white text-xs font-bold transition shadow-sm flex items-center gap-1.5">
                                <span>💾</span>
                                <span>Sauvegarder</span>
                            </button>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-3.5">
                        @for($slot = 0; $slot < 5; $slot++)
                            <div class="relative group rounded-xl overflow-hidden border border-white/10 bg-ink-deep aspect-[2/3] flex flex-col items-center justify-center text-center p-2 transition hover:border-amber-400/50">
                                <div class="absolute top-2 left-2 z-20 w-6 h-6 rounded-full bg-black/80 backdrop-blur-sm border border-white/20 flex items-center justify-center text-[10px] font-black text-amber-400 shadow">
                                    #{{ $slot + 1 }}
                                </div>

                                <template x-if="topMangasIds[{{ $slot }}] && getManga(topMangasIds[{{ $slot }}])">
                                    <div class="w-full h-full relative">
                                        <img :src="getManga(topMangasIds[{{ $slot }}]).cover || '/images/default-cover.jpg'" class="w-full h-full object-cover rounded-lg" alt="">
                                        <div class="absolute inset-0 bg-gradient-to-t from-black via-black/40 to-transparent flex flex-col justify-end p-2.5">
                                            <span class="text-xs font-bold text-white line-clamp-2 leading-tight" x-text="getManga(topMangasIds[{{ $slot }}]).title"></span>
                                        </div>
                                        <button type="button" @click.stop="removeTopSlot({{ $slot }})" class="absolute top-2 right-2 z-20 w-6 h-6 rounded-full bg-crimson/90 text-white flex items-center justify-center text-xs opacity-0 group-hover:opacity-100 transition shadow hover:scale-110" title="Retirer">
                                            ✕
                                        </button>
                                    </div>
                                </template>

                                <template x-if="!topMangasIds[{{ $slot }}] || !getManga(topMangasIds[{{ $slot }}])">
                                    <button type="button" @click="openSelector('top:{{ $slot }}')" class="w-full h-full flex flex-col items-center justify-center gap-2 border-2 border-dashed border-white/10 rounded-lg hover:border-crimson/60 hover:bg-white/[0.02] transition cursor-pointer p-4 group">
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

                {{-- Section : Reprendre la lecture --}}
                <div style="margin-bottom: 36px;" x-show="history.length > 0">
                    <div class="profile-section-header">
                        <h3 class="profile-section-heading">
                            <svg style="width: 18px; height: 18px; stroke: #dc2626; fill: none;" viewBox="0 0 24 24" stroke-width="2"><path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Reprendre la lecture
                        </h3>
                        <button type="button" @click="switchTab('library'); libTab = 'history'" style="background: none; border: none; color: #7070a0; font-size: 13px; cursor: pointer; font-weight: 600;">Voir tout l'historique →</button>
                    </div>

                    <div class="profile-resume-grid">
                        <template x-for="item in history.slice(0, 3)" :key="item.mangaSlug + item.chapterSlug">
                            <a :href="'/lecture/' + item.mangaSlug + '/' + item.chapterSlug" class="profile-resume-card">
                                <div class="profile-resume-thumb">
                                    <template x-if="item.cover">
                                        <img :src="item.cover" class="profile-resume-img" alt="" onerror="this.style.display='none'; if(this.nextElementSibling) this.nextElementSibling.style.display='flex'">
                                    </template>
                                    <div class="profile-resume-placeholder" :style="item.cover ? 'display: none;' : 'display: flex;'">
                                        <svg style="width: 24px; height: 24px; color: #505068; stroke: currentColor; fill: none;" viewBox="0 0 24 24" stroke-width="1.5"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg>
                                    </div>
                                </div>
                                <div style="display: flex; flex-direction: column; justify-content: center; gap: 4px; overflow: hidden; flex: 1; min-width: 0;">
                                    <span style="font-size: 14px; font-weight: bold; color: #ffffff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" x-text="item.mangaTitle"></span>
                                    <span style="font-size: 12px; color: #dc2626; font-weight: 600;" x-text="item.chapterTitle"></span>
                                    <span style="font-size: 11px; color: #7070a0;">Continuer la lecture →</span>
                                </div>
                            </a>
                        </template>
                    </div>
                </div>

                {{-- Section : Mes derniers commentaires --}}
                <div>
                    <div class="profile-section-header">
                        <h3 class="profile-section-heading">
                            <svg style="width: 18px; height: 18px; stroke: #dc2626; fill: none;" viewBox="0 0 24 24" stroke-width="2"><path d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                            Mes derniers commentaires
                        </h3>
                    </div>

                    @if($recentComments->isNotEmpty())
                        <div style="display: flex; flex-direction: column; gap: 12px;">
                            @foreach($recentComments as $comment)
                                <div class="profile-comment-card">
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; gap: 10px;">
                                        <div style="font-size: 13px; font-weight: 600; color: #ffffff; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                            @if($comment->commentable instanceof \App\Models\Manga)
                                                Sur <a href="{{ route('manga.show', $comment->commentable->slug) }}" style="color: #dc2626; text-decoration: none;">{{ $comment->commentable->title }}</a>
                                            @elseif($comment->commentable instanceof \App\Models\Chapter)
                                                Sur <a href="{{ route('reader.show', [$comment->commentable->manga->slug, $comment->commentable->slug]) }}" style="color: #dc2626; text-decoration: none;">{{ $comment->commentable->manga->title }} — {{ $comment->commentable->title }}</a>
                                            @else
                                                Commentaire
                                            @endif
                                        </div>
                                        <span style="font-size: 12px; color: #7070a0; flex-shrink: 0;">{{ $comment->created_at->diffForHumans() }}</span>
                                    </div>
                                    <p style="font-size: 14px; color: #c0c0d8; line-height: 1.5; margin: 0; word-break: break-word;">
                                        {{ \Illuminate\Support\Str::limit(strip_tags($comment->content), 180) }}
                                    </p>
                                    <div style="margin-top: 8px; font-size: 12px; color: #7070a0; display: flex; align-items: center; gap: 10px;">
                                        <span>👍 {{ $comment->likes_count ?? 0 }} likes</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="empty-box">
                            <span style="font-size: 32px; display: block; margin-bottom: 8px;">💬</span>
                            <p style="font-size: 14px; margin: 0;">Vous n'avez pas encore posté de commentaire.</p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- ── Panel : Suivi d'Assiduité (Heatmap Mensuelle) ── --}}
            <div class="profile-tab-panel" :class="currentTab === 'activity' ? 'active' : ''">
                <div class="profile-card-panel">
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
                                Visualisez l'intensité de votre cultivation quotidienne. Chaque chapitre lu nourrit votre Qi martial !
                            </p>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl bg-panel border border-white/10 text-xs font-bold text-chalk">
                                <span class="text-amber-400">🔥 Flamme active :</span>
                                <strong class="text-white">{{ $user->streak ?? 0 }} jours</strong>
                            </span>
                        </div>
                    </div>

                    {{-- 4 Cartes Statistiques Mensuelles --}}
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3.5 mb-8">
                        <div class="p-4 rounded-xl bg-ink-deep/80 border border-white/5">
                            <span class="text-2xl font-black text-white block">{{ $monthlyHeatmap['total_chapters'] }}</span>
                            <span class="text-xs text-mist font-medium mt-0.5 block">Chapitres ce mois</span>
                        </div>
                        <div class="p-4 rounded-xl bg-ink-deep/80 border border-white/5">
                            <span class="text-2xl font-black text-amber-400 block">{{ $monthlyHeatmap['active_days'] }} j</span>
                            <span class="text-xs text-mist font-medium mt-0.5 block">Jours actifs</span>
                        </div>
                        <div class="p-4 rounded-xl bg-ink-deep/80 border border-white/5">
                            <span class="text-2xl font-black text-cyan-400 block">{{ $monthlyHeatmap['best_day_count'] }}</span>
                            <span class="text-xs text-mist font-medium mt-0.5 block">Record en 1 jour ({{ $monthlyHeatmap['best_day_label'] }})</span>
                        </div>
                        <div class="p-4 rounded-xl bg-ink-deep/80 border border-white/5">
                            <span class="text-2xl font-black text-emerald-400 block">{{ $monthlyHeatmap['assiduity_rate'] }}%</span>
                            <span class="text-xs text-mist font-medium mt-0.5 block">Taux d'assiduité</span>
                        </div>
                    </div>

                    {{-- Grille du Calendrier --}}
                    <div class="p-5 rounded-2xl bg-[#0e0e18] border border-white/10">
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
                            {{-- Cases vides début de mois --}}
                            @for($i = 0; $i < $monthlyHeatmap['leading_empty_days']; $i++)
                                <div class="aspect-square rounded-xl bg-transparent border border-dashed border-white/[0.03]"></div>
                            @endfor

                            {{-- Jours réels du mois --}}
                            @foreach($monthlyHeatmap['days'] as $day)
                                @php
                                    $bgClass = match($day['intensity']) {
                                        0 => 'bg-[#151522] border-white/5 text-mist/60 hover:border-white/20',
                                        1 => 'bg-indigo-950/70 border-indigo-700/50 text-indigo-200 hover:border-indigo-400',
                                        2 => 'bg-red-950/80 border-red-600/50 text-red-100 hover:border-red-400',
                                        3 => 'bg-red-800/90 border-red-500/80 text-white hover:border-red-300 shadow-[0_0_10px_rgba(239,68,68,0.3)]',
                                        4 => 'bg-gradient-to-tr from-amber-600 to-yellow-400 border-amber-300 text-black font-black shadow-[0_0_15px_rgba(245,158,11,0.6)] hover:scale-105',
                                        default => 'bg-[#151522] border-white/5 text-mist/60',
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

                                    {{-- Tooltip au hover --}}
                                    <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover:block z-30 px-2.5 py-1 rounded-lg bg-black text-white text-[10px] whitespace-nowrap shadow-xl border border-white/10 pointer-events-none">
                                        {{ $day['day_number'] }} {{ $monthlyHeatmap['month_label'] }} : <strong>{{ $day['chapters_read'] }}</strong> chap.
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        {{-- Légende d'intensité de Qi --}}
                        <div class="flex items-center justify-end gap-2 mt-4 pt-4 border-t border-white/5 text-[11px] text-mist font-medium">
                            <span>Moins de Qi</span>
                            <span class="w-3.5 h-3.5 rounded bg-[#151522] border border-white/10" title="0 chap."></span>
                            <span class="w-3.5 h-3.5 rounded bg-indigo-950/70 border border-indigo-700/50" title="1-2 chap."></span>
                            <span class="w-3.5 h-3.5 rounded bg-red-950/80 border border-red-600/50" title="3-5 chap."></span>
                            <span class="w-3.5 h-3.5 rounded bg-red-800/90 border border-red-500/80" title="6-8 chap."></span>
                            <span class="w-3.5 h-3.5 rounded bg-gradient-to-tr from-amber-600 to-yellow-400 border border-amber-300" title="9+ chap."></span>
                            <span>Flamme Suprême</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ── Panel : Tier List Webtoons ── --}}
            <div class="profile-tab-panel" :class="currentTab === 'tierlist' ? 'active' : ''">
                <div class="profile-card-panel">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
                        <div>
                            <h2 class="text-xl font-black text-white flex items-center gap-2.5">
                                <span>👑</span>
                                <span>Ma Tier List Webtoons</span>
                                <span class="text-xs px-2.5 py-0.5 rounded-full bg-crimson/10 text-crimson border border-crimson/20 font-bold">
                                    Échelle de Jugement S à F
                                </span>
                            </h2>
                            <p class="text-xs text-mist mt-1">
                                Hiérarchisez vos séries selon leur valeur martiale. Vos choix sont enregistrés sur votre profil !
                            </p>
                        </div>
                        <div class="flex items-center gap-2.5">
                            <button type="button" @click="saveTierList()" class="btn-save-red !py-2.5 !px-5 text-xs flex items-center gap-2 shadow-lg">
                                <span>💾</span>
                                <span>Enregistrer ma Tier List</span>
                            </button>
                        </div>
                    </div>

                    {{-- Rangées de la Tier List --}}
                    @php
                        $tiersMeta = [
                            'S' => ['label' => 'Divin & Suprême', 'bg' => 'bg-red-600 text-white', 'border' => 'border-red-500/40'],
                            'A' => ['label' => 'Grand Maître', 'bg' => 'bg-purple-600 text-white', 'border' => 'border-purple-500/40'],
                            'B' => ['label' => 'Élite du Qi', 'bg' => 'bg-blue-600 text-white', 'border' => 'border-blue-500/40'],
                            'C' => ['label' => 'Disciple Prometteur', 'bg' => 'bg-emerald-600 text-white', 'border' => 'border-emerald-500/40'],
                            'D' => ['label' => 'Guerrier Moyen', 'bg' => 'bg-amber-600 text-white', 'border' => 'border-amber-500/40'],
                            'F' => ['label' => 'Abysses Martiales', 'bg' => 'bg-zinc-700 text-white', 'border' => 'border-zinc-600/40'],
                        ];
                    @endphp

                    <div class="space-y-3">
                        @foreach(['S', 'A', 'B', 'C', 'D', 'F'] as $tierKey)
                            @php $meta = $tiersMeta[$tierKey]; @endphp
                            <div class="flex flex-col sm:flex-row items-stretch rounded-2xl bg-[#0e0e18] border border-white/10 overflow-hidden shadow-md">
                                {{-- En-tête du Rang (gauche) --}}
                                <div class="w-full sm:w-28 sm:min-w-[7rem] flex sm:flex-col items-center justify-between sm:justify-center p-3 text-center {{ $meta['bg'] }} font-black">
                                    <span class="text-2xl">{{ $tierKey }}</span>
                                    <span class="text-[9px] uppercase tracking-wider opacity-90 leading-tight">{{ $meta['label'] }}</span>
                                    <button type="button" @click="openSelector('tier:{{ $tierKey }}')" class="sm:hidden px-2 py-1 rounded bg-black/30 text-white text-[10px] font-bold">
                                        + Ajouter
                                    </button>
                                </div>

                                {{-- Conteneur des cartes de ce rang --}}
                                <div class="flex-1 p-3 flex items-center gap-3 overflow-x-auto min-h-[110px] bg-ink-deep/50">
                                    <template x-for="mId in (tierList['{{ $tierKey }}'] || [])" :key="mId">
                                        <div class="relative group/manga flex-shrink-0 w-20 aspect-[2/3] rounded-lg overflow-hidden border border-white/10 bg-black/60 shadow-md">
                                            <template x-if="getManga(mId)">
                                                <div class="w-full h-full relative">
                                                    <img :src="getManga(mId).cover || '/images/default-cover.jpg'" class="w-full h-full object-cover" alt="">
                                                    <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/30 to-transparent p-1 flex flex-col justify-end">
                                                        <span class="text-[10px] font-bold text-white line-clamp-2 leading-tight" x-text="getManga(mId).title"></span>
                                                    </div>
                                                    <button type="button" @click.stop="removeFromTier('{{ $tierKey }}', mId)" class="absolute top-1 right-1 w-5 h-5 rounded-full bg-crimson text-white flex items-center justify-center text-[10px] opacity-0 group-hover/manga:opacity-100 transition shadow hover:scale-110" title="Retirer de ce rang">
                                                        ✕
                                                    </button>
                                                </div>
                                            </template>
                                        </div>
                                    </template>

                                    {{-- Bouton ajouter dans le flux desktop --}}
                                    <button type="button" @click="openSelector('tier:{{ $tierKey }}')" class="flex-shrink-0 w-20 aspect-[2/3] rounded-lg border-2 border-dashed border-white/10 hover:border-amber-400/60 hover:bg-white/[0.02] transition flex flex-col items-center justify-center gap-1 text-mist hover:text-white cursor-pointer group">
                                        <span class="text-xl group-hover:scale-110 transition">+</span>
                                        <span class="text-[9px] font-bold uppercase tracking-wider">Ajouter</span>
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- ── Panel : Classement des Lecteurs ── --}}
            <div class="profile-tab-panel" :class="currentTab === 'leaderboard' ? 'active' : ''">
                <div class="profile-card-panel">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
                        <div>
                            <h2 class="text-xl font-black text-white flex items-center gap-2.5">
                                <span>🏆</span>
                                <span>Panthéon des Cultivateurs</span>
                                <span class="text-xs px-2.5 py-0.5 rounded-full bg-amber-500/10 text-amber-400 border border-amber-500/20 font-bold">
                                    Top Disciples du Jianghu
                                </span>
                            </h2>
                            <p class="text-xs text-mist mt-1">
                                Le classement officiel des lecteurs de Hidden Scan selon leur niveau de cultivation et leurs chapitres maîtrisés.
                            </p>
                        </div>
                        <a href="{{ route('leaderboard') }}" class="px-4 py-2 rounded-xl bg-white/5 hover:bg-white/10 border border-white/10 text-xs font-bold text-white transition flex items-center gap-2">
                            <span>Voir le Panthéon Public</span>
                            <span>→</span>
                        </a>
                    </div>

                    {{-- Position actuelle du lecteur connecté --}}
                    <div class="mb-6 p-4 rounded-xl bg-gradient-to-r from-amber-500/10 via-panel to-panel border border-amber-500/30 flex items-center justify-between gap-4 flex-wrap">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-amber-500/20 border border-amber-500/40 flex items-center justify-center text-lg font-black text-amber-400">
                                #{{ $leaderboard['current_user_rank'] }}
                            </div>
                            <div>
                                <span class="text-xs text-mist font-semibold">Votre rang actuel dans le Jianghu</span>
                                <div class="text-sm font-bold text-white flex items-center gap-2">
                                    <span>{{ $user->name }}</span>
                                    <span class="text-xs text-amber-400">{{ $murimStatus['current']['badge'] }} {{ $murimStatus['current']['title'] }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center gap-4 text-xs font-bold text-mist">
                            <span>📖 <strong>{{ number_format($readCount) }}</strong> chapitres</span>
                            <span>⚡ <strong>{{ number_format($user->xp ?? ($readCount * 15)) }}</strong> Qi</span>
                            <span>🔥 <strong>{{ $user->streak ?? 0 }}j</strong> streak</span>
                        </div>
                    </div>

                    {{-- Tableau du Panthéon --}}
                    <div class="rounded-2xl bg-[#0e0e18] border border-white/10 overflow-hidden">
                        <div class="grid grid-cols-12 gap-3 p-3.5 bg-black/40 text-[11px] font-black uppercase tracking-wider text-mist border-b border-white/5">
                            <span class="col-span-2 sm:col-span-1 text-center">Rang</span>
                            <span class="col-span-6 sm:col-span-5">Disciple</span>
                            <span class="hidden sm:block sm:col-span-3">Royaume Martial</span>
                            <span class="col-span-4 sm:col-span-3 text-right">Chapitres / Qi</span>
                        </div>

                        <div class="divide-y divide-white/5">
                            @foreach($leaderboard['all'] as $rankNum => $cultivator)
                                @php
                                    $isMe = ($cultivator['id'] === $user->id);
                                @endphp
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
                                        <div class="w-8 h-8 rounded-full bg-panel border border-white/10 overflow-hidden flex-shrink-0 flex items-center justify-center text-xs font-bold text-white">
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
                                            <span class="text-[10px] text-mist block sm:hidden">
                                                {{ $cultivator['murim_badge'] }} {{ $cultivator['murim_title'] }}
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
            </div>

            {{-- ── Panel : Ma Bibliothèque ── --}}
            <div class="profile-tab-panel" :class="currentTab === 'library' ? 'active' : ''">
                {{-- Bannière Mode Rattrapage --}}
                <div style="background: linear-gradient(135deg, rgba(220,38,38,0.16) 0%, rgba(20,20,32,0.95) 100%); border: 1px solid rgba(220,38,38,0.35); border-radius: 14px; padding: 16px 20px; margin-bottom: 22px; display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; box-shadow: 0 8px 24px rgba(0,0,0,0.4);">
                    <div style="display: flex; align-items: center; gap: 14px; min-width: 260px; flex: 1;">
                        <div style="width: 42px; height: 42px; border-radius: 12px; background: rgba(220,38,38,0.2); border: 1px solid rgba(220,38,38,0.45); display: flex; align-items: center; justify-content: center; color: #f87171; flex-shrink: 0; box-shadow: 0 0 15px rgba(220,38,38,0.25);">
                            <svg style="width: 22px; height: 22px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        </div>
                        <div>
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <span style="font-weight: 800; font-size: 15px; color: #ffffff;">Mode Rattrapage</span>
                                <span style="font-size: 10px; font-weight: 800; padding: 2px 7px; border-radius: 6px; background: #dc2626; color: #ffffff; text-transform: uppercase;">Nouveau</span>
                            </div>
                            <div style="font-size: 12px; color: #a0a6b8; margin-top: 2px;">
                                Retrouvez en un clin d'œil tous les chapitres non lus de vos séries en cours, triés par date de sortie !
                            </div>
                        </div>
                    </div>
                    <a href="{{ route('library', ['tab' => 'rattrapage']) }}" style="display: inline-flex; align-items: center; gap: 8px; background: #dc2626; color: #ffffff; padding: 10px 18px; border-radius: 10px; font-size: 13px; font-weight: 700; text-decoration: none; box-shadow: 0 4px 16px rgba(220,38,38,0.35); transition: all 0.2s;" onmouseover="this.style.background='#ef4444'; this.style.transform='translateY(-1px)'" onmouseout="this.style.background='#dc2626'; this.style.transform='none'">
                        Accéder au Rattrapage
                        <svg style="width: 14px; height: 14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>

                {{-- Sous-onglets --}}
                <div style="display: flex; gap: 10px; margin-bottom: 24px; flex-wrap: wrap;">
                    <button type="button" class="subtab-pill" :class="libTab === 'favorites' ? 'active' : ''" @click="libTab = 'favorites'">
                        Mes Favoris (<span x-text="favorites.length">0</span>)
                    </button>
                    <button type="button" class="subtab-pill" :class="libTab === 'history' ? 'active' : ''" @click="libTab = 'history'">
                        Historique de lecture (<span x-text="history.length">0</span>)
                    </button>
                    <a href="{{ route('library', ['tab' => 'rattrapage']) }}" class="subtab-pill" style="text-decoration: none; display: inline-flex; align-items: center; gap: 6px; color: #f87171; border-color: rgba(220,38,38,0.35); background: rgba(220,38,38,0.1);">
                        <svg style="width: 13px; height: 13px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        ⚡ Mode Rattrapage
                    </a>
                </div>

                {{-- Liste Favoris --}}
                <div x-show="libTab === 'favorites'">
                    <template x-if="favorites.length === 0">
                        <div class="empty-box">
                            <span style="font-size: 36px; display: block; margin-bottom: 12px;">🔖</span>
                            <p style="font-size: 14px; margin: 0; font-weight: 600; color: #e8e8f0;">Votre liste de favoris est vide.</p>
                            <p style="font-size: 13px; color: #7070a0; margin-top: 4px;">Cliquez sur le bouton favori sur la fiche d'une série pour l'ajouter ici.</p>
                        </div>
                    </template>

                    <div class="profile-lib-grid" x-show="favorites.length > 0">
                        <template x-for="fav in favorites" :key="fav.slug">
                            <div class="profile-manga-card">
                                <div class="profile-manga-cover-wrap">
                                    <template x-if="fav.cover">
                                        <img :src="fav.cover" class="profile-manga-cover" alt="" onerror="this.style.display='none'; if(this.nextElementSibling) this.nextElementSibling.style.display='flex'">
                                    </template>
                                    <div class="profile-resume-placeholder" :style="fav.cover ? 'display: none;' : 'display: flex;'">
                                        <svg style="width: 40px; height: 40px; color: #505068; stroke: currentColor; fill: none;" viewBox="0 0 24 24" stroke-width="1.5"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg>
                                    </div>
                                </div>
                                <div class="profile-manga-info">
                                    <span class="profile-manga-title" x-text="fav.title"></span>
                                    <span class="profile-manga-meta" x-text="'Ajouté le ' + new Date(fav.addedAt).toLocaleDateString('fr-FR')"></span>
                                    
                                    {{-- Progression de lecture --}}
                                    <template x-if="serverProgress[fav.slug]">
                                        <div style="margin-top: 8px;">
                                            <div style="display: flex; justify-content: space-between; font-size: 10px; color: #a0a6b8; margin-bottom: 3px; font-weight: 500;">
                                                <span x-text="serverProgress[fav.slug].read_count + '/' + serverProgress[fav.slug].total_chapters + ' lus'"></span>
                                                <span style="color: #ffffff; font-weight: 700;" x-text="serverProgress[fav.slug].percent + '%'"></span>
                                            </div>
                                            <div style="width: 100%; height: 4px; background: #1b1c28; border-radius: 999px; overflow: hidden; border: 1px solid rgba(255,255,255,0.05);">
                                                <div style="height: 100%; background: #dc2626; border-radius: 999px; transition: width 0.3s ease;" :style="'width: ' + serverProgress[fav.slug].percent + '%'"></div>
                                            </div>
                                        </div>
                                    </template>

                                    <div style="display: flex; gap: 8px; margin-top: 10px;">
                                        <template x-if="serverProgress[fav.slug] && serverProgress[fav.slug].next_chapter">
                                            <a :href="serverProgress[fav.slug].next_chapter.url" class="profile-manga-btn" style="flex: 1; background: #dc2626; color: #ffffff; font-weight: 700;">
                                                Reprendre
                                            </a>
                                        </template>
                                        <template x-if="!serverProgress[fav.slug] || !serverProgress[fav.slug].next_chapter">
                                            <a :href="'/manga/' + fav.slug" class="profile-manga-btn" style="flex: 1;">Voir la série</a>
                                        </template>
                                        <button type="button" @click="removeFavorite(fav.slug)" style="background: rgba(220,38,38,.15); border: 1px solid rgba(220,38,38,.3); color: #f87171; border-radius: 6px; padding: 0 10px; cursor: pointer;" title="Retirer des favoris">✕</button>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- Liste Historique --}}
                <div x-show="libTab === 'history'">
                    <template x-if="history.length === 0">
                        <div class="empty-box">
                            <span style="font-size: 36px; display: block; margin-bottom: 12px;">📖</span>
                            <p style="font-size: 14px; margin: 0; font-weight: 600; color: #e8e8f0;">Aucun historique de lecture enregistré.</p>
                            <p style="font-size: 13px; color: #7070a0; margin-top: 4px;">Les chapitres que vous lisez s'afficheront automatiquement ici.</p>
                        </div>
                    </template>

                    <div class="profile-lib-grid" x-show="history.length > 0">
                        <template x-for="item in history" :key="item.mangaSlug + item.chapterSlug">
                            <div class="profile-manga-card">
                                <div class="profile-manga-cover-wrap">
                                    <template x-if="item.cover">
                                        <img :src="item.cover" class="profile-manga-cover" alt="" onerror="this.style.display='none'; if(this.nextElementSibling) this.nextElementSibling.style.display='flex'">
                                    </template>
                                    <div class="profile-resume-placeholder" :style="item.cover ? 'display: none;' : 'display: flex;'">
                                        <svg style="width: 40px; height: 40px; color: #505068; stroke: currentColor; fill: none;" viewBox="0 0 24 24" stroke-width="1.5"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg>
                                    </div>
                                </div>
                                <div class="profile-manga-info">
                                    <span class="profile-manga-title" x-text="item.mangaTitle"></span>
                                    <span class="profile-manga-meta" style="color: #dc2626; font-weight: 600;" x-text="item.chapterTitle"></span>
                                    <span class="profile-manga-meta" x-text="new Date(item.readAt).toLocaleDateString('fr-FR')"></span>
                                    <a :href="'/lecture/' + item.mangaSlug + '/' + item.chapterSlug" class="profile-manga-btn" style="margin-top: 10px;">Lire la suite →</a>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            {{-- ── Panel 3 : Modifier mon profil ── --}}
            <div class="profile-tab-panel" :class="currentTab === 'edit' ? 'active' : ''">
                <div class="profile-card-panel">
                    <div style="margin-bottom: 28px;">
                        <h2 style="font-size: 20px; font-weight: bold; color: #ffffff; display: flex; align-items: center; gap: 10px;">
                            <svg style="width: 20px; height: 20px; stroke: #dc2626; fill: none;" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>
                            Modifier mon profil
                        </h2>
                        <p style="font-size: 14px; color: #7070a0; margin-top: 4px;">Personnalisez votre avatar, votre bannière et vos préférences de lecture.</p>
                    </div>

                    <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data">
                        @csrf
                        @method('patch')
                        <input type="hidden" name="avatar_data" :value="avatarData">
                        <input type="hidden" name="banner_data" :value="bannerData">

                        {{-- Section PHOTO DE PROFIL --}}
                        <div style="margin-bottom: 24px;">
                            <span style="font-size: 12px; font-weight: 700; color: #7070a0; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 12px; display: block;">Photo de profil</span>
                            <div class="avatar-edit-box">
                                <div class="avatar-prev-circle">
                                    <template x-if="!avatarPreview">
                                        <span style="font-size: 26px; font-weight: bold; color: #6880c0;">{{ $initials }}</span>
                                    </template>
                                    <template x-if="avatarPreview">
                                        <img :src="avatarPreview" style="width: 100%; height: 100%; object-fit: cover; display: block;" alt="Aperçu Avatar">
                                    </template>
                                </div>
                                <div style="flex: 1; display: flex; flex-direction: column; gap: 10px; width: 100%;">
                                    <label class="btn-upload-file">
                                        <svg viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                                        Importer une photo (PNG, JPG, WEBP)
                                        <input type="file" name="avatar_file" accept="image/png, image/jpeg, image/webp" style="display:none;" @change="handleAvatar($event)">
                                    </label>
                                    <input type="text" name="avatar_url" class="input-dark" placeholder="Ou collez un lien URL d'image direct (https://...)" @input="if ($el.value.startsWith('http')) { avatarPreview = $el.value; }">
                                </div>
                            </div>
                        </div>

                        <div class="line-divider"></div>

                        {{-- Section BANNIÈRE DE PROFIL --}}
                        <div style="margin-bottom: 24px;">
                            <span style="font-size: 12px; font-weight: 700; color: #7070a0; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 12px; display: block;">Bannière de profil</span>
                            <div class="banner-prev-rect">
                                <template x-if="!bannerPreview">
                                    <svg class="profile-hero-svg" viewBox="0 0 1200 220" preserveAspectRatio="xMidYMid slice" xmlns="http://www.w3.org/2000/svg">
                                        <rect width="1200" height="220" fill="url(#spaceGradApp)" />
                                        <rect width="1200" height="220" fill="url(#redGlowApp)" />
                                        <circle cx="50" cy="30" r="1.2" fill="#ffffff" opacity="0.8" />
                                        <circle cx="210" cy="35" r="1.4" fill="#ffffff" opacity="0.9" />
                                        <circle cx="430" cy="30" r="1.1" fill="#ffffff" opacity="0.7" />
                                        <circle cx="670" cy="45" r="1.5" fill="#ffffff" opacity="0.8" />
                                        <circle cx="920" cy="40" r="1.3" fill="#ffffff" opacity="0.9" />
                                        <circle cx="240" cy="70" r="18" fill="url(#smallPlanetGradApp)" />
                                        <circle cx="640" cy="105" r="54" fill="url(#moonGradApp)" />
                                    </svg>
                                </template>
                                <template x-if="bannerPreview">
                                    <img :src="bannerPreview" style="width: 100%; height: 100%; object-fit: cover; display: block;" alt="Aperçu Bannière">
                                </template>
                            </div>
                            <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                                <label class="btn-upload-file">
                                    <svg viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                                    Téléverser une bannière
                                    <input type="file" name="banner_file" accept="image/*" style="display:none;" @change="handleBanner($event)">
                                </label>
                                <input type="text" name="banner_url" class="input-dark" style="flex: 1;" placeholder="Ou lien URL direct (https://...)" @input="if ($el.value.startsWith('http')) { bannerPreview = $el.value; }">
                            </div>
                        </div>

                        <div class="line-divider"></div>

                        {{-- Pseudo & Email --}}
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 20px; margin-bottom: 20px;">
                            <div>
                                <label style="font-size: 13px; color: #7070a0; display: block; margin-bottom: 6px;">Pseudo</label>
                                <input type="text" name="name" value="{{ old('name', $user->name) }}" required class="input-dark">
                            </div>
                            @if(!$user->pass_code)
                                <div>
                                    <label style="font-size: 13px; color: #7070a0; display: block; margin-bottom: 6px;">Adresse Email Staff</label>
                                    <input type="email" name="email" value="{{ old('email', $user->email) }}" required class="input-dark">
                                </div>
                            @endif
                        </div>

                        {{-- Bio & Préférences --}}
                        <div style="margin-bottom: 20px;">
                            <label style="font-size: 13px; color: #7070a0; display: block; margin-bottom: 6px;">Biographie / À propos</label>
                            <textarea name="bio" rows="3" class="input-dark" style="resize: vertical;" placeholder="Quelques mots sur vous ou vos goûts manga...">{{ old('bio', $user->bio) }}</textarea>
                        </div>

                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 20px; margin-bottom: 24px;">
                            <div>
                                <label style="font-size: 13px; color: #7070a0; display: block; margin-bottom: 6px;">Genre favori</label>
                                <select name="favorite_genre" class="input-dark">
                                    <option value="">— Aucun sélectionné —</option>
                                    @foreach($genres as $genre)
                                        <option value="{{ $genre->name }}" {{ old('favorite_genre', $user->favorite_genre) === $genre->name ? 'selected' : '' }}>
                                            {{ $genre->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label style="font-size: 13px; color: #7070a0; display: block; margin-bottom: 6px;">Mode de lecture par défaut</label>
                                <select name="reader_mode" class="input-dark">
                                    <option value="vertical" {{ old('reader_mode', $user->reader_mode) === 'vertical' ? 'selected' : '' }}>Cascade verticale (Webtoon)</option>
                                    <option value="single" {{ old('reader_mode', $user->reader_mode) === 'single' ? 'selected' : '' }}>Page par page (Manga traditionnel)</option>
                                </select>
                            </div>
                        </div>

                        {{-- Actions --}}
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 28px; flex-wrap: wrap; gap: 14px;">
                            <div>
                                @if (session('status') === 'profile-updated')
                                    <span style="font-size: 14px; color: #10b981; font-weight: 600;">✓ Modifications enregistrées avec succès !</span>
                                @endif
                            </div>
                            <button type="submit" class="btn-save-red">Enregistrer les modifications</button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- ── Panel 4 : Paramètres & Sécurité ── --}}
            <div class="profile-tab-panel" :class="currentTab === 'settings' ? 'active' : ''">
                <div class="profile-card-panel">
                    <div style="margin-bottom: 28px;">
                        <h2 style="font-size: 20px; font-weight: bold; color: #ffffff; display: flex; align-items: center; gap: 10px;">
                            <svg style="width: 20px; height: 20px; stroke: #dc2626; fill: none;" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
                            Paramètres & Sécurité
                        </h2>
                        <p style="font-size: 14px; color: #7070a0; margin-top: 4px;">
                            @if($user->pass_code)
                                Gérez votre Pass Secret et la sécurité de votre espace de lecture anonyme.
                            @else
                                Gérez la sécurité de votre compte et votre mot de passe.
                            @endif
                        </p>
                    </div>

                    @if($user->pass_code)
                        @include('profile.partials.pass-secret-manager')
                    @else
                        @include('profile.partials.update-password-form')
                    @endif

                    <div class="line-divider"></div>

                    @include('profile.partials.delete-user-form')
                </div>
            </div>

        </div>

        {{-- ═══════════════════════════════════════════
             MODALE DE SÉLECTION D'ŒUVRE (TOP 5 & TIER LIST)
             ═══════════════════════════════════════════ --}}
        <div 
            x-show="selectorOpen" 
            x-transition.opacity
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-md"
            style="display: none;"
            @keydown.escape.window="closeSelector()"
        >
            <div 
                class="w-full max-w-2xl bg-[#0f0f18] border border-white/10 rounded-2xl shadow-2xl overflow-hidden flex flex-col max-h-[85vh]"
                @click.outside="closeSelector()"
            >
                {{-- En-tête Modal --}}
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

                {{-- Barre de recherche --}}
                <div class="p-4 border-b border-white/5 bg-ink-deep/50">
                    <div class="relative">
                        <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-mist" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        <input 
                            type="text" 
                            x-model="selectorSearch" 
                            placeholder="Rechercher une série par son titre..." 
                            class="w-full pl-10 pr-4 py-2.5 rounded-xl bg-panel border border-white/10 text-white placeholder-mist/60 text-xs focus:outline-none focus:border-crimson"
                            x-ref="searchInput"
                        >
                    </div>
                </div>

                {{-- Grille des Séries --}}
                <div class="p-4 overflow-y-auto flex-1 grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
                    <template x-for="manga in filteredMangas" :key="manga.id">
                        <div 
                            @click="selectMangaForTarget(manga)"
                            class="group rounded-xl overflow-hidden border border-white/10 bg-panel hover:border-amber-400 hover:scale-[1.02] transition cursor-pointer p-2 flex flex-col items-center text-center shadow-md"
                        >
                            <div class="w-full aspect-[2/3] rounded-lg overflow-hidden bg-black/40 mb-2">
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

        {{-- Toast Notification --}}
        <div 
            x-show="toastVisible" 
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 translate-y-4"
            class="fixed bottom-6 right-6 z-50 px-4 py-3 rounded-xl bg-panel border border-amber-400/50 text-white text-xs font-bold shadow-2xl flex items-center gap-2.5 backdrop-blur-md"
            style="display: none;"
        >
            <span class="text-amber-400 text-sm">✓</span>
            <span x-text="toastMessage"></span>
        </div>

    </div>
</x-layouts.public>