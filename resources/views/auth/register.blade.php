<x-layouts.public title="Inscription Anonyme — Hidden Scan">
    <div class="max-w-lg mx-auto mt-6 sm:mt-10 p-6 sm:p-8 bg-[#161820] rounded-3xl shadow-2xl border border-[#252837]/60 animate-fade-in-up">
        
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
            @auth
            <div class="p-4 rounded-2xl bg-amber-500/10 border border-amber-500/30 text-amber-200 text-xs space-y-2">
                <div class="flex items-center gap-2 font-bold text-amber-300">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span>Vous êtes actuellement connecté : <strong>{{ Auth::user()->name }}</strong></span>
                </div>
                <p class="text-amber-200/80 leading-relaxed">
                    Si vous cliquez sur « Générer mon Pass Secret », vous serez déconnecté de ce compte pour créer un <strong>nouveau profil vierge</strong>. Pensez à conserver votre pass actuel si vous souhaitez y revenir :
                    <span class="inline-block mt-1 font-mono font-bold text-white bg-black/50 px-2 py-0.5 rounded border border-amber-500/30">{{ Auth::user()->pass_code ?? Auth::user()->email }}</span>
                </p>
                <div class="pt-1">
                    <a href="{{ route('profile.edit', ['tab' => 'settings']) }}" class="text-amber-400 hover:text-white underline font-semibold transition">
                        Gérer mon pass actuel dans mes Paramètres →
                    </a>
                </div>
            </div>
            @endauth

            @guest
            <div x-data="{
                hasSaved: false,
                savedName: '',
                savedPass: '',
                init() {
                    if (window.HiddenScan) {
                        const p = window.HiddenScan.getProfile();
                        if (p && p.pass_code) {
                            this.hasSaved = true;
                            this.savedName = p.name || 'Mon Profil';
                            this.savedPass = p.pass_code;
                        }
                    }
                }
            }" x-show="hasSaved" x-cloak class="p-4 rounded-2xl bg-blue-500/10 border border-blue-500/30 text-blue-200 text-xs space-y-2 animate-fade-in">
                <div class="flex items-center gap-2 font-bold text-blue-300">
                    <span>💡 Profil existant détecté sur cet appareil : <strong x-text="savedName"></strong></span>
                </div>
                <p class="text-blue-200/80 leading-relaxed">
                    Vous possédez déjà le pass <span class="font-mono font-bold text-white bg-black/40 px-1.5 py-0.5 rounded" x-text="savedPass"></span>. Si vous souhaitez retrouver vos lectures et vos personnalisations :
                </p>
                <div>
                    <a href="{{ route('login') }}" class="inline-flex items-center gap-1.5 text-blue-400 hover:text-white font-bold underline transition">
                        Se reconnecter avec ce pass →
                    </a>
                </div>
            </div>
            @endguest

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

        {{-- Lien Connexion --}}
        <p class="mt-6 text-center text-xs text-gray-400">
            Vous avez déjà un Pass Secret ? 
            <a href="{{ route('login') }}" class="text-red-400 font-bold hover:underline transition ml-1">Se connecter</a>
        </p>
    </div>
</x-layouts.public>
