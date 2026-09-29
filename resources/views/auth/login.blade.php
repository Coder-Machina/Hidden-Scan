<x-layouts.public title="Connexion — Hidden Scan">
    <div class="max-w-md mx-auto mt-6 sm:mt-10 p-6 sm:p-8 bg-[#161820] rounded-3xl shadow-2xl border border-[#252837]/60 animate-fade-in-up"
         x-data="{
             savedPass: '',
             savedName: '',
             savedAvatar: '',
             init() {
                 if (window.HiddenScan) {
                     const p = window.HiddenScan.getProfile();
                     if (p && p.pass_code) {
                         this.savedPass = p.pass_code;
                         this.savedName = p.name || 'Mon Profil';
                         this.savedAvatar = p.avatar_url || p.avatar || '';
                         const input = document.getElementById('pass_code');
                         if (input && !input.value) {
                             input.value = p.pass_code;
                         }
                     }
                 }
             },
             quickLogin() {
                 const input = document.getElementById('pass_code');
                 if (input) input.value = this.savedPass;
                 this.$refs.loginForm.submit();
             }
         }">
        
        <div class="text-center mb-6">
            <div class="w-14 h-14 mx-auto mb-3 rounded-2xl bg-gradient-to-tr from-red-600 to-amber-600 flex items-center justify-center text-2xl shadow-lg shadow-red-600/30">
                🔑
            </div>
            <h1 class="font-display text-2xl font-bold text-white">Connexion</h1>
            <p class="text-gray-400 text-xs sm:text-sm mt-1.5">Entrez votre Pass secret pour retrouver votre compte et vos favoris</p>
        </div>

        <x-auth-session-status class="mb-4" :status="session('status')" />

        {{-- Carte de reprise en 1 clic si un profil est détecté localement --}}
        <template x-if="savedPass">
            <div class="mb-5 p-3.5 rounded-2xl bg-[#1e2029] border border-red-500/30 flex items-center justify-between gap-3 shadow-lg">
                <div class="flex items-center gap-3 min-w-0">
                    <template x-if="savedAvatar">
                        <img :src="savedAvatar" class="w-10 h-10 rounded-full object-cover border-2 border-red-500 flex-shrink-0" alt="Avatar">
                    </template>
                    <template x-if="!savedAvatar">
                        <div class="w-10 h-10 rounded-full bg-red-600/20 text-red-400 font-extrabold flex items-center justify-center text-sm border-2 border-red-500/40 flex-shrink-0" x-text="savedName.slice(0, 2).toUpperCase()"></div>
                    </template>
                    <div class="min-w-0">
                        <div class="text-xs text-gray-400">Reprendre ce compte :</div>
                        <div class="text-sm font-extrabold text-white truncate" x-text="savedName"></div>
                    </div>
                </div>
                <button type="button" @click="quickLogin()" 
                        class="px-3.5 py-2 bg-gradient-to-r from-red-600 to-red-700 hover:from-red-500 hover:to-red-600 text-white font-extrabold text-xs rounded-xl shadow-md transition flex items-center gap-1.5 flex-shrink-0 cursor-pointer hover:scale-[1.02] active:scale-[0.98]">
                    <span>Entrer ⚡</span>
                </button>
            </div>
        </template>

        <form x-ref="loginForm" method="POST" action="{{ route('login') }}" class="space-y-4">
            @csrf

            <div>
                <label for="pass_code" class="block text-xs font-semibold text-gray-300 mb-1.5">
                    Votre Pass Secret
                </label>
                <div class="relative">
                    <input id="pass_code" name="pass_code" type="text" value="{{ old('pass_code') }}" autofocus
                           placeholder="HS-••••-••••-••••"
                           style="text-transform: uppercase; letter-spacing: 1.5px;"
                           class="w-full bg-[#11121a] border border-[#26283b] text-white font-mono placeholder-gray-600 focus:ring-2 focus:ring-red-500 focus:border-transparent rounded-xl px-4 py-3 text-sm transition outline-none shadow-inner">
                </div>
                <x-input-error :messages="$errors->get('pass_code')" class="mt-1.5 text-rose text-xs font-medium" />
            </div>

            <button type="submit" 
                    class="w-full py-3.5 px-4 bg-gradient-to-r from-red-600 to-red-700 hover:from-red-500 hover:to-red-600 text-white font-extrabold text-sm rounded-xl transition duration-200 shadow-lg shadow-red-600/30 flex items-center justify-center gap-2 hover:scale-[1.01] active:scale-[0.99] cursor-pointer">
                <span>Accéder à mon espace ⚡</span>
            </button>
        </form>

        {{-- Lien Création de Pass --}}
        <div class="mt-5 p-3.5 rounded-xl bg-[#0f1016] border border-[#222332] text-center">
            <span class="text-xs text-gray-400">Pas encore de Pass secret ?</span>
            <form method="POST" action="{{ route('register.pass') }}" class="inline-block ml-1">
                @csrf
                <button type="submit" class="text-xs text-red-400 font-bold hover:underline cursor-pointer">
                    Créer en 1 clic →
                </button>
            </form>
        </div>

    </div>
</x-layouts.public>
