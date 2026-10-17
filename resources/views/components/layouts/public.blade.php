@props(['title' => 'Hidden Scan', 'description' => null, 'hideNavbar' => false, 'showFooter' => null, 'container' => true])
@php
    $authPayload = auth()->check() ? [
        'id' => auth()->id(),
        'name' => auth()->user()->name,
        'email' => auth()->user()->email,
        'avatar' => auth()->user()->avatar,
        'avatar_url' => auth()->user()->avatar_url,
        'banner' => auth()->user()->banner,
        'banner_url' => auth()->user()->banner_url,
        'bio' => auth()->user()->bio,
        'favorite_genre' => auth()->user()->favorite_genre,
        'reader_mode' => auth()->user()->reader_mode,
    ] : null;
@endphp

<!DOCTYPE html>
<html lang="fr" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="dark">
    <meta name="description" content="{{ $description ?? 'Hidden Scan — Lis tes mangas, manhwas et manhuas en ligne gratuitement et sans inscription.' }}">
    <meta name="theme-color" content="#0a0a0a">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta property="og:title" content="{{ $title ?? 'Hidden Scan' }}">
    <meta property="og:description" content="{{ $description ?? 'Plateforme de lecture de mangas, manhwas et manhuas. Accès libre, sans compte.' }}">
    <meta property="og:type" content="website">
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    <title>{{ $title ?? 'Hidden Scan' }}</title>

    {{-- Google Fonts (Preload Inter & Nunito Stricts) --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Nunito:wght@600;700;800;900&display=swap" rel="stylesheet">

    {{-- AOS.js (cdnjs.cloudflare.com) --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css" integrity="sha512-1cK78a1o+ht2JcaW6g8OXYwqpev9+6GqOkz9kcKe9ybVDh8nvm98y01wlPX9h5qv5qObO4Ufa4001lK833SOEg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js" integrity="sha512-A7AYk1fGKX6S2SsHyWMPkrnzTZHrgiVT7GcQkLGDe2ev0aWb8zejytzS8wjo7PGEXKqJOrjQ4oORtnimIRZBtw==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>

    {{-- Swiper.js (Déjà présent dans le projet) --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>

    {{-- Tippy.js (cdnjs.cloudflare.com) --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tippy.js/6.3.7/tippy.css" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/2.11.8/umd/popper.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/tippy.js/6.3.7/tippy.umd.min.js"></script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles

    <script>
    // Système de données locales Hidden Scan (Sauvegarde & Sync)
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
        getProfile() {
            try { return JSON.parse(localStorage.getItem('hiddenscan_profile') || '{}'); } catch(e) { return {}; }
        },
        saveProfile(profile) {
            try {
                const current = this.getProfile();
                const updated = { ...current, ...profile, updatedAt: new Date().toISOString() };
                localStorage.setItem('hiddenscan_profile', JSON.stringify(updated));
                return updated;
            } catch(e) { return {}; }
        },
        syncProfile() {
            const local = this.getProfile();
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const currentUser = @json($authPayload);

            if (currentUser) {
                let updatedLocal = false;
                if (currentUser.avatar) {
                    local.avatar = currentUser.avatar;
                    local.avatar_url = currentUser.avatar_url;
                    updatedLocal = true;
                }
                if (currentUser.banner) {
                    local.banner = currentUser.banner;
                    local.banner_url = currentUser.banner_url;
                    updatedLocal = true;
                }
                if (currentUser.bio) {
                    local.bio = currentUser.bio;
                    updatedLocal = true;
                }
                if (currentUser.name && (!local.name || local.name.startsWith('Lecteur-'))) {
                    local.name = currentUser.name;
                    updatedLocal = true;
                }
                if (updatedLocal) {
                    this.saveProfile(local);
                }

                const needsPush = (!currentUser.avatar && (local.avatar || local.avatar_url)) || 
                                  (!currentUser.banner && (local.banner || local.banner_url)) || 
                                  (!currentUser.bio && local.bio);

                if (needsPush) {
                    fetch('/api/profile/sync', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': token || ''
                        },
                        body: JSON.stringify({
                            avatar: local.avatar || local.avatar_url || '',
                            banner: local.banner || local.banner_url || '',
                            bio: local.bio || '',
                            name: local.name || '',
                            favorite_genre: local.favorite_genre || '',
                            reader_mode: local.reader_mode || '',
                        })
                    })
                    .then(r => r.json())
                    .then(res => {
                        if (res && res.success && res.user) {
                            this.saveProfile(res.user);
                            document.querySelectorAll('.navbar-avatar-img').forEach(img => {
                                if (res.user.avatar_url) img.src = res.user.avatar_url;
                            });
                        }
                    })
                    .catch(() => {});
                }
            }
        },
    };

    document.addEventListener('DOMContentLoaded', () => {
        window.HiddenScan.syncFavorites();
        window.HiddenScan.syncProfile();
    });

    // ═══ Alpine Store : Notifications ═══
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
                            this.unreadCount = data.unread_count || 0;
                            this.notifications = data.notifications || [];
                        }
                    } catch(e) {}
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
                    } catch(e) {}
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
                    } catch(e) {}
                },

                handleClick(item) {
                    try { this.markAsRead(item.id); } catch(e) {}
                    if (item.url) {
                        window.location.href = item.url;
                    }
                }
            });
        } catch(e) {}
    }

    // ═══ Alpine Store : Murim ═══
    function setupMurimStore() {
        if (!window.Alpine) return;
        try {
            if (typeof Alpine.store === 'function' && Alpine.store('murim')) return;
        } catch(e) {}

        try {
            Alpine.store('murim', {
                open: false,
                loading: false,
                status: null,
                streak: 0,
                breakthroughAlert: false,

                async init() {
                    this.updateStreak();
                    await this.fetchStatus();
                    window.addEventListener('chapter-read', () => {
                        this.onChapterRead();
                    });
                },

                updateStreak() {
                    try {
                        const todayStr = new Date().toISOString().split('T')[0];
                        const data = JSON.parse(localStorage.getItem('hs_murim_streak') || '{}');
                        if (data.lastDate === todayStr) {
                            this.streak = data.streak || 1;
                        } else if (data.lastDate) {
                            const yesterday = new Date();
                            yesterday.setDate(yesterday.getDate() - 1);
                            const yesterdayStr = yesterday.toISOString().split('T')[0];
                            this.streak = data.lastDate === yesterdayStr ? (data.streak || 1) : 0;
                        } else {
                            this.streak = 0;
                        }
                    } catch(e) { this.streak = 0; }
                },

                recordDailyReading() {
                    try {
                        const todayStr = new Date().toISOString().split('T')[0];
                        const data = JSON.parse(localStorage.getItem('hs_murim_streak') || '{}');
                        if (data.lastDate !== todayStr) {
                            const yesterday = new Date();
                            yesterday.setDate(yesterday.getDate() - 1);
                            const yesterdayStr = yesterday.toISOString().split('T')[0];
                            let newStreak = (data.lastDate === yesterdayStr) ? ((data.streak || 0) + 1) : 1;
                            localStorage.setItem('hs_murim_streak', JSON.stringify({ lastDate: todayStr, streak: newStreak }));
                            this.streak = newStreak;
                        }
                    } catch(e) {}
                },

                async onChapterRead() {
                    this.recordDailyReading();
                    const oldRankId = this.status?.current?.id;
                    const prevCount = parseInt(localStorage.getItem('hs_total_read_chapters') || '0');
                    localStorage.setItem('hs_total_read_chapters', (prevCount + 1).toString());
                    await this.fetchStatus();
                    if (oldRankId && this.status?.current?.id > oldRankId) {
                        this.breakthroughAlert = true;
                        setTimeout(() => { this.breakthroughAlert = false; }, 7000);
                    }
                },

                getLocalReadCount() {
                    try {
                        const data = window.HiddenScan ? window.HiddenScan._getData() : {};
                        const progress = data.progress || {};
                        let count = 0;
                        Object.values(progress).forEach(p => {
                            if (p && Array.isArray(p.readChapterIds)) count += p.readChapterIds.length;
                            else if (p && p.chapter) count += 1;
                        });
                        const explicit = parseInt(localStorage.getItem('hs_total_read_chapters') || '0');
                        return Math.max(count, explicit);
                    } catch(e) { return 0; }
                },

                async fetchStatus() {
                    try {
                        const localCount = this.getLocalReadCount();
                        const res = await fetch(`/api/progress/murim?local_read_count=${localCount}&streak=${this.streak}`);
                        if (!res.ok) return;
                        const json = await res.json();
                        if (json && json.success) {
                            this.status = json.status;
                        }
                    } catch(e) {}
                },

                toggle() {
                    this.open = !this.open;
                    if (this.open) {
                        this.fetchStatus();
                    }
                }
            });
        } catch(e) {}
    }

    function initStores() {
        setupNotificationsStore();
        setupMurimStore();
    }

    document.addEventListener('alpine:init', initStores);
    document.addEventListener('livewire:init', initStores);
    document.addEventListener('DOMContentLoaded', () => {
        initStores();
        if (typeof AOS !== 'undefined') {
            AOS.init({
                once: true,
                duration: 400,
                easing: 'ease-out-cubic'
            });
        }
        if (typeof tippy !== 'undefined') {
            tippy('[data-tippy-content]', {
                theme: 'dark',
                animation: 'fade',
                duration: 150
            });
        }
    });
    if (window.Alpine) {
        initStores();
    }
    </script>
