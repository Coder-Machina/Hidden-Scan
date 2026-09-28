<x-layouts.public title="Connexion — Hidden Scan">
    <div class="max-w-md mx-auto mt-6 sm:mt-10 p-6 sm:p-8 bg-[#161820] rounded-3xl shadow-2xl border border-[#252837]/60 animate-fade-in-up"
         x-data="{ showStaff: {{ $errors->has('email') || $errors->has('password') ? 'true' : 'false' }} }">
        
        <div class="text-center mb-6">
            <div class="w-14 h-14 mx-auto mb-3 rounded-2xl bg-gradient-to-tr from-red-600 to-amber-600 flex items-center justify-center text-2xl shadow-lg shadow-red-600/30">
                🔑
            </div>
            <h1 class="font-display text-2xl font-bold text-white">Connexion</h1>
            <p class="text-gray-400 text-xs sm:text-sm mt-1.5">Entrez votre Pass secret pour retrouver vos mangas</p>
        </div>

        <x-auth-session-status class="mb-4" :status="session('status')" />

        {{-- ═══ SECTION PRINCIPALE : PASS SECRET ═══ --}}
        <form method="POST" action="{{ route('login') }}" class="space-y-4">
            @csrf

            <div>
                <label for="pass_code" class="block text-xs font-semibold text-gray-300 mb-1.5">
                    Votre Pass Secret
                </label>
                <div class="relative">
                    <input id="pass_code" name="pass_code" type="text" value="{{ old('pass_code') }}" required autofocus
                           placeholder="ex: HS-7F2A-9K3M-P8X4"
                           style="text-transform: uppercase; letter-spacing: 1px;"
                           class="w-full bg-[#11121a] border border-[#26283b] text-white font-mono placeholder-gray-500 focus:ring-2 focus:ring-red-500 focus:border-transparent rounded-xl px-4 py-3 text-sm transition outline-none shadow-inner">
                </div>
                <x-input-error :messages="$errors->get('pass_code')" class="mt-1.5 text-rose text-xs font-medium" />
            </div>

            <button type="submit" 
                    class="w-full py-3.5 px-4 bg-gradient-to-r from-red-600 to-red-700 hover:from-red-500 hover:to-red-600 text-white font-extrabold text-sm rounded-xl transition duration-200 shadow-lg shadow-red-600/30 flex items-center justify-center gap-2 hover:scale-[1.01] active:scale-[0.99] cursor-pointer">
                <span>Accéder à mon espace ⚡</span>
            </button>
        </form>

        {{-- Lien Création de Pass --}}
        <div class="mt-4 p-3 rounded-xl bg-[#0f1016] border border-[#222332] text-center">
            <span class="text-xs text-gray-400">Pas encore de Pass secret ?</span>
            <form method="POST" action="{{ route('register.pass') }}" class="inline-block ml-1">
                @csrf
                <button type="submit" class="text-xs text-red-400 font-bold hover:underline cursor-pointer">
                    Créer en 1 clic →
                </button>
            </form>
        </div>

        {{-- Séparateur --}}
        <div class="relative my-6">
            <div class="absolute inset-0 flex items-center"><div class="w-full border-t border-[#252837]"></div></div>
            <div class="relative flex justify-center text-xs">
                <span class="px-3 bg-[#161820] text-gray-500">ou</span>
            </div>
        </div>

        {{-- Toggle Connexion Classique (Admin / Staff) --}}
        <div>
            <button type="button" @click="showStaff = !showStaff" 
                    class="w-full text-center text-xs font-semibold text-gray-400 hover:text-white transition flex items-center justify-center gap-1.5">
                <span x-text="showStaff ? 'Masquer la connexion Staff' : 'Connexion Staff / Administrateur (Email)'"></span>
                <svg class="w-3.5 h-3.5 transition-transform" :class="showStaff ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>

            <div x-show="showStaff" x-cloak x-collapse class="mt-4 pt-4 border-t border-[#222332]">
                <form method="POST" action="{{ route('login') }}" class="space-y-4">
                    @csrf

                    <div>
                        <label for="email" class="block text-xs font-semibold text-gray-300 mb-1">Email Staff</label>
                        <input id="email" class="w-full bg-[#11121a] border border-[#26283b] text-white placeholder-gray-500 focus:ring-2 focus:ring-violet rounded-xl px-3.5 py-2 text-xs transition outline-none" type="email" name="email" value="{{ old('email') }}" autocomplete="username" />
                        <x-input-error :messages="$errors->get('email')" class="mt-1 text-rose text-xs" />
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label for="password" class="block text-xs font-semibold text-gray-300">Mot de passe</label>
                            @if (Route::has('password.request'))
                                <a href="{{ route('password.request') }}" class="text-[10px] text-gray-400 hover:text-white transition">Oublié ?</a>
                            @endif
                        </div>
                        <input id="password" class="w-full bg-[#11121a] border border-[#26283b] text-white placeholder-gray-500 focus:ring-2 focus:ring-violet rounded-xl px-3.5 py-2 text-xs transition outline-none" type="password" name="password" autocomplete="current-password" />
                        <x-input-error :messages="$errors->get('password')" class="mt-1 text-rose text-xs" />
                    </div>

                    <div class="flex items-center">
                        <input id="remember_me" type="checkbox" class="rounded bg-[#11121a] border-line text-red-500 focus:ring-0 cursor-pointer" name="remember">
                        <label for="remember_me" class="ml-2 text-xs text-mist cursor-pointer">Se souvenir de moi</label>
                    </div>

                    <button type="submit" class="w-full py-2.5 bg-panel-hi hover:bg-panel-hover text-white text-xs font-bold rounded-xl border border-line transition">
                        Connexion Staff
                    </button>
                </form>
            </div>
        </div>

    </div>
</x-layouts.public>
