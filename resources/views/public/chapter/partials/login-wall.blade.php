<div class="w-full max-w-xl mx-auto px-4 my-8 relative z-20">
    <div class="bg-gradient-to-b from-[#181b26] to-[#0e1017] border border-amber-500/40 rounded-2xl p-6 sm:p-8 text-center shadow-2xl relative overflow-hidden backdrop-blur-md">
        {{-- Aura d'arrière-plan --}}
        <div class="absolute -top-20 -right-20 w-48 h-48 bg-amber-500/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-20 -left-20 w-48 h-48 bg-orange-600/10 rounded-full blur-3xl pointer-events-none"></div>

        <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-amber-500/15 border border-amber-500/30 text-amber-300 text-xs font-bold uppercase tracking-wider mb-4">
            <span>🔒 Aperçu Gratuit Terminé</span>
        </div>

        <h3 class="text-xl sm:text-2xl font-bold text-white mb-2 tracking-tight">
            Continuez votre lecture gratuitement
        </h3>
        <p class="text-xs sm:text-sm text-gray-300 leading-relaxed mb-6 max-w-md mx-auto">
            Vous avez lu les {{ $previewLimit }} premières pages. Rejoignez la communauté Hidden Scan ou connectez-vous pour débloquer immédiatement la suite de ce chapitre !
        </p>

        <div class="space-y-3 mb-6">
            <form method="POST" action="{{ route('register.pass') }}">
                @csrf
                <input type="hidden" name="redirect" value="{{ url()->current() }}">
                <button type="submit" 
                        class="w-full py-3.5 px-6 rounded-xl bg-gradient-to-r from-amber-500 via-amber-400 to-amber-500 hover:brightness-110 text-black font-extrabold text-sm shadow-lg shadow-amber-500/20 transition-all transform hover:-translate-y-0.5 cursor-pointer flex items-center justify-center gap-2">
                    <svg class="w-4 h-4 text-black fill-current" viewBox="0 0 20 20"><path d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z"/></svg>
                    <span>Débloquer la suite en 1 clic (Pass Gratuit)</span>
                </button>
            </form>

            <a href="{{ route('login', ['redirect' => url()->current()]) }}" 
               class="w-full py-3 px-6 rounded-xl bg-[#1e2230] hover:bg-[#282d40] text-gray-100 hover:text-white font-semibold text-sm border border-gray-700/60 transition inline-flex items-center justify-center gap-2">
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>
                <span>Déjà un compte ? Se connecter</span>
            </a>
        </div>

        {{-- Avantages membre --}}
        <div class="grid grid-cols-2 gap-2 text-left text-[11px] text-gray-400 border-t border-gray-800/80 pt-4">
            <div class="flex items-center gap-1.5">
                <span class="text-emerald-400 font-bold">✓</span>
                <span>100% Gratuit & Illimité</span>
            </div>
            <div class="flex items-center gap-1.5">
                <span class="text-emerald-400 font-bold">✓</span>
                <span>Progression sauvegardée</span>
            </div>
            <div class="flex items-center gap-1.5">
                <span class="text-emerald-400 font-bold">✓</span>
                <span>Système de Rangs & XP</span>
            </div>
            <div class="flex items-center gap-1.5">
                <span class="text-emerald-400 font-bold">✓</span>
                <span>Favoris & Alertes sorties</span>
            </div>
        </div>
    </div>
</div>