</head>

<body class="bg-[var(--bg-base)] text-[var(--text-1)] min-h-screen font-sans antialiased flex flex-col relative overflow-x-hidden"
      x-data="{
          searchOverlayOpen: false,
          searchQuery: '',
          searchResults: [],
          searchLoading: false,
          selectedIndex: -1,

          openSearch() {
              this.searchOverlayOpen = true;
              this.$nextTick(() => {
                  this.$refs.overlayInput?.focus();
              });
          },

          closeSearch() {
              this.searchOverlayOpen = false;
              this.searchQuery = '';
              this.searchResults = [];
              this.selectedIndex = -1;
          },

          async performSearch() {
              const q = this.searchQuery.trim();
              if (q.length < 2) {
                  this.searchResults = [];
                  this.selectedIndex = -1;
                  return;
              }
              this.searchLoading = true;
              try {
                  const res = await fetch('/api/bot/mangas?q=' + encodeURIComponent(q));
                  if (res.ok) {
                      const data = await res.json();
                      this.searchResults = (data.mangas || data || []).slice(0, 8);
                  }
              } catch(e) {
                  this.searchResults = [];
              } finally {
                  this.searchLoading = false;
                  this.selectedIndex = -1;
              }
          },

          selectNext() {
              if (this.searchResults.length === 0) return;
              this.selectedIndex = (this.selectedIndex + 1) % this.searchResults.length;
          },

          selectPrev() {
              if (this.searchResults.length === 0) return;
              this.selectedIndex = (this.selectedIndex - 1 + this.searchResults.length) % this.searchResults.length;
          },

          submitSelected() {
              if (this.selectedIndex >= 0 && this.searchResults[this.selectedIndex]) {
                  window.location.href = '/manga/' + this.searchResults[this.selectedIndex].slug;
              } else if (this.searchQuery.trim()) {
                  window.location.href = '{{ route('manga.index') }}?q=' + encodeURIComponent(this.searchQuery.trim());
              }
          }
      }"
      @keydown.window.slash.prevent="if (!['INPUT', 'TEXTAREA'].includes($event.target.tagName)) openSearch()"
      @keydown.window.cmd.k.prevent="openSearch()"
      @keydown.window.ctrl.k.prevent="openSearch()"
      @keydown.escape.window="closeSearch()">

    @unless($hideNavbar)
    {{-- ═══ Navbar (64px, Transparent -> Dark Blur on scroll) ═══ --}}
    <nav id="main-navbar" 
         class="fixed top-0 left-0 right-0 z-50 h-16 transition-all duration-200 border-b border-transparent bg-transparent"
         x-data="{ 
             mobileOpen: false, 
             userMobileMenuOpen: false,
             notifMenuOpen: false,

             closeAll() {
                 this.mobileOpen = false;
                 this.userMobileMenuOpen = false;
                 this.notifMenuOpen = false;
             }
         }"
         @click.outside="closeAll()">
        
        <div class="max-w-[1280px] w-full mx-auto px-4 sm:px-6 h-full flex items-center justify-between gap-4">
            
            {{-- Logo Gauche --}}
            <a href="{{ route('home') }}" class="flex items-center gap-2.5 flex-shrink-0 group py-1">
                <img src="{{ asset('images/logo.png') }}" alt="Hidden Scan" class="w-8 h-8 rounded-[4px] object-cover">
                <span class="font-heading font-extrabold text-[19px] tracking-tight text-[var(--text-1)]">
                    HIDDEN<span class="text-[var(--accent)]">SCAN</span>
                </span>
            </a>

            {{-- Recherche Centre (Desktop) --}}
            <div class="hidden md:flex flex-1 max-w-sm mx-4">
                <button type="button" 
                        @click="openSearch()"
                        class="w-full h-9 bg-[var(--bg-surface)] hover:bg-[var(--bg-raised)] border border-[var(--border)] hover:border-[var(--text-3)] rounded-[4px] px-3 text-caption flex items-center justify-between text-[var(--text-2)] transition cursor-pointer">
                    <span class="flex items-center gap-2">
                        <svg class="w-3.5 h-3.5 text-[var(--text-3)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        <span>Rechercher une série, un genre...</span>
                    </span>
                    <kbd class="px-1.5 py-0.5 bg-[var(--bg-raised)] border border-[var(--border)] rounded-[4px] text-[10px] font-mono text-[var(--text-3)]">/</kbd>
                </button>
            </div>

            {{-- Navigation Centrale / Liens Essentiels --}}
            <div class="hidden lg:flex items-center gap-6 text-[14px] font-medium text-[var(--text-2)]">
                <a href="{{ route('home') }}" class="hover:text-[var(--text-1)] transition {{ request()->routeIs('home') ? 'text-[var(--text-1)] font-semibold' : '' }}">
                    Accueil
                </a>
                <a href="{{ route('manga.index') }}" class="hover:text-[var(--text-1)] transition {{ request()->routeIs('manga.index') ? 'text-[var(--text-1)] font-semibold' : '' }}">
                    Catalogue
                </a>
                <a href="{{ route('planning') }}" class="hover:text-[var(--text-1)] transition {{ request()->routeIs('planning') ? 'text-[var(--text-1)] font-semibold' : '' }}">
                    Planning
                </a>
                <a href="{{ route('leaderboard') }}" class="hover:text-[var(--text-1)] transition {{ request()->routeIs('leaderboard') ? 'text-[var(--text-1)] font-semibold' : '' }}">
                    Classement
                </a>
                <a href="{{ route('manga.random') }}" class="hover:text-[var(--accent)] transition flex items-center gap-1.5 text-caption font-semibold">
                    <span>🎲</span>
                    <span>Surprends-moi</span>
                </a>
            </div>

            {{-- Actions Droite (Auth & Menus) --}}
            <div class="flex items-center gap-3 flex-shrink-0">
                
                {{-- Bouton Recherche Mobile --}}
                <button type="button" @click="openSearch()" class="md:hidden p-2 text-[var(--text-2)] hover:text-[var(--text-1)]" aria-label="Recherche">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </button>

                @auth
                    {{-- Notifications Bell --}}
                    <div class="relative" x-data="{ notifOpen: false }">
                        <button type="button" 
                                @click="notifOpen = !notifOpen" 
                                class="relative p-2 rounded-[4px] text-[var(--text-2)] hover:text-[var(--text-1)] hover:bg-[var(--bg-raised)] transition cursor-pointer"
                                aria-label="Notifications">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                            </svg>
                            <template x-if="$store?.notifications && $store.notifications.unreadCount > 0">
                                <span class="absolute top-1 right-1 w-2 h-2 rounded-full bg-[var(--accent)]"></span>
                            </template>
                        </button>

                        {{-- Dropdown Notifications --}}
                        <div x-show="notifOpen" 
                             x-cloak
                             @click.outside="notifOpen = false"
                             class="absolute right-0 mt-2 w-80 bg-[var(--bg-surface)] border border-[var(--border)] rounded-[6px] shadow-lg py-2 z-50">
                            <div class="px-3 py-2 border-b border-[var(--border)] flex items-center justify-between text-caption">
                                <span class="font-semibold text-[var(--text-1)]">Notifications</span>
                                <template x-if="$store?.notifications && $store.notifications.unreadCount > 0">
                                    <button @click="$store.notifications.markAllAsRead()" class="text-[var(--accent)] hover:underline">Tout marquer comme lu</button>
                                </template>
                            </div>
                            <div class="max-h-64 overflow-y-auto">
                                <template x-if="!$store?.notifications?.notifications || $store.notifications.notifications.length === 0">
                                    <div class="p-6 text-center text-caption text-[var(--text-3)]">
                                        Aucune notification pour le moment.
                                    </div>
                                </template>
                                <template x-for="item in ($store?.notifications?.notifications || [])" :key="item.id">
                                    <div @click="$store.notifications.handleClick(item); notifOpen = false"
                                         class="px-3 py-2 border-b border-[var(--border)] hover:bg-[var(--bg-raised)] cursor-pointer text-caption flex items-center gap-2.5">
                                        <div class="flex-1 min-w-0">
                                            <div class="font-semibold text-[var(--text-1)] truncate" x-text="item.manga_title"></div>
                                            <div class="text-[var(--text-2)]" x-text="'Ch. ' + item.chapter_number"></div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>

                    {{-- Profil Utilisateur & Menu Déroulant --}}
                    <div class="relative">
                        <button type="button" 
                                @click="userMobileMenuOpen = !userMobileMenuOpen" 
                                class="flex items-center gap-2 py-1 px-2 rounded-[4px] hover:bg-[var(--bg-raised)] border border-transparent hover:border-[var(--border)] transition cursor-pointer"
                                aria-label="Menu profil utilisateur">
                            <img src="{{ Auth::user()->avatar_url }}" alt="{{ Auth::user()->name }}" class="w-6 h-6 rounded-[4px] object-cover bg-[var(--bg-raised)] navbar-avatar-img">
                            <span class="text-caption font-semibold text-[var(--text-1)] max-w-[90px] truncate hidden sm:inline">{{ Auth::user()->name }}</span>
                            <svg class="w-3.5 h-3.5 text-[var(--text-3)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>

                        <div x-show="userMobileMenuOpen" 
                             x-cloak 
                             @click.outside="userMobileMenuOpen = false"
                             class="absolute right-0 mt-2 w-64 bg-[var(--bg-surface)] border border-[var(--border)] rounded-[6px] shadow-2xl z-50 overflow-hidden backdrop-blur-xl">
                            
                            {{-- Header Utilisateur avec avatar & informations --}}
                            <div class="px-4 py-3 border-b border-[var(--border)] bg-[var(--bg-raised)]/60 flex items-center gap-3">
                                <img src="{{ Auth::user()->avatar_url }}" alt="{{ Auth::user()->name }}" class="w-9 h-9 rounded-[4px] object-cover border border-[var(--border)] bg-[var(--bg-base)] flex-shrink-0">
                                <div class="min-w-0 flex-1">
                                    <div class="font-bold text-white text-xs truncate">
                                        {{ Auth::user()->name }}
                                    </div>
                                    <div class="text-[11px] text-[var(--text-3)] truncate mt-0.5">
                                        @if(Auth::user()->email && !str_contains(Auth::user()->email, '@anon.'))
                                            {{ Auth::user()->email }}
                                        @else
                                            Compte Anonyme
                                        @endif
                                    </div>
                                </div>
                            </div>

                            {{-- Liens du Menu avec icônes nettes et contrastées --}}
                            <div class="p-1.5 space-y-0.5 text-xs font-medium">
                                <a href="{{ route('profile.edit', ['tab' => 'overview']) }}" class="flex items-center gap-3 px-3 py-2 rounded-[4px] text-[var(--text-1)] hover:text-white hover:bg-[var(--bg-raised)] transition group">
                                    <span class="w-5 text-center text-sm">👤</span>
                                    <span class="flex-1">Mon Profil</span>
                                    <span class="text-[10px] text-[var(--text-3)] group-hover:text-white transition">→</span>
                                </a>
                                <a href="{{ route('library', ['tab' => 'rattrapage']) }}" class="flex items-center gap-3 px-3 py-2 rounded-[4px] text-[var(--accent)] hover:bg-[var(--bg-raised)] transition group font-semibold">
                                    <span class="w-5 text-center text-sm">🔥</span>
                                    <span class="flex-1">Mode Rattrapage</span>
                                    <span class="text-[9px] px-1.5 py-0.5 rounded-[4px] bg-[var(--accent)]/15 border border-[var(--accent)]/30 text-[var(--accent)] uppercase font-bold tracking-wider">Sync</span>
                                </a>
                                <a href="{{ route('library') }}" class="flex items-center gap-3 px-3 py-2 rounded-[4px] text-[var(--text-1)] hover:text-white hover:bg-[var(--bg-raised)] transition group">
                                    <span class="w-5 text-center text-sm">📚</span>
                                    <span class="flex-1">Ma Bibliothèque</span>
                                </a>
                                <a href="{{ route('profile.edit', ['tab' => 'settings']) }}" class="flex items-center gap-3 px-3 py-2 rounded-[4px] text-[var(--text-1)] hover:text-white hover:bg-[var(--bg-raised)] transition group">
                                    <span class="w-5 text-center text-sm">⚙️</span>
                                    <span class="flex-1">Paramètres</span>
                                </a>
                                @if(Auth::user()->canAccessPanel(filament()->getPanel('admin')))
                                    <a href="/admin" target="_blank" class="flex items-center gap-3 px-3 py-2 rounded-[4px] text-amber-400 hover:bg-[var(--bg-raised)] transition font-semibold">
                                        <span class="w-5 text-center text-sm">🛡️</span>
                                        <span class="flex-1">Administration</span>
                                        <span class="text-[9px] px-1 py-0.5 rounded-[4px] bg-amber-400/10 text-amber-300 font-bold">Admin</span>
                                    </a>
                                @endif
                            </div>

                            <div class="p-1.5 pt-1 border-t border-[var(--border)]">
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="w-full flex items-center gap-3 px-3 py-2 rounded-[4px] text-xs font-semibold text-[var(--accent)] hover:bg-[var(--accent)]/10 hover:text-[var(--accent-hover)] transition cursor-pointer">
                                        <span class="w-5 text-center text-sm">🚪</span>
                                        <span>Se déconnecter</span>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @else
                    {{-- Visiteur : Connexion & Inscription --}}
                    <div class="flex items-center gap-2">
                        <a href="{{ route('login') }}" class="btn-primary">
                            Se connecter
                        </a>
                        <a href="{{ route('register') }}" class="btn-secondary hidden sm:inline-flex">
                            S'inscrire
                        </a>
                    </div>
                @endauth

                @guest
                {{-- Mobile Hamburger --}}
                <button type="button" @click="mobileOpen = !mobileOpen" class="lg:hidden p-2 text-[var(--text-2)] hover:text-[var(--text-1)]" aria-label="Menu mobile" title="Menu principal">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                @endguest
            </div>
        </div>

        {{-- Mobile Drawer --}}
        <div x-show="mobileOpen" x-cloak class="lg:hidden bg-[var(--bg-surface)] border-b border-[var(--border)] px-4 py-4 space-y-2">
            <a href="{{ route('home') }}" class="block px-3 py-2 rounded-[4px] text-body text-[var(--text-2)] hover:text-[var(--text-1)] hover:bg-[var(--bg-raised)]">
                Accueil
            </a>
            <a href="{{ route('manga.index') }}" class="block px-3 py-2 rounded-[4px] text-body text-[var(--text-2)] hover:text-[var(--text-1)] hover:bg-[var(--bg-raised)]">
                Catalogue
            </a>
            <a href="{{ route('planning') }}" class="block px-3 py-2 rounded-[4px] text-body text-[var(--text-2)] hover:text-[var(--text-1)] hover:bg-[var(--bg-raised)]">
                Planning
            </a>
            <a href="{{ route('leaderboard') }}" class="block px-3 py-2 rounded-[4px] text-body text-[var(--text-2)] hover:text-[var(--text-1)] hover:bg-[var(--bg-raised)]">
                Classement
            </a>
            <a href="{{ route('library') }}" class="block px-3 py-2 rounded-[4px] text-body text-[var(--text-2)] hover:text-[var(--text-1)] hover:bg-[var(--bg-raised)]">
                Bibliothèque
            </a>
        </div>
    </nav>
    @endunless

    {{-- ═══ Search Overlay Plein Écran (Composant 9) ═══ --}}
    <div x-show="searchOverlayOpen" 
         x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-[100] bg-[rgba(10,10,10,0.96)] backdrop-blur-[12px] flex flex-col p-4 sm:p-8"
         @click.self="closeSearch()">
        
        <div class="max-w-2xl w-full mx-auto flex flex-col h-full pt-8 sm:pt-16">
            {{-- Entrée Recherche --}}
            <div class="relative flex items-center border-b border-[var(--border)] pb-4 mb-6">
                <svg class="w-6 h-6 text-[var(--text-3)] mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text" 
                       x-ref="overlayInput"
                       x-model="searchQuery" 
                       @input.debounce.250ms="performSearch()"
                       @keydown.down.prevent="selectNext()"
                       @keydown.up.prevent="selectPrev()"
                       @keydown.enter.prevent="submitSelected()"
                       placeholder="Rechercher une série, un auteur..." 
                       class="w-full bg-transparent text-[22px] sm:text-[28px] font-heading font-semibold text-[var(--text-1)] placeholder-[var(--text-3)] outline-none border-none">
                
                <button type="button" @click="closeSearch()" class="px-2.5 py-1 rounded-[4px] bg-[var(--bg-raised)] border border-[var(--border)] text-caption text-[var(--text-3)] hover:text-[var(--text-1)] transition cursor-pointer">
                    Échap
                </button>
            </div>

            {{-- Résultats Instantanés --}}
            <div class="flex-1 overflow-y-auto space-y-2 pr-2">
                <template x-if="searchLoading">
                    <div class="py-8 text-center text-caption text-[var(--text-3)]">
                        Recherche en cours...
                    </div>
                </template>

                <template x-if="!searchLoading && searchResults.length > 0">
                    <div class="space-y-1">
                        <template x-for="(item, idx) in searchResults" :key="item.id || item.slug">
                            <a :href="'/manga/' + item.slug" 
                               :class="selectedIndex === idx ? 'bg-[var(--bg-raised)] border-[var(--accent)]' : 'bg-[var(--bg-surface)] border-[var(--border)]'"
                               class="flex items-center gap-4 p-3 rounded-[6px] border hover:border-[var(--accent)] hover:bg-[var(--bg-raised)] transition text-decoration-none group">
                                <div class="w-10 h-14 bg-[var(--bg-base)] rounded-[4px] overflow-hidden flex-shrink-0">
                                    <template x-if="item.cover_image">
                                        <img :src="item.cover_image.startsWith('http') ? item.cover_image : '/storage/' + item.cover_image" class="w-full h-full object-cover">
                                    </template>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="font-heading font-semibold text-[15px] text-[var(--text-1)] group-hover:text-[var(--accent)] truncate" x-text="item.title"></div>
                                    <div class="text-caption text-[var(--text-3)]" x-text="item.type ? item.type.toUpperCase() : 'MANGA'"></div>
                                </div>
                                <div class="text-caption text-[var(--text-3)]">
                                    →
                                </div>
                            </a>
                        </template>
                    </div>
                </template>

                <template x-if="!searchLoading && searchQuery.trim().length >= 2 && searchResults.length === 0">
                    <div class="py-12 text-center">
                        <p class="text-[15px] text-[var(--text-2)] mb-3">Aucun résultat pour « <span class="text-[var(--text-1)]" x-text="searchQuery"></span> »</p>
                        <a :href="'{{ route('manga.index') }}?q=' + encodeURIComponent(searchQuery)" class="btn-secondary !text-caption">
                            Rechercher dans tout le catalogue
                        </a>
                    </div>
                </template>
            </div>
        </div>
    </div>

    {{-- ═══ Page Wrapper (1360px container or Full Width) ═══ --}}
    <main class="flex-1 w-full mx-auto relative z-10 overflow-x-hidden">
        @if($container)
            <div class="max-w-[1360px] w-full mx-auto px-4 sm:px-6 lg:px-8 {{ $hideNavbar ? '' : 'pt-20' }} pb-16">
                {{ $slot }}
            </div>
        @else
            {{ $slot }}
        @endif

        {{-- Modal de remise du Pass Secret --}}
        @if(session('new_pass_code'))
        <div x-data="{ open: true, copied: false }" x-show="open" x-cloak 
             class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/90 backdrop-blur-md">
            <div class="relative w-full max-w-md bg-[var(--bg-surface)] border border-[var(--border)] rounded-[6px] p-6 text-center shadow-xl">
                <div class="w-12 h-12 mx-auto mb-3 rounded-[4px] bg-[var(--bg-raised)] border border-[var(--border)] flex items-center justify-center text-xl text-[var(--accent)]">
                    🔑
                </div>
                <h3 class="font-heading text-h3 text-[var(--text-1)] mb-2">Bienvenue sur Hidden Scan</h3>
                <p class="text-caption text-[var(--text-2)] mb-5 leading-relaxed">
                    Votre compte anonyme est prêt. Voici votre <strong>Pass Secret</strong> unique, il remplace votre email et votre mot de passe :
                </p>

                <div class="p-3 rounded-[4px] bg-[var(--bg-base)] border border-[var(--border)] mb-4 flex items-center justify-between gap-3">
                    <span class="font-mono font-bold text-sm text-[var(--accent)] tracking-wider select-all">
                        {{ session('new_pass_code') }}
                    </span>
                    <button type="button" 
                            @click="navigator.clipboard.writeText('{{ session('new_pass_code') }}'); copied = true; setTimeout(() => copied = false, 2500)"
                            class="px-2.5 py-1 rounded-[4px] bg-[var(--bg-raised)] hover:bg-[var(--border)] text-[var(--text-1)] text-caption font-semibold transition cursor-pointer">
                        <span x-show="!copied">Copier</span>
                        <span x-show="copied" x-cloak class="text-emerald-400">Copié ✓</span>
                    </button>
                </div>

                <p class="text-caption text-[var(--text-3)] text-left mb-6">
                    ⚠️ Conservez ce code précieusement. C'est votre unique identifiant de connexion.
                </p>

                <button type="button" @click="open = false" class="btn-primary w-full">
                    Commencer à lire
                </button>
            </div>
        </div>
        @endif
    </main>

    {{-- ═══ Footer 3 Colonnes Strict (Composant 11) ═══ --}}
    @php
        $displayFooter = $showFooter ?? request()->routeIs('home', 'manga.show', 'chapter.show');
    @endphp
    @if($displayFooter)
    <footer class="border-t border-[var(--border)] bg-[var(--bg-base)] mt-auto relative z-10">
        <div class="max-w-[1280px] mx-auto px-4 sm:px-6 py-12">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-8">
                
                {{-- Colonne 1 : À Propos & Marque --}}
                <div>
                    <a href="{{ route('home') }}" class="inline-flex items-center gap-2 mb-3">
                        <img src="{{ asset('images/logo.png') }}" alt="Hidden Scan" class="w-6 h-6 rounded-[4px]">
                        <span class="font-heading font-bold text-h3 text-[var(--text-1)]">Hidden<span class="text-[var(--accent)]">Scan</span></span>
                    </a>
                    <p class="text-caption text-[var(--text-2)] leading-relaxed max-w-sm mb-4">
                        Plateforme de scantrad et de lecture de mangas, manhwas et manhuas en français. Conçue pour une lecture fluide, sans publicité intrusive.
                    </p>
                    <p class="text-caption text-[var(--text-3)]">
                        © {{ date('Y') }} Hidden Scan. Tous droits réservés.
                    </p>
                </div>

                {{-- Colonne 2 : Navigation --}}
                <div>
                    <h4 class="text-label text-[var(--text-1)] mb-3">Navigation</h4>
                    <ul class="space-y-2 text-caption">
                        <li><a href="{{ route('home') }}" class="text-[var(--text-2)] hover:text-[var(--text-1)] transition">Accueil</a></li>
                        <li><a href="{{ route('manga.index') }}" class="text-[var(--text-2)] hover:text-[var(--text-1)] transition">Catalogue</a></li>
                        <li><a href="{{ route('planning') }}" class="text-[var(--text-2)] hover:text-[var(--text-1)] transition">Planning des Sorties</a></li>
                        <li><a href="{{ route('leaderboard') }}" class="text-[var(--text-2)] hover:text-[var(--text-1)] transition">Classement des Lecteurs</a></li>
                        <li><a href="{{ route('library') }}" class="text-[var(--text-2)] hover:text-[var(--text-1)] transition">Ma Bibliothèque</a></li>
                    </ul>
                </div>

                {{-- Colonne 3 : Légal & Communauté --}}
                <div>
                    <h4 class="text-label text-[var(--text-1)] mb-3">Légal & Communauté</h4>
                    <ul class="space-y-2 text-caption">
                        <li><a href="{{ route('legal.cgu') }}" class="text-[var(--text-2)] hover:text-[var(--text-1)] transition">Conditions Générales d'Utilisation</a></li>
                        <li><a href="{{ route('legal.mentions') }}" class="text-[var(--text-2)] hover:text-[var(--text-1)] transition">Mentions Légales</a></li>
                        <li><a href="{{ route('legal.privacy') }}" class="text-[var(--text-2)] hover:text-[var(--text-1)] transition">Politique de Confidentialité</a></li>
                        <li><a href="{{ route('legal.dmca') }}" class="text-[var(--text-2)] hover:text-[var(--text-1)] transition">Notice DMCA</a></li>
                        <li>
                            <a href="https://discord.gg/sMUeFSks4" target="_blank" rel="noopener noreferrer" class="text-[var(--accent)] hover:underline font-semibold inline-flex items-center gap-1.5">
                                <span>Rejoindre notre Discord</span>
                                <span>→</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </footer>
    @endif

    {{-- ═══ Script Navbar Scroll Transparent / Dark Blur ═══ --}}
    <script>
    window.addEventListener('scroll', () => {
        const navbar = document.getElementById('main-navbar');
        if (!navbar) return;
        const scrolled = window.scrollY > 60;
        navbar.style.background = scrolled ? 'rgba(10,10,10,0.95)' : 'transparent';
        navbar.style.backdropFilter = scrolled ? 'blur(12px)' : 'none';
        navbar.style.borderBottomColor = scrolled ? 'var(--border)' : 'transparent';
        navbar.style.transition = 'all 250ms ease';
    });
    </script>

    @livewireScripts
</body>
</html>