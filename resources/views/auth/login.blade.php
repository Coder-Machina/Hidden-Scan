<x-layouts.public title="Connexion — Hidden Scan">
    <div class="max-w-md mx-auto mt-6 sm:mt-10 p-6 sm:p-8 bg-[#161820] rounded-3xl shadow-2xl border border-[#252837]/60 animate-fade-in-up">
        
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
