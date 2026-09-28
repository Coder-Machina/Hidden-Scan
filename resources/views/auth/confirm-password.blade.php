<x-layouts.public title="Confirmer le mot de passe — Hidden Scan">
    <div class="max-w-md mx-auto mt-6 sm:mt-10 p-6 sm:p-8 bg-[#161820] border border-[#252837]/60 rounded-3xl shadow-2xl relative overflow-hidden animate-fade-in-up">
        
        <div class="text-center mb-6">
            <div class="inline-flex p-3 rounded-2xl bg-[#141420] border border-[#262638] mb-4 shadow-lg shadow-black/40">
                <img src="{{ asset('images/logo.png') }}" alt="Hidden Scan" class="w-12 h-12 rounded-xl object-cover">
            </div>
            <h1 class="font-display text-2xl font-bold text-white tracking-tight">Zone sécurisée</h1>
            <p class="text-[#8080a0] text-sm mt-2 leading-relaxed">
                Il s'agit d'une zone protégée du site. Veuillez confirmer votre mot de passe pour continuer.
            </p>
        </div>

        <form method="POST" action="{{ route('password.confirm') }}" class="space-y-4">
            @csrf

            <!-- Mot de passe -->
            <div>
                <label for="password" class="block text-xs font-bold text-[#a0a0c0] uppercase tracking-wider mb-1.5">Mot de passe</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-[#60608a]">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    </div>
                    <input 
                        id="password" 
                        class="w-full bg-[#14151e] border border-[#252837] text-white placeholder-[#505070] focus:border-[#dc2626] focus:ring-1 focus:ring-[#dc2626] rounded-xl pl-10 pr-4 py-2.5 text-sm transition outline-none shadow-inner" 
                        type="password" 
                        name="password" 
                        placeholder="••••••••"
                        required 
                        autocomplete="current-password" 
                        autofocus
                    />
                </div>
                <x-input-error :messages="$errors->get('password')" class="mt-1.5 text-red-400 text-xs font-semibold" />
            </div>

            <div class="pt-2">
                <button type="submit" class="btn-primary w-full py-3 !text-sm !font-bold flex items-center justify-center gap-2 shadow-lg shadow-red-950/50">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    <span>Confirmer mon mot de passe</span>
                </button>
            </div>
        </form>

    </div>
</x-layouts.public>
