@props(['title' => 'Hidden Scan', 'description' => null, 'hideNavbar' => false])

<!DOCTYPE html>
<html lang="fr" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="dark">
    <meta name="description" content="{{ $description ?? 'Hidden Scan — Lis tes mangas, manhwas et manhuas en ligne gratuitement et sans inscription.' }}">
    <meta name="theme-color" content="#14111f">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta property="og:title" content="{{ $title ?? 'Hidden Scan' }}">
    <meta property="og:description" content="{{ $description ?? 'Plateforme de lecture de mangas, manhwas et manhuas. Accès libre, sans compte.' }}">
    <meta property="og:type" content="website">
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    <title>{{ $title ?? 'Hidden Scan' }}</title>

    <script>
    // Système de données locales Hidden Scan
    window.HiddenScan = {
        _getData() {
            try { return JSON.parse(localStorage.getItem('hiddenscan') || '{}'); } catch(e) { return {}; }
        },
        _save(data) {
            try { localStorage.setItem('hiddenscan', JSON.stringify(data)); } catch(e) {}
        },
        toggleFavorite(slug, title, cover) {
            const data = this._getData();
            if (!data.favorites) data.favorites = {};
            const willBeFavorite = !data.favorites[slug];
            if (!willBeFavorite) {
                delete data.favorites[slug];
            } else {
                data.favorites[slug] = { slug, title, cover, addedAt: new Date().toISOString() };
            }
            this._save(data);

            // Sync with backend API
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            fetch('/api/favorites/toggle', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token || ''
                },
                body: JSON.stringify({ slug: slug })
            }).catch(() => {});

            return willBeFavorite;
        },
        syncFavorites() {
            const data = this._getData();
            const slugs = Object.keys(data.favorites || {});
            if (slugs.length === 0) return;
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            fetch('/api/favorites/sync', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token || ''
                },
                body: JSON.stringify({ slugs })
            }).catch(() => {});
        },
        isFavorite(slug) {
            const data = this._getData();
            return !!(data.favorites && data.favorites[slug]);
        },
        getFavorites() {
            const data = this._getData();
            return Object.values(data.favorites || {});
        },
        getHistory() {
            const data = this._getData();
            return data.history || [];
        },
        getProgress() {
            const data = this._getData();
            return data.progress || {};
        },
        getProgressForManga(slug) {
            const progress = this.getProgress();
            return progress[slug] || null;
        },
        exportData() {
            const data = localStorage.getItem('hiddenscan') || '{}';
            const blob = new Blob([data], { type: 'application/json' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'hiddenscan-bibliotheque.json';
            a.click();
            URL.revokeObjectURL(url);
        },
        importData(file) {
            return new Promise((resolve, reject) => {
                const reader = new FileReader();
                reader.onload = (e) => {
                    try {
                        const data = JSON.parse(e.target.result);
                        localStorage.setItem('hiddenscan', JSON.stringify(data));
                        resolve(data);
                    } catch(err) { reject(err); }
                };
                reader.onerror = reject;
                reader.readAsText(file);
            });
        },
    };

    document.addEventListener('DOMContentLoaded', () => {
        window.HiddenScan.syncFavorites();
    });

    // ═══ Alpine Store : Notifications des chapitres favoris ═══
    function setupNotificationsStore() {
        if (!window.Alpine) return;
        try {
            if (typeof Alpine.store === 'function' && Alpine.store('notifications')) return;
        } catch(e) {}

        try {
            Alpine.store('notifications', {
                open: false,
                unreadCount: 0,
                notifications: [],

                async init() {
                    @auth
                    try {
                        await this.fetchNotifications();
                    } catch(e) {}
                    setInterval(() => {
                        try { this.fetchNotifications(); } catch(e) {}
                    }, 45000);
                    @endauth
                },

                toggle() {
                    this.open = !this.open;
                    if (this.open) {
                        try { this.fetchNotifications(); } catch(e) {}
                    }
                },

                async fetchNotifications() {
                    try {
                        const res = await fetch('/api/notifications');
                        if (!res.ok) return;
                        const data = await res.json();
                        if (data && data.success) {
                            const oldCount = this.unreadCount;
                            this.unreadCount = data.unread_count || 0;
                            this.notifications = data.notifications || [];

                            // Alerte navigateur si nouveau chapitre détecté (vérification sécurisée de Notification)
                            if (oldCount !== null && data.unread_count > oldCount && oldCount > 0) {
                                try {
                                    if (typeof window !== 'undefined' && 'Notification' in window && typeof Notification.permission !== 'undefined' && Notification.permission === 'granted') {
                                        const latest = this.notifications.find(n => !n.is_read);
                                        if (latest) {
                                            new Notification(latest.title, {
                                                body: latest.message,
                                                icon: latest.manga_cover || '/images/logo.png',
                                            });
                                        }
                                    }
                                } catch(e) {}
                            }
                        }
                    } catch (e) {}
                },

                async markAsRead(id) {
                    try {
                        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                        await fetch(`/api/notifications/${id}/read`, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token || '' }
                        });
                        const notif = this.notifications.find(n => n.id === id);
                        if (notif && !notif.is_read) {
                            notif.is_read = true;
                            this.unreadCount = Math.max(0, this.unreadCount - 1);
                        }
                    } catch (e) {}
                },

                async markAllAsRead() {
                    try {
                        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                        await fetch('/api/notifications/mark-all-read', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token || '' }
                        });
                        this.unreadCount = 0;
                        this.notifications.forEach(n => n.is_read = true);
                    } catch (e) {}
                },

                handleClick(item) {
                    try { this.markAsRead(item.id); } catch(e) {}
                    if (item.url) {
                        window.location.href = item.url;
                    }
                }
            });
        } catch(e) {
            console.warn('Could not register notifications store:', e);
        }
    }

    document.addEventListener('alpine:init', setupNotificationsStore);
    document.addEventListener('livewire:init', setupNotificationsStore);
    document.addEventListener('DOMContentLoaded', setupNotificationsStore);
    if (window.Alpine) {
        setupNotificationsStore();
    }
    </script>

    {{-- Swiper --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles

    <style>
        .site-watermark-bg {
            position: fixed;
            inset: 0;
            pointer-events: none;
            user-select: none;
            z-index: 0;
            overflow: hidden;
        }
        .watermark-star {
            position: absolute;
            background-image: url('{{ asset("images/filgrane.png") }}');
            background-repeat: no-repeat;
            background-size: contain;
            background-position: center;
        }
        .watermark-star-center {
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 620px;
            height: 880px;
            max-width: 90vw;
            max-height: 85vh;
            opacity: 0.055;
            filter: drop-shadow(0 0 60px rgba(220, 38, 38, 0.15));
        }
        .watermark-star-primary {
            top: 4%;
            right: -6%;
            width: 650px;
            height: 920px;
            opacity: 0.045;
            transform: rotate(6deg);
            filter: drop-shadow(0 0 50px rgba(220, 38, 38, 0.18));
        }
        .watermark-star-secondary {
            bottom: -8%;
            left: -5%;
            width: 520px;
            height: 750px;
            opacity: 0.035;
            transform: rotate(-10deg);
            filter: drop-shadow(0 0 40px rgba(91, 110, 245, 0.18));
        }
        @media (max-width: 768px) {
            .watermark-star-center {
                width: 360px;
                height: 520px;
                opacity: 0.045;
            }
            .watermark-star-primary {
                top: 2%;
                right: -25%;
                width: 320px;
                height: 480px;
                opacity: 0.03;
            }
            .watermark-star-secondary {
                display: none;
            }
        }

        /* ── Widget Discord Flottant & Scroll to top ── */
        .discord-float-btn {
            position: fixed !important;
            bottom: calc(16px + env(safe-area-inset-bottom, 0px)) !important;
            left: calc(16px + env(safe-area-inset-left, 0px)) !important;
            z-index: 40 !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            background: linear-gradient(135deg, #5865F2, #4752C4) !important;
            color: #ffffff !important;
            box-shadow: 0 4px 20px rgba(88, 101, 242, 0.45), 0 2px 8px rgba(0, 0, 0, 0.4) !important;
            border: 1px solid rgba(255, 255, 255, 0.22) !important;
            border-radius: 9999px !important;
            text-decoration: none !important;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1) !important;
            cursor: pointer !important;
        }
        .discord-float-btn:hover {
            background: linear-gradient(135deg, #4752C4, #3b43a0) !important;
            box-shadow: 0 6px 26px rgba(88, 101, 242, 0.6), 0 4px 12px rgba(0, 0, 0, 0.45) !important;
            transform: translateY(-2px) !important;
        }
        .discord-float-btn:active {
            transform: scale(0.94) !important;
        }

        /* Mobile layout: Masqué sur mobile pour ne jamais obstruer la lecture (accessible dans le menu et le footer) */
        @media (max-width: 639px) {
            .discord-float-btn {
                display: none !important;
            }
        }

        /* Desktop layout: pill élégante avec texte */
        @media (min-width: 640px) {
            .discord-float-btn {
                bottom: 24px !important;
                left: 24px !important;
                height: 44px !important;
                padding: 0 16px 0 13px !important;
                gap: 10px !important;
            }
            .discord-float-btn svg {
                width: 22px !important;
                height: 22px !important;
                flex-shrink: 0 !important;
            }
            .discord-float-btn .discord-label {
                display: inline-block !important;
                font-size: 13px !important;
                font-weight: 700 !important;
                letter-spacing: -0.01em !important;
                white-space: nowrap !important;
                color: #ffffff !important;
            }
        }

        /* Harmonisation Scroll-to-top */
        .scroll-top-btn {
            position: fixed !important;
            bottom: calc(14px + env(safe-area-inset-bottom, 0px)) !important;
            right: calc(14px + env(safe-area-inset-right, 0px)) !important;
            z-index: 40 !important;
            width: 44px !important;
            height: 44px !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            border-radius: 9999px !important;
            box-shadow: 0 4px 18px rgba(0, 0, 0, 0.45) !important;
        }
        @media (min-width: 640px) {
            .scroll-top-btn {
                bottom: 24px !important;
                right: 24px !important;
                width: 44px !important;
                height: 44px !important;
            }
        }
    </style>
</head>

<body class="bg-ink text-chalk min-h-screen font-sans antialiased flex flex-col relative overflow-x-hidden">

    {{-- ═══ Filigrane Céleste en arrière-plan global ═══ --}}
    <div class="site-watermark-bg" aria-hidden="true">
        <div class="watermark-star watermark-star-center"></div>
        <div class="watermark-star watermark-star-primary"></div>
        <div class="watermark-star watermark-star-secondary"></div>
    </div>

    @unless($hideNavbar)
    {{-- ═══ Navbar ═══ --}}
    <nav class="fixed top-0 left-0 right-0 z-50 bg-ink/70 backdrop-blur-xl border-b border-line/40 shadow-[0_4px_30px_rgba(0,0,0,0.5)] transition-transform duration-300" 
         x-data="{ 
             mobileOpen: false, 
             searchOpen: false, 
             userMobileMenuOpen: false,
             notifMobileOpen: false,
             navHidden: false, 
             lastScrollY: 0,

             toggleSearch() {
                 this.searchOpen = !this.searchOpen;
                 if (this.searchOpen) {
                     this.mobileOpen = false;
                     this.notifMobileOpen = false;
                     this.userMobileMenuOpen = false;
                 }
             },

             toggleMobileMenu() {
                 this.mobileOpen = !this.mobileOpen;
                 if (this.mobileOpen) {
                     this.searchOpen = false;
                     this.notifMobileOpen = false;
                     this.userMobileMenuOpen = false;
                 }
             },

             toggleUserMobileMenu() {
                 this.userMobileMenuOpen = !this.userMobileMenuOpen;
                 if (this.userMobileMenuOpen) {
                     this.mobileOpen = false;
                     this.searchOpen = false;
                     this.notifMobileOpen = false;
                 }
             },

             toggleNotifMobile() {
                 this.notifMobileOpen = !this.notifMobileOpen;
                 if (this.notifMobileOpen) {
                     this.mobileOpen = false;
                     this.searchOpen = false;
                     this.userMobileMenuOpen = false;
                     try {
                         if (window.Alpine && Alpine.store && Alpine.store('notifications')) {
                             Alpine.store('notifications').fetchNotifications();
                         }
                     } catch(e) {}
                 }
             },

             closeAll() {
                 this.mobileOpen = false;
                 this.searchOpen = false;
                 this.notifMobileOpen = false;
                 this.userMobileMenuOpen = false;
             },

             handleNotifClick(item) {
                 try {
                     if (window.Alpine && Alpine.store && Alpine.store('notifications')) {
                         Alpine.store('notifications').handleClick(item);
                     }
                 } catch(e) {}
                 this.notifMobileOpen = false;
             },

             handleScroll() {
                 if (this.mobileOpen || this.searchOpen || this.notifMobileOpen || this.userMobileMenuOpen) {
                     this.navHidden = false;
                     return;
                 }
                 const cur = Math.max(0, window.scrollY);
                 const delta = cur - this.lastScrollY;
                 if (Math.abs(delta) > 12) {
                     this.navHidden = cur > 120 && delta > 0;
                     this.lastScrollY = cur;
                 }
             }
         }"
         @scroll.window="handleScroll()"
         @click.outside="closeAll()"
         :class="navHidden ? '-translate-y-full' : 'translate-y-0'">
        <div class="max-w-7xl mx-auto px-4 sm:px-6">
            <div class="flex items-center justify-between h-16">

                {{-- Logo --}}
                <a href="{{ route('home') }}" class="flex items-center gap-3 px-3 py-2 bg-panel-hi border border-line/50 rounded-xl hover:bg-panel-hover transition-all group shadow-sm">
                    <img src="{{ asset('images/logo.png') }}" alt="Hidden Scan Logo" class="h-8 w-8 rounded-md group-hover:scale-105 transition-transform duration-300">
                    <span class="font-display font-extrabold text-xl tracking-tight text-chalk hidden sm:block leading-none">
                        Hidden<span class="text-mist">Scan</span>
                    </span>
                </a>

                {{-- Navigation desktop --}}
                <div class="hidden md:flex items-center gap-1">
                    <a href="{{ route('home') }}"
                       class="px-4 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('home') ? 'text-violet bg-violet/10' : 'text-mist hover:text-chalk hover:bg-panel-hi/50' }}">
                        Accueil
                    </a>
                    <a href="{{ route('manga.index') }}"
                       class="px-4 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('manga.index') ? 'text-violet bg-violet/10' : 'text-mist hover:text-chalk hover:bg-panel-hi/50' }}">
                        Catalogue
                    </a>
                    <a href="{{ route('manga.random') }}"
                       onclick="if(window.HiddenScan && !{{ auth()->check() ? 'true' : 'false' }}){ const favs = window.HiddenScan.getFavorites().map(f => f.slug).join(','); if(favs) this.href = '{{ route('manga.random') }}?favs=' + encodeURIComponent(favs); }"
                       title="Lancer un manga aléatoire selon vos genres préférés"
                       class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-all flex items-center gap-1.5 bg-gradient-to-r from-red-600/15 via-red-500/10 to-amber-500/15 hover:from-red-600/25 hover:to-amber-500/25 text-red-400 hover:text-red-300 border border-red-500/25 hover:border-red-500/40 shadow-sm hover:scale-105 active:scale-95">
                        <span class="text-sm leading-none">🎲</span>
                        <span>Surprends-moi</span>
                    </a>
                    <a href="{{ route('library') }}"
                       class="px-4 py-2 rounded-lg text-sm font-medium transition-colors {{ request()->routeIs('library') ? 'text-violet bg-violet/10' : 'text-mist hover:text-chalk hover:bg-panel-hi/50' }}">
                        <span class="flex items-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                            Bibliothèque
                        </span>
                    </a>
                </div>

                {{-- Actions Desktop --}}
                <div class="hidden md:flex items-center gap-3">
                    {{-- Search --}}
                    <form action="{{ route('manga.index') }}" method="GET" class="relative">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-mist" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        <input
                            type="text"
                            name="q"
                            placeholder="Rechercher une série..."
                            class="input-field !pl-10 pr-4 py-2 w-56 text-sm !rounded-full !bg-ink-deep/50 !border-line/60 focus:!w-72 transition-all duration-300"
                        >
                    </form>
                    
                    {{-- Avatar Utilisateur avec Menu Déroulant & Notifications --}}
                    @auth
                        {{-- Centre de notifications (Desktop) --}}
                        <div class="relative" x-data="{ notifOpen: false }" @click.outside="notifOpen = false">
                            <button 
                                @click="notifOpen = !notifOpen; if(notifOpen && $store?.notifications) $store.notifications.fetchNotifications()" 
                                class="relative p-2.5 rounded-xl bg-[#141420] border border-[#222234] text-[#a0a0c0] hover:text-white hover:border-[#dc2626]/50 hover:bg-[#1a1a28] transition-all cursor-pointer flex items-center justify-center shadow-md focus:outline-none"
                                title="Notifications de vos séries favorites"
                                aria-label="Notifications"
                            >
                                <svg class="w-5 h-5 transition-transform active:scale-90" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                                </svg>

                                {{-- Badge non lu --}}
                                <template x-if="$store?.notifications && $store.notifications.unreadCount > 0">
                                    <span class="absolute -top-1 -right-1 flex h-4 min-w-[16px] px-1 items-center justify-center rounded-full bg-[#dc2626] text-[10px] font-extrabold text-white ring-2 ring-[#0e0e15] shadow-lg animate-pulse"
                                          x-text="$store.notifications.unreadCount > 99 ? '99+' : $store.notifications.unreadCount">
                                    </span>
                                </template>
                            </button>

                            {{-- Menu déroulant Notifications --}}
                            <div 
                                x-show="notifOpen" 
                                x-cloak
                                x-transition:enter="transition ease-out duration-150"
                                x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
                                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                                x-transition:leave="transition ease-in duration-100"
                                x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                                x-transition:leave-end="opacity-0 scale-95 -translate-y-1"
                                style="background-color: #12121c !important; border: 1px solid #262638 !important; box-shadow: 0 20px 50px rgba(0,0,0,0.85);"
                                class="absolute right-0 mt-2 w-80 sm:w-96 rounded-2xl z-50 overflow-hidden"
                            >
                                {{-- En-tête notifications --}}
                                <div class="p-3.5 border-b border-[#222234] flex items-center justify-between gap-2 bg-[#161624]">
                                    <div class="flex items-center gap-2">
                                        <span class="font-bold text-sm text-white">Notifications</span>
                                        <template x-if="$store?.notifications && $store.notifications.unreadCount > 0">
                                            <span class="text-[10px] font-extrabold px-1.5 py-0.5 rounded-full bg-red-600/30 text-red-400 border border-red-500/40"
                                                  x-text="$store.notifications.unreadCount + ' non lue(s)'"></span>
                                        </template>
                                    </div>
                                    <template x-if="$store?.notifications && $store.notifications.unreadCount > 0">
                                        <button 
                                            type="button" 
                                            @click="$store.notifications.markAllAsRead()"
                                            class="text-[11px] text-[#8ea1ff] hover:text-white transition font-medium cursor-pointer"
                                        >
                                            Tout marquer comme lu
                                        </button>
                                    </template>
                                </div>

                                {{-- Liste des notifications --}}
                                <div class="max-h-80 overflow-y-auto divide-y divide-[#1e1e2c]">
                                    <template x-if="!$store?.notifications || !$store.notifications.notifications || $store.notifications.notifications.length === 0">
                                        <div class="p-8 text-center">
                                            <div class="w-12 h-12 rounded-full bg-[#181826] border border-[#262638] flex items-center justify-center mx-auto mb-3 text-[#60608a]">
                                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                                            </div>
                                            <p class="text-xs font-bold text-white mb-1">Aucune nouvelle notification</p>
                                            <p class="text-[11px] text-[#7070a0] max-w-[220px] mx-auto leading-relaxed">
                                                Ajoutez des séries à vos favoris : dès qu'un chapitre sort, il apparaîtra directement ici !
                                            </p>
                                        </div>
                                    </template>

                                    <template x-for="item in ($store?.notifications?.notifications || [])" :key="item.id">
                                        <div 
                                            @click="$store.notifications.handleClick(item)"
                                            :class="item.is_read ? 'bg-[#12121c] opacity-80' : 'bg-[#181828] border-l-2 border-[#dc2626]'"
                                            class="p-3 flex items-start gap-3 hover:bg-[#1e1e30] transition cursor-pointer group"
                                        >
                                            {{-- Cover miniature --}}
                                            <div class="w-10 h-14 rounded-md overflow-hidden bg-[#1f1f2e] border border-white/10 flex-shrink-0">
                                                <template x-if="item.manga_cover">
                                                    <img :src="item.manga_cover" class="w-full h-full object-cover" alt="">
                                                </template>
                                                <template x-if="!item.manga_cover">
                                                    <div class="w-full h-full flex items-center justify-center text-xs text-mist font-bold">📖</div>
                                                </template>
                                            </div>

                                            {{-- Contenu --}}
                                            <div class="flex-1 min-w-0">
                                                <div class="flex items-center justify-between gap-1 mb-0.5">
                                                    <span class="text-xs font-bold text-white truncate group-hover:text-[#f87171] transition" x-text="item.manga_title"></span>
                                                    <span class="text-[10px] text-[#7070a0] flex-shrink-0" x-text="item.created_at_human"></span>
                                                </div>
                                                <p class="text-[11px] text-[#c0c0d8] leading-snug line-clamp-2">
                                                    <span class="font-semibold text-red-400" x-text="'Chapitre ' + item.chapter_number"></span>
                                                    <span x-text="item.chapter_title ? ' : ' + item.chapter_title : ' disponible'"></span>
                                                </p>
                                                <div class="mt-1.5 flex items-center gap-2">
                                                    <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-red-600/30 text-red-300 border border-red-500/30 group-hover:bg-red-600 group-hover:text-white transition">
                                                        Lire maintenant →
                                                    </span>
                                                    <template x-if="!item.is_read">
                                                        <span class="w-1.5 h-1.5 rounded-full bg-[#dc2626]"></span>
                                                    </template>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                </div>

                                {{-- Pied du menu --}}
                                <div class="p-2.5 bg-[#141422] border-t border-[#222234] flex items-center justify-between">
                                    <a href="{{ route('library', ['tab' => 'rattrapage']) }}" class="text-xs font-semibold text-[#f87171] hover:text-white transition flex items-center gap-1.5">
                                        <svg class="w-3.5 h-3.5 text-[#dc2626]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                        Mode Rattrapage complet
                                    </a>
                                    <a href="{{ route('library') }}" class="text-xs text-[#7070a0] hover:text-white transition">
                                        Bibliothèque
                                    </a>
                                </div>
                            </div>
                        </div>

                        {{-- Menu déroulant du profil utilisateur --}}
                        <div class="relative" x-data="{ userMenuOpen: false }" @click.outside="userMenuOpen = false">
                            <button 
                                @click="userMenuOpen = !userMenuOpen" 
                                class="w-10 h-10 rounded-full overflow-hidden ring-2 ring-[#dc2626]/70 hover:ring-[#dc2626] transition-all cursor-pointer flex items-center justify-center bg-[#111118] shadow-lg shadow-black/40 focus:outline-none"
                                title="{{ Auth::user()->name }}"
                            >
                                <img src="{{ Auth::user()->avatar_url }}" alt="{{ Auth::user()->name }}" class="w-full h-full object-cover">
                            </button>

                            {{-- Menu déroulant du profil (Solide 100% Opaque & Uniforme) --}}
                            <div 
                                x-show="userMenuOpen" 
                                x-cloak
                                x-transition:enter="transition ease-out duration-150"
                                x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
                                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                                x-transition:leave="transition ease-in duration-100"
                                x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                                x-transition:leave-end="opacity-0 scale-95 -translate-y-1"
                                style="background-color: #12121c !important; border: 1px solid #262638 !important; box-shadow: 0 16px 48px rgba(0,0,0,0.85);"
                                class="absolute right-0 mt-2 w-60 rounded-xl p-2 z-50 overflow-hidden"
                            >
                                {{-- En-tête profil --}}
                                <div style="border-bottom: 1px solid #222234; padding: 10px 12px 12px;">
                                    <div class="font-bold text-sm text-[#ffffff] truncate flex items-center justify-between gap-2">
                                        <span class="truncate">{{ Auth::user()->name }}</span>
                                        @if(Auth::user()->staff_badge)
                                            <span class="text-[10px] uppercase font-extrabold px-1.5 py-0.5 rounded border {{ Auth::user()->staff_badge['bg'] }} {{ Auth::user()->staff_badge['text'] }} {{ Auth::user()->staff_badge['border'] }}">
                                                {{ Auth::user()->staff_badge['name'] }}
                                            </span>
                                        @endif
                                    </div>
                                    <div class="text-xs text-[#7070a0] truncate mt-1">
                                        @if(Auth::user()->email && !str_contains(Auth::user()->email, '@anon.'))
                                            {{ Auth::user()->email }}
                                        @else
                                            <span class="inline-flex items-center gap-1.5 text-[#8080a8]">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                                Compte Anonyme
                                            </span>
                                        @endif
                                    </div>
                                </div>

                                {{-- Liens --}}
                                <div class="py-1.5 space-y-0.5">
                                    <a href="{{ route('profile.edit', ['tab' => 'overview']) }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-xs font-semibold text-[#c0c0d8] hover:text-white hover:bg-[#1a1a28] transition group">
                                        <svg class="w-4 h-4 text-[#dc2626] group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                        Mon Profil
                                    </a>

                                    <a href="{{ route('library', ['tab' => 'rattrapage']) }}" class="flex items-center justify-between px-3 py-2 rounded-lg text-xs font-semibold text-[#f87171] hover:text-white hover:bg-red-950/40 border border-red-500/20 transition group">
                                        <span class="flex items-center gap-3">
                                            <svg class="w-4 h-4 text-[#dc2626] group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                            Mode Rattrapage
                                        </span>
                                        <span class="text-[10px] px-1.5 py-0.5 rounded bg-red-600 text-white font-extrabold">NEW</span>
                                    </a>

                                    <a href="{{ route('library') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-xs font-semibold text-[#c0c0d8] hover:text-white hover:bg-[#1a1a28] transition group">
                                        <svg class="w-4 h-4 text-[#dc2626] group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                                        Ma Bibliothèque
                                    </a>

                                    <a href="{{ route('profile.edit', ['tab' => 'edit']) }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-xs font-semibold text-[#c0c0d8] hover:text-white hover:bg-[#1a1a28] transition group">
                                        <svg class="w-4 h-4 text-[#dc2626] group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        Modifier mon profil
                                    </a>

                                    <a href="{{ route('profile.edit', ['tab' => 'settings']) }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-xs font-semibold text-[#c0c0d8] hover:text-white hover:bg-[#1a1a28] transition group">
                                        <svg class="w-4 h-4 text-[#dc2626] group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                        Paramètres & Sécurité
                                    </a>

                                    @if(Auth::user()->canAccessPanel(filament()->getPanel('admin')))
                                        @php
                                            $staffBadge = Auth::user()->staff_badge;
                                            $admClass = match($staffBadge['color'] ?? '') {
                                                'red' => 'text-red-400 hover:bg-red-600/20 border-red-500/25',
                                                'purple' => 'text-purple-300 hover:bg-purple-600/20 border-purple-500/25',
                                                'sky' => 'text-sky-300 hover:bg-sky-500/20 border-sky-400/25',
                                                default => 'text-purple-300 hover:bg-purple-600/20 border-purple-500/25',
                                            };
                                            $iconColor = match($staffBadge['color'] ?? '') {
                                                'red' => 'text-red-400',
                                                'purple' => 'text-purple-400',
                                                'sky' => 'text-sky-400',
                                                default => 'text-purple-400',
                                            };
                                        @endphp
                                        <a href="/admin" target="_blank" class="flex items-center justify-between px-3 py-2 rounded-lg text-xs font-bold transition border {{ $admClass }}">
                                            <span class="flex items-center gap-3">
                                                <svg class="w-4 h-4 {{ $iconColor }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                                Administration
                                            </span>
                                            @if($staffBadge)
                                                <span class="text-[9px] uppercase tracking-wider font-extrabold px-1.5 py-0.5 rounded border {{ $staffBadge['bg'] }} {{ $staffBadge['text'] }} {{ $staffBadge['border'] }}">
                                                    {{ $staffBadge['name'] }}
                                                </span>
                                            @endif
                                        </a>
                                    @endif
                                </div>

                                {{-- Déconnexion --}}
                                <div style="border-top: 1px solid #222234; padding-top: 4px; margin-top: 2px;">
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit" class="w-full flex items-center gap-3 px-3 py-2 rounded-lg text-xs font-semibold text-[#f87171] hover:bg-red-500/10 hover:text-red-400 transition cursor-pointer">
                                            <svg class="w-4 h-4 text-[#f87171]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                                            Se déconnecter
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @else
                        <a href="{{ route('login') }}" class="btn-primary !py-2 !px-4 !text-sm">
                            Se connecter
                        </a>
                    @endauth
                </div>

                {{-- Boutons mobile --}}
                <div class="flex md:hidden items-center gap-1 sm:gap-2">
                    {{-- Bouton Recherche Mobile --}}
                    <button 
                        type="button"
                        @click.stop="toggleSearch()" 
                        class="p-2 sm:p-2.5 rounded-xl text-mist hover:text-chalk hover:bg-panel-hi active:scale-95 transition cursor-pointer flex items-center justify-center"
                        title="Recherche"
                        aria-label="Ouvrir la recherche"
                    >
                        <svg x-show="!searchOpen" class="w-5 h-5 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        <svg x-show="searchOpen" x-cloak class="w-5 h-5 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>

                    @auth
                        {{-- Bouton Cloche Notifications Mobile --}}
                        <button 
                            type="button"
                            @click.stop="toggleNotifMobile()" 
                            class="relative p-2 sm:p-2.5 rounded-xl text-[#a0a0c0] hover:text-white hover:bg-panel-hi active:scale-95 transition cursor-pointer flex items-center justify-center"
                            title="Notifications"
                            aria-label="Notifications"
                        >
                            <svg class="w-5 h-5 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                            </svg>
                            <template x-if="$store?.notifications && $store.notifications.unreadCount > 0">
                                <span class="absolute top-1 right-1 flex h-4 min-w-[16px] px-1 items-center justify-center rounded-full bg-[#dc2626] text-[9px] font-extrabold text-white ring-2 ring-[#0e0e15] pointer-events-none"
                                      x-text="$store.notifications.unreadCount > 99 ? '99+' : $store.notifications.unreadCount">
                                </span>
                            </template>
                        </button>

                        {{-- Avatar Utilisateur Mobile avec Menu Déroulant --}}
                        <div class="relative">
                            <button 
                                type="button"
                                @click.stop="toggleUserMobileMenu()" 
                                class="w-9 h-9 rounded-full overflow-hidden ring-2 ring-[#dc2626]/80 hover:ring-[#dc2626] active:scale-95 transition-all cursor-pointer flex items-center justify-center bg-[#111118] shadow-md shadow-black/40 focus:outline-none"
                                title="{{ Auth::user()->name }}"
                                aria-label="Menu profil utilisateur"
                            >
                                <img src="{{ Auth::user()->avatar_url }}" alt="{{ Auth::user()->name }}" class="w-full h-full object-cover pointer-events-none">
                            </button>

                            {{-- Menu Déroulant Profil & Navigation Mobile --}}
                            <div 
                                x-show="userMobileMenuOpen" 
                                x-cloak
                                @click.stop
                                x-transition:enter="transition ease-out duration-150"
                                x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
                                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                                x-transition:leave="transition ease-in duration-100"
                                x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                                x-transition:leave-end="opacity-0 scale-95 -translate-y-1"
                                style="background-color: #12121c !important; border: 1px solid #262638 !important; box-shadow: 0 16px 48px rgba(0,0,0,0.85);"
                                class="absolute right-0 mt-2.5 w-72 max-w-[calc(100vw-24px)] rounded-2xl p-2.5 z-50 overflow-hidden max-h-[85vh] overflow-y-auto"
                            >
                                {{-- En-tête profil --}}
                                <div style="border-bottom: 1px solid #222234; padding: 6px 8px 10px;">
                                    <div class="font-bold text-sm text-[#ffffff] truncate flex items-center justify-between gap-2">
                                        <span class="truncate">{{ Auth::user()->name }}</span>
                                        @if(Auth::user()->staff_badge)
                                            <span class="text-[10px] uppercase font-extrabold px-1.5 py-0.5 rounded border {{ Auth::user()->staff_badge['bg'] }} {{ Auth::user()->staff_badge['text'] }} {{ Auth::user()->staff_badge['border'] }}">
                                                {{ Auth::user()->staff_badge['name'] }}
                                            </span>
                                        @endif
                                    </div>
                                    <div class="text-xs text-[#7070a0] truncate mt-1">
                                        @if(Auth::user()->email && !str_contains(Auth::user()->email, '@anon.'))
                                            {{ Auth::user()->email }}
                                        @else
                                            <span class="inline-flex items-center gap-1.5 text-[#8080a8]">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                                Compte Anonyme
                                            </span>
                                        @endif
                                    </div>
                                </div>

                                {{-- Navigation Principale Mobile --}}
                                <div class="py-1.5 space-y-0.5 border-b border-[#222234]">
                                    <a href="{{ route('home') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-xs font-semibold {{ request()->routeIs('home') ? 'text-white bg-[#dc2626]' : 'text-[#c0c0d8] hover:text-white hover:bg-[#1a1a28]' }} transition group">
                                        <svg class="w-4 h-4 text-[#dc2626] group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                                        <span>Accueil</span>
                                    </a>

                                    <a href="{{ route('manga.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-xs font-semibold {{ request()->routeIs('manga.index') ? 'text-white bg-[#dc2626]' : 'text-[#c0c0d8] hover:text-white hover:bg-[#1a1a28]' }} transition group">
                                        <svg class="w-4 h-4 text-[#dc2626] group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                                        <span>Catalogue</span>
                                    </a>

                                    <a href="{{ route('manga.random') }}" class="flex items-center justify-between px-3 py-2 rounded-lg text-xs font-semibold text-amber-400 hover:text-white hover:bg-amber-950/30 border border-amber-500/20 transition group">
                                        <span class="flex items-center gap-3">
                                            <span class="text-sm group-hover:rotate-12 transition-transform">🎲</span>
                                            <span>Surprends-moi</span>
                                        </span>
                                        <span class="text-[10px] px-1.5 py-0.5 rounded bg-amber-500/20 text-amber-300 font-extrabold border border-amber-500/30">CIBLÉ</span>
                                    </a>

                                    <a href="{{ route('library', ['tab' => 'rattrapage']) }}" class="flex items-center justify-between px-3 py-2 rounded-lg text-xs font-semibold text-[#f87171] hover:text-white hover:bg-red-950/40 border border-red-500/20 transition group">
                                        <span class="flex items-center gap-3">
                                            <svg class="w-4 h-4 text-[#dc2626] group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                            <span>Mode Rattrapage</span>
                                        </span>
                                        <span class="text-[10px] px-1.5 py-0.5 rounded bg-red-600 text-white font-extrabold">NON LUS</span>
                                    </a>

                                    <a href="{{ route('library') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-xs font-semibold {{ request()->routeIs('library') && request('tab') !== 'rattrapage' ? 'text-white bg-[#dc2626]' : 'text-[#c0c0d8] hover:text-white hover:bg-[#1a1a28]' }} transition group">
                                        <svg class="w-4 h-4 text-[#dc2626] group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                                        <span>Ma Bibliothèque</span>
                                    </a>
                                </div>

                                {{-- Liens du profil --}}
                                <div class="py-1.5 space-y-0.5">
                                    <a href="{{ route('profile.edit', ['tab' => 'overview']) }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-xs font-semibold text-[#c0c0d8] hover:text-white hover:bg-[#1a1a28] transition group">
                                        <svg class="w-4 h-4 text-[#dc2626] group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                        <span>Mon Profil</span>
                                    </a>

                                    <a href="{{ route('profile.edit', ['tab' => 'edit']) }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-xs font-semibold text-[#c0c0d8] hover:text-white hover:bg-[#1a1a28] transition group">
                                        <svg class="w-4 h-4 text-[#dc2626] group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        <span>Modifier mon profil</span>
                                    </a>

                                    <a href="{{ route('profile.edit', ['tab' => 'settings']) }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-xs font-semibold text-[#c0c0d8] hover:text-white hover:bg-[#1a1a28] transition group">
                                        <svg class="w-4 h-4 text-[#dc2626] group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                        <span>Paramètres & Sécurité</span>
                                    </a>

                                    @if(Auth::user()->canAccessPanel(filament()->getPanel('admin')))
                                        @php
                                            $staffBadge = Auth::user()->staff_badge;
                                            $admClass = match($staffBadge['color'] ?? '') {
                                                'red' => 'text-red-400 hover:bg-red-600/20 border-red-500/25',
                                                'purple' => 'text-purple-300 hover:bg-purple-600/20 border-purple-500/25',
                                                'sky' => 'text-sky-300 hover:bg-sky-500/20 border-sky-400/25',
                                                default => 'text-purple-300 hover:bg-purple-600/20 border-purple-500/25',
                                            };
                                            $iconColor = match($staffBadge['color'] ?? '') {
                                                'red' => 'text-red-400',
                                                'purple' => 'text-purple-400',
                                                'sky' => 'text-sky-400',
                                                default => 'text-purple-400',
                                            };
                                        @endphp
                                        <a href="/admin" target="_blank" class="flex items-center justify-between px-3 py-2 rounded-lg text-xs font-bold transition border {{ $admClass }}">
                                            <span class="flex items-center gap-3">
                                                <svg class="w-4 h-4 {{ $iconColor }} flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                                <span>Administration</span>
                                            </span>
                                            @if($staffBadge)
                                                <span class="text-[9px] uppercase tracking-wider font-extrabold px-1.5 py-0.5 rounded border {{ $staffBadge['bg'] }} {{ $staffBadge['text'] }} {{ $staffBadge['border'] }}">
                                                    {{ $staffBadge['name'] }}
                                                </span>
                                            @endif
                                        </a>
                                    @endif
                                </div>

                                {{-- Discord tout en bas --}}
                                <div class="pt-1.5 border-t border-[#222234]">
                                    <a href="https://discord.gg/sMUeFSks4" target="_blank" rel="noopener noreferrer" class="flex items-center justify-between px-3 py-2 rounded-lg text-xs font-semibold text-[#8ea1ff] bg-[#5865F2]/10 border border-[#5865F2]/25 hover:bg-[#5865F2]/20 transition">
                                        <span class="flex items-center gap-2.5">
                                            <svg class="w-4 h-4 text-[#5865F2]" viewBox="0 0 127.14 96.36" fill="currentColor"><path d="M107.7,8.07A105.15,105.15,0,0,0,81.47,0a72.06,72.06,0,0,0-3.36,6.83A97.68,97.68,0,0,0,49,6.83,72.37,72.37,0,0,0,45.64,0,105.89,105.89,0,0,0,19.39,8.09C2.79,32.65-1.71,56.6.54,80.21h0A105.73,105.73,0,0,0,32.71,96.36,77.7,77.7,0,0,0,39.6,85.25a68.42,68.42,0,0,1-10.85-5.18c.91-.66,1.8-1.34,2.66-2a75.57,75.57,0,0,0,64.32,0c.87.71,1.76,1.39,2.66,2a68.68,68.68,0,0,1-10.87,5.19,77,77,0,0,0,6.89,11.1A105.25,105.25,0,0,0,126.6,80.22h0C129.24,52.84,122.09,29.11,107.7,8.07ZM42.45,65.69C36.18,65.69,31,60,31,53s5-12.74,11.43-12.74S54,46,53.89,53,48.84,65.69,42.45,65.69Zm42.24,0C78.41,65.69,73.31,60,73.31,53s5-12.74,11.43-12.74S96.2,46,96.12,53,91.08,65.69,84.69,65.69Z"/></svg>
                                            <span>Discord</span>
                                        </span>
                                        <span class="text-[9px] font-extrabold uppercase px-1.5 py-0.5 rounded-full bg-[#5865F2]/20 text-[#8ea1ff] border border-[#5865F2]/30">Communauté</span>
                                    </a>
                                </div>

                                {{-- Déconnexion --}}
                                <div style="border-top: 1px solid #222234; padding-top: 4px; margin-top: 6px;">
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit" class="w-full flex items-center gap-3 px-3 py-2 rounded-lg text-xs font-semibold text-[#f87171] hover:bg-red-500/10 hover:text-red-400 transition cursor-pointer">
                                            <svg class="w-4 h-4 text-[#f87171]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                                            <span>Se déconnecter</span>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @else
                        {{-- Bouton Se connecter Mobile (Visiteur) --}}
                        <a href="{{ route('login') }}" 
                           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-gradient-to-r from-[#dc2626] to-[#b91c1c] hover:from-[#ef4444] hover:to-[#dc2626] text-white text-xs font-bold transition shadow-md shadow-red-950/50 flex-shrink-0 active:scale-95">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>
                            <span>Se connecter</span>
                        </a>

                        {{-- Bouton Menu Hamburger Mobile (Visiteur uniquement) --}}
                        <button 
                            type="button"
                            @click.stop="toggleMobileMenu()" 
                            class="p-2 rounded-lg text-mist hover:text-chalk hover:bg-panel-hi transition cursor-pointer"
                            title="Menu principal"
                            aria-label="Ouvrir le menu"
                        >
                            <svg x-show="!mobileOpen" class="w-5 h-5 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                            <svg x-show="mobileOpen" x-cloak class="w-5 h-5 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    @endauth
                </div>
            </div>

            {{-- Recherche mobile --}}
            <div x-show="searchOpen" x-cloak @click.stop x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-100"
                 x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-1"
                 class="md:hidden pt-1 pb-3 px-1">
                <form action="{{ route('manga.index') }}" method="GET" class="relative flex items-center">
                    <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-mist pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input type="text" name="q" placeholder="Rechercher une série..."
                        class="input-field w-full text-sm !pl-10 !pr-10 !py-2.5 !rounded-xl !bg-[#12121c] !border-[#262638] focus:!border-[#dc2626] shadow-inner text-white placeholder-[#7070a0]" autofocus>
                    <button type="submit" class="absolute right-2 top-1/2 -translate-y-1/2 p-1.5 text-mist hover:text-white" aria-label="Rechercher">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    </button>
                </form>
            </div>

            {{-- Panneau Notifications Mobile --}}
            @auth
            <div x-show="notifMobileOpen" x-cloak @click.stop x-transition class="md:hidden pb-3">
                <div style="background-color: #12121c; border: 1px solid #262638; border-radius: 14px; overflow: hidden; box-shadow: 0 16px 40px rgba(0,0,0,0.85);">
                    <div class="p-3 border-b border-[#222234] flex items-center justify-between bg-[#161624]">
                        <div class="flex items-center gap-2">
                            <span class="font-bold text-xs text-white">Notifications des chapitres</span>
                            <template x-if="$store?.notifications && $store.notifications.unreadCount > 0">
                                <span class="text-[9px] font-extrabold px-1.5 py-0.5 rounded-full bg-red-600/30 text-red-400 border border-red-500/40"
                                      x-text="$store.notifications.unreadCount"></span>
                            </template>
                        </div>
                        <template x-if="$store?.notifications && $store.notifications.unreadCount > 0">
                            <button @click="$store.notifications.markAllAsRead()" class="text-[10px] text-[#8ea1ff] font-medium">Tout lire</button>
                        </template>
                    </div>

                    <div class="max-h-72 overflow-y-auto divide-y divide-[#1e1e2c]">
                        <template x-if="!$store?.notifications || !$store.notifications.notifications || $store.notifications.notifications.length === 0">
                            <div class="p-6 text-center text-xs text-[#7070a0]">
                                Aucune notification pour le moment.
                            </div>
                        </template>

                        <template x-for="item in ($store?.notifications?.notifications || [])" :key="item.id">
                            <div @click="handleNotifClick(item)"
                                 :class="item.is_read ? 'bg-[#12121c] opacity-80' : 'bg-[#181828] border-l-2 border-[#dc2626]'"
                                 class="p-2.5 flex items-start gap-2.5 hover:bg-[#1e1e30] transition cursor-pointer">
                                <div class="w-9 h-12 rounded bg-[#1f1f2e] overflow-hidden flex-shrink-0">
                                    <template x-if="item.manga_cover">
                                        <img :src="item.manga_cover" class="w-full h-full object-cover" alt="">
                                    </template>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between gap-1 mb-0.5">
                                        <span class="text-xs font-bold text-white truncate" x-text="item.manga_title"></span>
                                        <span class="text-[9px] text-[#7070a0]" x-text="item.created_at_human"></span>
                                    </div>
                                    <p class="text-[11px] text-[#c0c0d8]">
                                        <span class="font-bold text-red-400" x-text="'Ch. ' + item.chapter_number"></span>
                                        <span x-text="item.chapter_title ? ' - ' + item.chapter_title : ''"></span>
                                    </p>
                                </div>
                            </div>
                        </template>
                    </div>

                    <div class="p-2 bg-[#141422] border-t border-[#222234] text-center">
                        <a href="{{ route('library', ['tab' => 'rattrapage']) }}" class="text-xs font-bold text-[#f87171] hover:text-white transition inline-flex items-center gap-1.5">
                            ⚡ Accéder au Mode Rattrapage →
                        </a>
                    </div>
                </div>
            </div>
            @endauth
        </div>

        {{-- Menu mobile (Visiteurs uniquement) --}}
        @guest
        <div x-show="mobileOpen" x-cloak @click.stop x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-2"
             style="background-color: #0e0e15; border-top: 1px solid #1e1e2e; box-shadow: 0 16px 36px rgba(0,0,0,0.85);"
             class="md:hidden max-h-[calc(100vh-4.5rem)] overflow-y-auto">
            <div class="px-4 py-3 space-y-2">
                {{-- Liens de navigation principale --}}
                <div class="space-y-1">
                    <a href="{{ route('home') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold {{ request()->routeIs('home') ? 'text-white bg-[#dc2626]' : 'text-[#a0a0c0] hover:text-white hover:bg-[#161622]' }} transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                        Accueil
                    </a>
                    <a href="{{ route('manga.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold {{ request()->routeIs('manga.index') ? 'text-white bg-[#dc2626]' : 'text-[#a0a0c0] hover:text-white hover:bg-[#161622]' }} transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                        Catalogue
                    </a>

                    <a href="{{ route('manga.random') }}"
                       onclick="if(window.HiddenScan){ const favs = window.HiddenScan.getFavorites().map(f => f.slug).join(','); if(favs) this.href = '{{ route('manga.random') }}?favs=' + encodeURIComponent(favs); }"
                       class="flex items-center justify-between px-3.5 py-2.5 rounded-xl text-sm font-bold text-amber-400 bg-amber-950/20 border border-amber-500/30 hover:bg-amber-900/30 transition">
                        <span class="flex items-center gap-3">
                            <span class="text-base">🎲</span>
                            <span>Surprends-moi</span>
                        </span>
                        <span class="text-[10px] px-2 py-0.5 rounded-full bg-amber-500/20 text-amber-300 font-extrabold uppercase border border-amber-500/30">Ciblé</span>
                    </a>
                    <a href="{{ route('library', ['tab' => 'rattrapage']) }}" class="flex items-center justify-between px-3.5 py-2.5 rounded-xl text-sm font-bold text-[#f87171] bg-red-950/30 border border-red-500/30 hover:bg-red-900/40 transition">
                        <span class="flex items-center gap-3">
                            <svg class="w-4 h-4 text-[#dc2626]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                            Mode Rattrapage
                        </span>
                        <span class="text-[10px] px-2 py-0.5 rounded-full bg-red-600 text-white font-extrabold uppercase">Non lus</span>
                    </a>
                    <a href="{{ route('library') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold {{ request()->routeIs('library') && request('tab') !== 'rattrapage' ? 'text-white bg-[#dc2626]' : 'text-[#a0a0c0] hover:text-white hover:bg-[#161622]' }} transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                        Bibliothèque
                    </a>
                </div>

                {{-- Connexion / Inscription pour invité --}}
                <div class="pt-2 border-t border-[#1e1e2e] grid grid-cols-2 gap-2">
                    <a href="{{ route('login') }}" class="flex items-center justify-center gap-1.5 py-2.5 px-3 rounded-xl bg-gradient-to-r from-[#dc2626] to-[#b91c1c] hover:from-[#ef4444] hover:to-[#dc2626] text-white text-xs font-bold transition text-center shadow-md">
                        Se connecter
                    </a>
                    <a href="{{ route('register') }}" class="flex items-center justify-center gap-1.5 py-2.5 px-3 rounded-xl bg-[#181826] hover:bg-[#202032] text-white text-xs font-semibold border border-[#2a2a3e] transition text-center">
                        S'inscrire
                    </a>
                </div>

                {{-- Discord Tout en bas --}}
                <div class="pt-2 border-t border-[#1e1e2e]">
                    <a href="https://discord.gg/sMUeFSks4" target="_blank" rel="noopener noreferrer" class="flex items-center justify-between px-3.5 py-2.5 rounded-xl text-xs font-semibold text-[#8ea1ff] bg-[#5865F2]/10 border border-[#5865F2]/25 hover:bg-[#5865F2]/20 transition">
                        <span class="flex items-center gap-3">
                            <svg class="w-4 h-4 text-[#5865F2]" viewBox="0 0 127.14 96.36" fill="currentColor"><path d="M107.7,8.07A105.15,105.15,0,0,0,81.47,0a72.06,72.06,0,0,0-3.36,6.83A97.68,97.68,0,0,0,49,6.83,72.37,72.37,0,0,0,45.64,0,105.89,105.89,0,0,0,19.39,8.09C2.79,32.65-1.71,56.6.54,80.21h0A105.73,105.73,0,0,0,32.71,96.36,77.7,77.7,0,0,0,39.6,85.25a68.42,68.42,0,0,1-10.85-5.18c.91-.66,1.8-1.34,2.66-2a75.57,75.57,0,0,0,64.32,0c.87.71,1.76,1.39,2.66,2a68.68,68.68,0,0,1-10.87,5.19,77,77,0,0,0,6.89,11.1A105.25,105.25,0,0,0,126.6,80.22h0C129.24,52.84,122.09,29.11,107.7,8.07ZM42.45,65.69C36.18,65.69,31,60,31,53s5-12.74,11.43-12.74S54,46,53.89,53,48.84,65.69,42.45,65.69Zm42.24,0C78.41,65.69,73.31,60,73.31,53s5-12.74,11.43-12.74S96.2,46,96.12,53,91.08,65.69,84.69,65.69Z"/></svg>
                            <span>Rejoindre notre Discord</span>
                        </span>
                        <span class="text-[10px] font-extrabold uppercase px-2 py-0.5 rounded-full bg-[#5865F2]/20 text-[#8ea1ff] border border-[#5865F2]/30">Communauté</span>
                    </a>
                </div>

            </div>
        </div>
        @endguest
    </nav>
    @endunless

    <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 {{ $hideNavbar ? '' : 'pt-20' }} pb-24 sm:pb-12 relative z-10 overflow-x-hidden">
        {{ $slot }}

        {{-- Modal de remise du Pass Secret --}}
        @if(session('new_pass_code'))
        <div x-data="{ open: true, copied: false }" x-show="open" x-cloak 
             class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/85 backdrop-blur-md animate-fade-in">
            <div class="relative w-full max-w-md bg-[#13141d] border-2 border-red-500/40 rounded-3xl p-6 sm:p-8 shadow-2xl shadow-red-950/70 text-center animate-fade-in-up">
                <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-gradient-to-tr from-red-600 to-amber-600 flex items-center justify-center text-3xl shadow-lg shadow-red-600/40 animate-pulse">
                    🎉
                </div>
                <h3 class="font-display text-xl sm:text-2xl font-extrabold text-white mb-2">Bienvenue sur Hidden Scan !</h3>
                <p class="text-xs sm:text-sm text-gray-300 mb-6 leading-relaxed">
                    Votre compte anonyme est prêt. Voici votre <strong>Pass Secret</strong> unique, il remplace votre email et votre mot de passe :
                </p>

                {{-- Le Pass Code affiché en grand --}}
                <div class="p-4 rounded-2xl bg-[#0c0d12] border border-red-500/40 mb-4 flex items-center justify-between gap-3 shadow-inner">
                    <span class="font-mono font-bold text-base sm:text-lg text-red-400 tracking-wider select-all">
                        {{ session('new_pass_code') }}
                    </span>
                    <button type="button" 
                            @click="navigator.clipboard.writeText('{{ session('new_pass_code') }}'); copied = true; setTimeout(() => copied = false, 2500)"
                            class="px-3 py-1.5 rounded-xl bg-red-600/20 hover:bg-red-600/30 text-white text-xs font-bold border border-red-500/30 transition flex items-center gap-1.5 flex-shrink-0 cursor-pointer">
                        <span x-show="!copied">Copier 📋</span>
                        <span x-show="copied" x-cloak class="text-green-400">Copié ! ✓</span>
                    </button>
                </div>

                <div class="p-3.5 rounded-xl bg-amber-500/10 border border-amber-500/25 text-[11px] text-amber-200/90 leading-relaxed mb-6 text-left flex items-start gap-2.5">
                    <span class="text-sm">⚠️</span>
                    <span><strong>Conservez ce code précieusement</strong> (dans vos notes ou gestionnaire). C'est le seul moyen de vous reconnecter sur votre téléphone ou un autre navigateur.</span>
                </div>

                <button type="button" @click="open = false" 
                        class="w-full py-3.5 bg-gradient-to-r from-red-600 to-red-700 hover:from-red-500 hover:to-red-600 text-white font-extrabold text-sm rounded-xl transition shadow-lg shadow-red-600/30 cursor-pointer hover:scale-[1.01] active:scale-[0.99]">
                    C'est noté, commencer à lire ! 🚀
                </button>
            </div>
        </div>
        @endif
    </main>

    {{-- ═══ Footer ═══ --}}
    <footer class="border-t border-line/50 bg-ink-deep/50 mt-auto relative z-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 py-12">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8 mb-8">
                {{-- Marque --}}
                <div class="sm:col-span-2 lg:col-span-1">
                    <a href="{{ route('home') }}" class="flex items-center gap-3 mb-4">
                        <img src="{{ asset('images/logo.png') }}" alt="Hidden Scan" class="h-8 w-8 rounded-lg">
                        <span class="font-display font-extrabold text-lg text-chalk">Hidden<span class="text-violet">Scan</span></span>
                    </a>
                    <p class="text-mist text-sm leading-relaxed">Plateforme de lecture de mangas, manhwas et manhuas. Accès libre, sans compte.</p>
                </div>

                {{-- Navigation --}}
                <div>
                    <h4 class="font-display font-bold text-sm text-chalk mb-3 uppercase tracking-wider">Navigation</h4>
                    <ul class="space-y-2">
                        <li><a href="{{ route('home') }}" class="text-sm text-mist hover:text-violet transition">Accueil</a></li>
                        <li><a href="{{ route('manga.index') }}" class="text-sm text-mist hover:text-violet transition">Catalogue</a></li>
                        <li><a href="{{ route('library') }}" class="text-sm text-mist hover:text-violet transition">Bibliothèque</a></li>
                    </ul>
                </div>

                {{-- Légal --}}
                <div>
                    <h4 class="font-display font-bold text-sm text-chalk mb-3 uppercase tracking-wider">Légal</h4>
                    <ul class="space-y-2">
                        <li><a href="{{ route('legal.mentions') }}" class="text-sm text-mist hover:text-violet transition">Mentions légales</a></li>
                        <li><a href="{{ route('legal.cgu') }}" class="text-sm text-mist hover:text-violet transition">CGU</a></li>
                        <li><a href="{{ route('legal.privacy') }}" class="text-sm text-mist hover:text-violet transition">Confidentialité</a></li>
                        <li><a href="{{ route('legal.dmca') }}" class="text-sm text-mist hover:text-violet transition">DMCA</a></li>
                    </ul>
                </div>

                {{-- Communauté --}}
                <div>
                    <h4 class="font-display font-bold text-sm text-chalk mb-3 uppercase tracking-wider">Communauté</h4>
                    <ul class="space-y-2">
                        <li>
                            <a href="https://discord.gg/sMUeFSks4" target="_blank" class="text-sm text-mist hover:text-violet transition inline-flex items-center gap-2">
                                <svg class="w-4 h-4" viewBox="0 0 127.14 96.36" fill="currentColor"><path d="M107.7,8.07A105.15,105.15,0,0,0,81.47,0a72.06,72.06,0,0,0-3.36,6.83A97.68,97.68,0,0,0,49,6.83,72.37,72.37,0,0,0,45.64,0,105.89,105.89,0,0,0,19.39,8.09C2.79,32.65-1.71,56.6.54,80.21h0A105.73,105.73,0,0,0,32.71,96.36,77.7,77.7,0,0,0,39.6,85.25a68.42,68.42,0,0,1-10.85-5.18c.91-.66,1.8-1.34,2.66-2a75.57,75.57,0,0,0,64.32,0c.87.71,1.76,1.39,2.66,2a68.68,68.68,0,0,1-10.87,5.19,77,77,0,0,0,6.89,11.1A105.25,105.25,0,0,0,126.6,80.22h0C129.24,52.84,122.09,29.11,107.7,8.07ZM42.45,65.69C36.18,65.69,31,60,31,53s5-12.74,11.43-12.74S54,46,53.89,53,48.84,65.69,42.45,65.69Zm42.24,0C78.41,65.69,73.31,60,73.31,53s5-12.74,11.43-12.74S96.2,46,96.12,53,91.08,65.69,84.69,65.69Z"/></svg>
                                Discord
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            {{-- Copyright --}}
            <div class="pt-6 border-t border-line/30 flex flex-col sm:flex-row items-center justify-between gap-3">
                <p class="text-mist text-xs">© {{ date('Y') }} Hidden Scan — Tous droits réservés</p>
                <p class="text-mist/60 text-xs">Fait avec ♥ pour la communauté</p>
            </div>
        </div>
    </footer>

    {{-- Widget Discord Flottant (Grand écran uniquement) --}}
    @unless($hideNavbar)
    <a href="https://discord.gg/sMUeFSks4"
       target="_blank"
       rel="noopener noreferrer"
       class="discord-float-btn hidden sm:inline-flex group"
       title="Rejoindre notre Discord"
       aria-label="Rejoindre notre communauté Discord">
        <span class="relative flex items-center justify-center">
            <svg viewBox="0 0 127.14 96.36" fill="currentColor">
                <path d="M107.7,8.07A105.15,105.15,0,0,0,81.47,0a72.06,72.06,0,0,0-3.36,6.83A97.68,97.68,0,0,0,49,6.83,72.37,72.37,0,0,0,45.64,0,105.89,105.89,0,0,0,19.39,8.09C2.79,32.65-1.71,56.6.54,80.21h0A105.73,105.73,0,0,0,32.71,96.36,77.7,77.7,0,0,0,39.6,85.25a68.42,68.42,0,0,1-10.85-5.18c.91-.66,1.8-1.34,2.66-2a75.57,75.57,0,0,0,64.32,0c.87.71,1.76,1.39,2.66,2a68.68,68.68,0,0,1-10.87,5.19,77,77,0,0,0,6.89,11.1A105.25,105.25,0,0,0,126.6,80.22h0C129.24,52.84,122.09,29.11,107.7,8.07ZM42.45,65.69C36.18,65.69,31,60,31,53s5-12.74,11.43-12.74S54,46,53.89,53,48.84,65.69,42.45,65.69Zm42.24,0C78.41,65.69,73.31,60,73.31,53s5-12.74,11.43-12.74S96.2,46,96.12,53,91.08,65.69,84.69,65.69Z"/>
            </svg>
            {{-- Point vert indicateur de présence --}}
            <span class="absolute -top-1 -right-1 w-2.5 h-2.5 bg-emerald-400 border-2 border-[#5865F2] rounded-full shadow-sm"></span>
        </span>
        <span class="discord-label">
            Rejoindre Discord
        </span>
    </a>
    @endunless

    {{-- Bouton retour en haut --}}
    <button
        x-data="{ show: false }"
        x-init="window.addEventListener('scroll', () => show = window.scrollY > 400)"
        x-show="show"
        x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-2 scale-90"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
        x-transition:leave-end="opacity-0 translate-y-2 scale-90"
        @click="window.scrollTo({ top: 0, behavior: 'smooth' })"
        class="scroll-top-btn bg-panel-hi/90 hover:bg-[#dc2626] text-white border border-line/50 hover:border-[#dc2626]/50 backdrop-blur-md cursor-pointer transition-all hover:scale-105"
        title="Retour en haut"
        aria-label="Retour en haut de page"
    >
        <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 15l7-7 7 7"/></svg>
    </button>

    @livewireScripts
    <script>
        if (window.Alpine && typeof setupNotificationsStore === 'function') {
            setupNotificationsStore();
        }
        // Secours résilient : si Livewire tarde ou échoue à charger Alpine, on charge Alpine automatiquement
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() {
                if (!window.Alpine) {
                    var s = document.createElement('script');
                    s.src = 'https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js';
                    s.defer = true;
                    s.onload = function() {
                        if (typeof setupNotificationsStore === 'function') {
                            try { setupNotificationsStore(); } catch(e) {}
                        }
                    };
                    document.head.appendChild(s);
                }
            }, 300);
        });
    </script>
</body>
</html>