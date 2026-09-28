<x-layouts.public title="Nouveau mot de passe — Hidden Scan">
    <div class="max-w-md mx-auto mt-6 sm:mt-10 p-6 sm:p-8 bg-[#161820] border border-[#252837]/60 rounded-3xl shadow-2xl relative overflow-hidden animate-fade-in-up">
        
        {{-- En-tête avec logo HiddenScan --}}
        <div class="text-center mb-6">
            <div class="inline-flex p-3 rounded-2xl bg-[#141420] border border-[#262638] mb-4 shadow-lg shadow-black/40">
                <img src="{{ asset('images/logo.png') }}" alt="Hidden Scan" class="w-12 h-12 rounded-xl object-cover">
            </div>
            <h1 class="font-display text-2xl font-bold text-white tracking-tight">Nouveau mot de passe</h1>
            <p class="text-[#8080a0] text-sm mt-2 leading-relaxed">
                Définissez un nouveau mot de passe sécurisé pour votre compte Hidden Scan.
            </p>
        </div>

        {{-- Formulaire --}}
        <form method="POST" action="{{ route('password.store') }}" class="space-y-4">
            @csrf

            <!-- Token de réinitialisation -->
            <input type="hidden" name="token" value="{{ $request->route('token') }}">

            <!-- Adresse Email -->
            <div>
                <label for="email" class="block text-xs font-bold text-[#a0a0c0] uppercase tracking-wider mb-1.5">Adresse Email</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-[#60608a]">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.206"/></svg>
                    </div>
                    <input 
                        id="email" 
                        class="w-full bg-[#14151e] border border-[#252837] text-white placeholder-[#505070] focus:border-[#dc2626] focus:ring-1 focus:ring-[#dc2626] rounded-xl pl-10 pr-4 py-2.5 text-sm transition outline-none shadow-inner" 
                        type="email" 
                        name="email" 
                        value="{{ old('email', $request->email) }}" 
                        required 
                        autofocus 
                        autocomplete="username" 
                    />
                </div>
                <x-input-error :messages="$errors->get('email')" class="mt-1.5 text-red-400 text-xs font-semibold" />
            </div>

            <!-- Nouveau mot de passe -->
            <div>
                <label for="password" class="block text-xs font-bold text-[#a0a0c0] uppercase tracking-wider mb-1.5">Nouveau mot de passe</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-[#60608a]">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    </div>
                    <input 
                        id="password" 
                        class="w-full bg-[#14151e] border border-[#252837] text-white placeholder-[#505070] focus:border-[#dc2626] focus:ring-1 focus:ring-[#dc2626] rounded-xl pl-10 pr-4 py-2.5 text-sm transition outline-none shadow-inner" 
                        type="password" 
                        name="password" 
                        placeholder="Minimum 8 caractères"
                        required 
                        autocomplete="new-password" 
                    />
                </div>
                <x-input-error :messages="$errors->get('password')" class="mt-1.5 text-red-400 text-xs font-semibold" />
            </div>

            <!-- Confirmation du mot de passe -->
            <div>
                <label for="password_confirmation" class="block text-xs font-bold text-[#a0a0c0] uppercase tracking-wider mb-1.5">Confirmer le mot de passe</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-[#60608a]">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    </div>
                    <input 
                        id="password_confirmation" 
                        class="w-full bg-[#14151e] border border-[#252837] text-white placeholder-[#505070] focus:border-[#dc2626] focus:ring-1 focus:ring-[#dc2626] rounded-xl pl-10 pr-4 py-2.5 text-sm transition outline-none shadow-inner" 
                        type="password" 
                        name="password_confirmation" 
                        placeholder="Répétez le mot de passe"
                        required 
                        autocomplete="new-password" 
                    />
                </div>
                <x-input-error :messages="$errors->get('password_confirmation')" class="mt-1.5 text-red-400 text-xs font-semibold" />
            </div>

            <div class="pt-2">
                <button type="submit" class="btn-primary w-full py-3 !text-sm !font-bold flex items-center justify-center gap-2 shadow-lg shadow-red-950/50">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    <span>Enregistrer mon nouveau mot de passe</span>
                </button>
            </div>
        </form>

        {{-- Lien retour --}}
        <div class="mt-6 pt-5 border-t border-[#222234] text-center text-xs">
            <a href="{{ route('login') }}" class="text-[#a0a0c0] hover:text-white transition inline-flex items-center gap-1.5 font-medium">
                <svg class="w-3.5 h-3.5 text-[#dc2626]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Retour à la page de connexion
            </a>
        </div>

    </div>
</x-layouts.public>
