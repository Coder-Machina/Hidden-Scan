<nav x-data="{ open: false }" class="bg-[var(--bg-surface)] border-b border-[var(--border)] sticky top-0 z-40">
    <!-- Primary Navigation Menu -->
    <div class="max-w-[1280px] mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('home') }}" class="flex items-center gap-2.5">
                        <img src="{{ asset('images/logo.png') }}" alt="Hidden Scan" class="w-8 h-8 rounded-[4px] object-cover">
                        <span class="font-heading font-extrabold text-[19px] tracking-tight text-[var(--text-1)]">
                            HIDDEN<span class="text-[var(--accent)]">SCAN</span>
                        </span>
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-6 sm:-my-px sm:ms-8 sm:flex">
                    <x-nav-link :href="route('home')" :active="request()->routeIs('home')">
                        Accueil
                    </x-nav-link>
                    <x-nav-link :href="route('manga.index')" :active="request()->routeIs('manga.index')">
                        Catalogue
                    </x-nav-link>
                    <x-nav-link :href="route('planning')" :active="request()->routeIs('planning')">
                        Planning
                    </x-nav-link>
                    <x-nav-link :href="route('library')" :active="request()->routeIs('library')">
                        Bibliothèque
                    </x-nav-link>
                </div>
            </div>

            <!-- Settings Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:ms-6">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center gap-2 px-3 py-1.5 border border-[var(--border)] text-caption font-semibold rounded-[4px] text-[var(--text-1)] bg-[var(--bg-surface)] hover:bg-[var(--bg-raised)] focus:outline-none transition ease-in-out duration-150 cursor-pointer">
                            <img src="{{ Auth::user()->avatar_url }}" alt="" class="w-6 h-6 rounded-full object-cover bg-[var(--bg-raised)] border border-[var(--border)]">
                            <div class="max-w-[100px] truncate">{{ Auth::user()->name }}</div>

                            <div class="ms-1">
                                <svg class="fill-current h-3.5 w-3.5 text-[var(--text-3)]" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">
                            Mon Profil
                        </x-dropdown-link>

                        <!-- Authentication -->
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault();
                                                this.closest('form').submit();"
                                    class="text-[var(--accent)] hover:text-[var(--accent)]">
                                Se déconnecter
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-[4px] text-[var(--text-2)] hover:text-[var(--text-1)] hover:bg-[var(--bg-raised)] focus:outline-none transition duration-150 ease-in-out">
                    <svg class="h-5 w-5" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden border-t border-[var(--border)] bg-[var(--bg-surface)]">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('home')" :active="request()->routeIs('home')">
                Accueil
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('manga.index')" :active="request()->routeIs('manga.index')">
                Catalogue
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('planning')" :active="request()->routeIs('planning')">
                Planning
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('library')" :active="request()->routeIs('library')">
                Bibliothèque
            </x-responsive-nav-link>
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-3 border-t border-[var(--border)]">
            <div class="px-4">
                <div class="font-semibold text-sm text-[var(--text-1)]">{{ Auth::user()->name }}</div>
                <div class="text-xs text-[var(--text-3)]">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">
                    Mon Profil
                </x-responsive-nav-link>

                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault();
                                        this.closest('form').submit();"
                            class="text-[var(--accent)] hover:text-[var(--accent)]">
                        Se déconnecter
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
