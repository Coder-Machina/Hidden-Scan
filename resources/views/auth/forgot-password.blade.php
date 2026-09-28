<x-layouts.public title="Mot de passe oublié — Hidden Scan">
    <div class="max-w-md mx-auto mt-6 sm:mt-10 p-6 sm:p-8 bg-[#161820] border border-[#252837]/60 rounded-3xl shadow-2xl relative overflow-hidden animate-fade-in-up">
        
        {{-- En-tête avec logo HiddenScan --}}
        <div class="text-center mb-6">
            <div class="inline-flex p-3 rounded-2xl bg-[#141420] border border-[#262638] mb-4 shadow-lg shadow-black/40">
                <img src="{{ asset('images/logo.png') }}" alt="Hidden Scan" class="w-12 h-12 rounded-xl object-cover">
            </div>
            <h1 class="font-display text-2xl font-bold text-white tracking-tight">Mot de passe oublié ?</h1>
            <p class="text-[#8080a0] text-sm mt-2 leading-relaxed">
                Entrez l'adresse email associée à votre compte pour recevoir un lien de réinitialisation sécurisé.
            </p>
        </div>

        {{-- Statut de la session --}}
        @if (session('status'))
            <div class="mb-5 p-3.5 rounded-xl bg-emerald-950/40 border border-emerald-500/40 flex items-start gap-2.5 text-xs text-emerald-300">
                <svg class="w-4 h-4 flex-shrink-0 mt-0.5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                <span class="leading-relaxed">{{ session('status') }}</span>
            </div>
        @endif

        {{-- Lien direct de réinitialisation en mode local / dev --}}
        @if (session('reset_url'))
            <div class="mb-5 p-4 rounded-xl bg-gradient-to-r from-red-950/50 to-purple-950/50 border border-red-500/40 flex flex-col gap-2.5 shadow-lg">
                <div class="flex items-center gap-2 text-xs font-bold text-red-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    <span>Lien direct prêt pour test :</span>
                </div>
                <a href="{{ session('reset_url') }}" class="btn-primary !py-2.5 !px-4 !text-xs !font-bold flex items-center justify-center gap-2 text-center shadow-md">
                    <span>Créer mon nouveau mot de passe maintenant →</span>
                </a>
            </div>
        @endif

        {{-- Formulaire --}}
        <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
            @csrf

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
                        value="{{ old('email') }}" 
                        placeholder="exemple@domaine.com"
                        required 
                        autofocus 
                    />
                </div>
                <x-input-error :messages="$errors->get('email')" class="mt-1.5 text-red-400 text-xs font-semibold" />
            </div>

            <div class="pt-2">
                <button type="submit" class="btn-primary w-full py-3 !text-sm !font-bold flex items-center justify-center gap-2 shadow-lg shadow-red-950/50">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    <span>Envoyer le lien de réinitialisation</span>
                </button>
            </div>
        </form>

        {{-- Liens de navigation --}}
        <div class="mt-6 pt-5 border-t border-[#222234] flex flex-col sm:flex-row items-center justify-between gap-3 text-xs">
            <a href="{{ route('login') }}" class="text-[#a0a0c0] hover:text-white transition flex items-center gap-1.5 font-medium">
                <svg class="w-3.5 h-3.5 text-[#dc2626]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Retour à la connexion
            </a>
            <a href="{{ route('register') }}" class="text-[#f87171] hover:text-white transition font-semibold">
                Créer un compte
            </a>
        </div>

        {{-- Aide Discord si compte ou email perdu --}}
        <div class="mt-5 p-3 rounded-xl bg-[#141420] border border-[#222234] flex items-center gap-3">
            <div class="w-8 h-8 rounded-lg bg-[#5865F2]/20 border border-[#5865F2]/30 flex items-center justify-center flex-shrink-0 text-[#8ea1ff]">
                <svg class="w-4 h-4" viewBox="0 0 127.14 96.36" fill="currentColor"><path d="M107.7,8.07A105.15,105.15,0,0,0,81.47,0a72.06,72.06,0,0,0-3.36,6.83A97.68,97.68,0,0,0,49,6.83,72.37,72.37,0,0,0,45.64,0,105.89,105.89,0,0,0,19.39,8.09C2.79,32.65-1.71,56.6.54,80.21h0A105.73,105.73,0,0,0,32.71,96.36,77.7,77.7,0,0,0,39.6,85.25a68.42,68.42,0,0,1-10.85-5.18c.91-.66,1.8-1.34,2.66-2a75.57,75.57,0,0,0,64.32,0c.87.71,1.76,1.39,2.66,2a68.68,68.68,0,0,1-10.87,5.19,77,77,0,0,0,6.89,11.1A105.25,105.25,0,0,0,126.6,80.22h0C129.24,52.84,122.09,29.11,107.7,8.07ZM42.45,65.69C36.18,65.69,31,60,31,53s5-12.74,11.43-12.74S54,46,53.89,53,48.84,65.69,42.45,65.69Zm42.24,0C78.41,65.69,73.31,60,73.31,53s5-12.74,11.43-12.74S96.2,46,96.12,53,91.08,65.69,84.69,65.69Z"/></svg>
            </div>
            <div class="text-[11px] text-[#8080a0] leading-tight">
                <span class="text-white font-semibold">Email ou pseudo introuvable ?</span> Contactez l'équipe sur <a href="https://discord.gg/sMUeFSks4" target="_blank" class="text-[#8ea1ff] hover:underline font-bold">Discord</a> pour récupérer l'accès à votre compte.
            </div>
        </div>

    </div>
</x-layouts.public>
