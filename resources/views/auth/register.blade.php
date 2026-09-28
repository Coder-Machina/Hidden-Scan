<x-layouts.public title="Inscription Anonyme — Hidden Scan">
    <div class="max-w-lg mx-auto mt-6 sm:mt-10 p-6 sm:p-8 bg-[#161820] rounded-3xl shadow-2xl border border-[#252837]/60 animate-fade-in-up" 
         x-data="{ showClassic: false }">
        
        {{-- En-tête --}}
        <div class="text-center mb-6">
            <div class="w-14 h-14 mx-auto mb-3 rounded-2xl bg-gradient-to-tr from-red-600 to-amber-600 flex items-center justify-center text-2xl shadow-lg shadow-red-600/30">
                🛡️
            </div>
            <h1 class="font-display text-2xl sm:text-3xl font-extrabold text-white">Compte 100% Anonyme</h1>
            <p class="text-gray-400 text-xs sm:text-sm mt-1.5 max-w-sm mx-auto">
                Pas d'email requis. Pas de mot de passe à mémoriser. Zéro pistage publicitaire.
            </p>
        </div>

        {{-- ═══ SECTION PRINCIPALE : PASS SECRET (1 CLIC) ═══ --}}
        <div class="space-y-4">
            {{-- Avantages --}}
            <div class="p-4 rounded-2xl bg-[#0f1016] border border-[#222332] space-y-2.5">
                <div class="flex items-center gap-3 text-xs text-gray-300">
                    <span class="w-6 h-6 rounded-lg bg-green-500/20 text-green-400 flex items-center justify-center font-bold text-xs flex-shrink-0">✓</span>
                    <span><strong>1 Clic suffit</strong> : Un Pass Secret unique vous est attribué immédiatement.</span>
                </div>
                <div class="flex items-center gap-3 text-xs text-gray-300">
                    <span class="w-6 h-6 rounded-lg bg-green-500/20 text-green-400 flex items-center justify-center font-bold text-xs flex-shrink-0">✓</span>
                    <span><strong>Favoris & Historique</strong> sauvegardés et synchronisés dans votre espace.</span>
                </div>
                <div class="flex items-center gap-3 text-xs text-gray-300">
                    <span class="w-6 h-6 rounded-lg bg-green-500/20 text-green-400 flex items-center justify-center font-bold text-xs flex-shrink-0">✓</span>
                    <span><strong>Multi-appareils</strong> : Tapez votre Pass sur smartphone ou PC pour vous reconnecter.</span>
                </div>
            </div>

            {{-- Formulaire Création Pass --}}
            <form method="POST" action="{{ route('register.pass') }}" class="space-y-4">
                @csrf

                <div>
                    <label for="pass_name" class="block text-xs font-semibold text-gray-300 mb-1.5">
                        Pseudo de lecteur <span class="text-gray-500 font-normal">(Optionnel, modifiable à tout moment)</span>
                    </label>
                    <div class="relative">
                        <input id="pass_name" name="name" type="text" maxlength="30"
                               placeholder="ex: Shadow, SoloReader..."
                               class="w-full bg-[#11121a] border border-[#26283b] text-white placeholder-gray-500 focus:ring-2 focus:ring-red-500 focus:border-transparent rounded-xl px-4 py-2.5 text-sm transition outline-none">
                    </div>
                </div>

                <button type="submit" 
                        class="w-full py-3.5 px-4 bg-gradient-to-r from-red-600 to-red-700 hover:from-red-500 hover:to-red-600 text-white font-extrabold text-sm rounded-xl transition duration-200 shadow-lg shadow-red-600/30 flex items-center justify-center gap-2 hover:scale-[1.01] active:scale-[0.99] cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    <span>Générer mon Pass Secret & Entrer</span>
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

        {{-- Toggle Inscription Classique (Staff / Admin / Optionnel) --}}
        <div>
            <button type="button" @click="showClassic = !showClassic" 
                    class="w-full text-center text-xs font-semibold text-gray-400 hover:text-white transition flex items-center justify-center gap-1.5">
                <span x-text="showClassic ? 'Masquer l\'inscription classique' : 'Créer un compte classique avec Email & Mot de passe'"></span>
                <svg class="w-3.5 h-3.5 transition-transform" :class="showClassic ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>

            <div x-show="showClassic" x-cloak x-collapse class="mt-4 pt-4 border-t border-[#222332]">
                <form method="POST" action="{{ route('register') }}" class="space-y-4">
                    @csrf

                    <div>
                        <label for="name" class="block text-xs font-semibold text-gray-300 mb-1">Pseudo</label>
                        <input id="name" class="w-full bg-[#11121a] border border-[#26283b] text-white placeholder-gray-500 focus:ring-2 focus:ring-violet rounded-xl px-3.5 py-2 text-xs transition outline-none" type="text" name="name" value="{{ old('name') }}" placeholder="Votre pseudo" />
                        <x-input-error :messages="$errors->get('name')" class="mt-1 text-rose text-xs" />
                    </div>

                    <div>
                        <label for="email" class="block text-xs font-semibold text-gray-300 mb-1">Adresse Email</label>
                        <input id="email" class="w-full bg-[#11121a] border border-[#26283b] text-white placeholder-gray-500 focus:ring-2 focus:ring-violet rounded-xl px-3.5 py-2 text-xs transition outline-none" type="email" name="email" value="{{ old('email') }}" placeholder="admin@hidden-scan.com" />
                        <x-input-error :messages="$errors->get('email')" class="mt-1 text-rose text-xs" />
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label for="password" class="block text-xs font-semibold text-gray-300 mb-1">Mot de passe</label>
                            <input id="password" class="w-full bg-[#11121a] border border-[#26283b] text-white placeholder-gray-500 focus:ring-2 focus:ring-violet rounded-xl px-3.5 py-2 text-xs transition outline-none" type="password" name="password" placeholder="••••••••" />
                            <x-input-error :messages="$errors->get('password')" class="mt-1 text-rose text-xs" />
                        </div>
                        <div>
                            <label for="password_confirmation" class="block text-xs font-semibold text-gray-300 mb-1">Confirmation</label>
                            <input id="password_confirmation" class="w-full bg-[#11121a] border border-[#26283b] text-white placeholder-gray-500 focus:ring-2 focus:ring-violet rounded-xl px-3.5 py-2 text-xs transition outline-none" type="password" name="password_confirmation" placeholder="••••••••" />
                        </div>
                    </div>

                    <button type="submit" class="w-full py-2.5 bg-panel-hi hover:bg-panel-hover text-white text-xs font-bold rounded-xl border border-line transition">
                        Créer un compte classique
                    </button>
                </form>
            </div>
        </div>

        {{-- Lien Connexion --}}
        <p class="mt-6 text-center text-xs text-gray-400">
            Vous avez déjà un Pass ou un compte ? 
            <a href="{{ route('login') }}" class="text-red-400 font-bold hover:underline transition ml-1">Se connecter</a>
        </p>
    </div>
</x-layouts.public>
