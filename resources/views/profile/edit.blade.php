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
                currentTab: '{{ in_array(request('tab'), ['overview', 'library', 'edit', 'settings']) ? request('tab') : ((session('status') === 'password-updated' || $errors->updatePassword->isNotEmpty()) ? 'settings' : 'overview') }}',
                avatarPreview: '{{ $user->avatar ? $user->avatar_url : '' }}',
                bannerPreview: '{{ $user->banner ? $user->banner_url : '' }}',
                favorites: [],
                history: [],
                serverProgress: {},
                libTab: 'favorites',
                mangasLookup: @json($mangasLookup ?? []),
                init() {
                    this.loadData();
                    this.fetchProgress();
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
                    }
                },
                handleBanner(event) {
                    const file = event.target.files[0];
                    if (file) {
                        this.bannerPreview = URL.createObjectURL(file);
                    }
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
                        </div>
                        <div class="profile-meta-info">
                            Membre depuis {{ $user->created_at->translatedFormat('F Y') }}
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
             BLOC 2 — BARRE D'ONGLETS (Moderne & Pleine Page)
             ═══════════════════════════════════════════ --}}
        <div class="profile-tabs-bar">
            <button class="profile-tab-item" :class="currentTab === 'overview' ? 'active' : ''" @click="switchTab('overview')">
                <svg viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                Aperçu
            </button>
            <button class="profile-tab-item" :class="currentTab === 'library' ? 'active' : ''" @click="switchTab('library')">
                <svg viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg>
                Ma Bibliothèque (<span x-text="favorites.length">0</span>)
            </button>
            <button class="profile-tab-item" :class="currentTab === 'edit' ? 'active' : ''" @click="switchTab('edit')">
                <svg viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>
                Modifier mon profil
            </button>
            <button class="profile-tab-item" :class="currentTab === 'settings' ? 'active' : ''" @click="switchTab('settings')">
                <svg viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
                Paramètres & Sécurité
            </button>
        </div>

        {{-- ═══════════════════════════════════════════
             BLOC 3 — CONTENU PRINCIPAL
             ═══════════════════════════════════════════ --}}
        <div class="profile-content-section">

            {{-- ── Panel 1 : Aperçu ── --}}
            <div class="profile-tab-panel" :class="currentTab === 'overview' ? 'active' : ''">
                {{-- Grille Statistiques --}}
                <div class="profile-stats-grid">
                    <div class="profile-stat-box">
                        <span class="profile-stat-num" x-text="history.length">0</span>
                        <span class="profile-stat-label">Chapitres lus</span>
                    </div>
                    <div class="profile-stat-box">
                        <span class="profile-stat-num" x-text="favorites.length">0</span>
                        <span class="profile-stat-label">Favoris</span>
                    </div>
                    <div class="profile-stat-box">
                        <span class="profile-stat-num">{{ $commentsCount }}</span>
                        <span class="profile-stat-label">Commentaires</span>
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

            {{-- ── Panel 2 : Ma Bibliothèque ── --}}
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
                            <div>
                                <label style="font-size: 13px; color: #7070a0; display: block; margin-bottom: 6px;">Adresse Email</label>
                                <input type="email" name="email" value="{{ old('email', $user->email) }}" required class="input-dark">
                            </div>
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
                        <p style="font-size: 14px; color: #7070a0; margin-top: 4px;">Gérez la sécurité de votre compte et votre mot de passe.</p>
                    </div>

                    @include('profile.partials.update-password-form')

                    <div class="line-divider"></div>

                    @include('profile.partials.delete-user-form')
                </div>
            </div>

        </div>

    </div>
</x-layouts.public>