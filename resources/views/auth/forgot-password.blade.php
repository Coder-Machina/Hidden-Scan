<x-layouts.public title="Mot de passe oublié — Hidden Scan">
    <div class="max-w-md mx-auto mt-8 sm:mt-16 p-6 sm:p-8 bg-[var(--bg-surface)] border border-[var(--border)] rounded-[6px] shadow-sm">
        
        <div class="text-center mb-6">
            <h1 class="text-h2 font-semibold text-[var(--text-1)] mb-1">Mot de passe oublié ?</h1>
            <p class="text-caption text-[var(--text-2)]">
                Entrez votre adresse email pour recevoir un lien de réinitialisation sécurisé.
            </p>
        </div>

        @if (session('status'))
            <div class="mb-5 p-3 rounded-[4px] bg-[var(--bg-raised)] border border-[var(--border)] text-caption text-emerald-400">
                {{ session('status') }}
            </div>
        @endif

        @if (session('reset_url'))
            <div class="mb-5 p-3 rounded-[4px] bg-[var(--bg-raised)] border border-[var(--border)] text-caption">
                <a href="{{ session('reset_url') }}" class="btn-primary w-full text-center">
                    Créer mon nouveau mot de passe →
                </a>
            </div>
        @endif

        <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
            @csrf

            <div>
                <label for="email" class="text-label text-[var(--text-2)] block mb-1.5">Adresse Email</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                       placeholder="exemple@domaine.com"
                       class="input-field text-caption">
                <x-input-error :messages="$errors->get('email')" class="mt-1 text-xs text-red-400" />
            </div>

            <button type="submit" class="btn-primary w-full !py-2.5">
                Envoyer le lien de réinitialisation
            </button>
        </form>

        <div class="mt-6 pt-4 border-t border-[var(--border)] flex items-center justify-between text-caption text-[var(--text-2)]">
            <a href="{{ route('login') }}" class="hover:text-[var(--text-1)] transition">
                ← Connexion
            </a>
            <a href="{{ route('register') }}" class="text-[var(--accent)] hover:underline font-semibold">
                Créer un compte
            </a>
        </div>

        {{-- Aide Discord --}}
        <div class="mt-5 p-3 rounded-[4px] bg-[var(--bg-raised)] border border-[var(--border)] flex items-center gap-3 text-caption text-[var(--text-2)]">
            <span class="text-base text-[var(--accent)]">💬</span>
            <div class="text-[12px] leading-snug">
                <span class="text-[var(--text-1)] font-semibold">Besoin d'aide ?</span> Contactez l'équipe sur <a href="https://discord.gg/sMUeFSks4" target="_blank" rel="noopener noreferrer" class="text-[var(--accent)] hover:underline font-semibold">Discord</a>.
            </div>
        </div>

    </div>
</x-layouts.public>
