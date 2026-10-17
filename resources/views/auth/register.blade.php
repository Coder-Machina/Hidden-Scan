<x-layouts.public title="Inscription Anonyme — Hidden Scan">
    <div class="max-w-md mx-auto mt-8 sm:mt-16 p-6 sm:p-8 bg-[var(--bg-surface)] border border-[var(--border)] rounded-[6px] shadow-sm">
        
        <div class="text-center mb-6">
            <h1 class="text-h2 font-semibold text-[var(--text-1)] mb-1">Créer un Pass Secret</h1>
            <p class="text-caption text-[var(--text-2)]">Accès immédiat sans email, sans mot de passe, 100% anonyme.</p>
        </div>

        @auth
            <div class="mb-5 p-3 rounded-[4px] bg-[var(--bg-raised)] border border-[var(--border)] text-caption text-[var(--text-2)] space-y-1.5">
                <div>
                    <span>Vous êtes actuellement connecté : <strong class="text-[var(--text-1)]">{{ Auth::user()->name }}</strong></span>
                </div>
                <div class="text-xs text-[var(--text-2)]">
                    Pass actuel : <span class="font-mono text-[var(--text-1)] font-semibold">{{ Auth::user()->pass_code ?? Auth::user()->email }}</span>
                </div>
                <div class="pt-1">
                    <a href="{{ route('profile.edit', ['tab' => 'settings']) }}" class="text-[var(--accent)] hover:underline font-semibold text-xs">
                        Gérer mon profil dans les Paramètres →
                    </a>
                </div>
            </div>
        @endauth

        <div class="space-y-4">
            <div class="p-3.5 rounded-[4px] bg-[var(--bg-base)] border border-[var(--border)] space-y-2 text-caption text-[var(--text-2)]">
                <div class="flex items-center gap-2">
                    <span class="text-emerald-400 font-bold">✓</span>
                    <span>Génération instantanée d'un identifiant secret unique</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-emerald-400 font-bold">✓</span>
                    <span>Synchronisation automatique de vos favoris et progression</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-emerald-400 font-bold">✓</span>
                    <span>Connexion multi-appareils sans jamais renseigner d'email</span>
                </div>
            </div>

            <form method="POST" action="{{ route('register.pass') }}" class="space-y-4">
                @csrf

                <div>
                    <label for="pass_name" class="text-label text-[var(--text-2)] block mb-1.5">
                        Pseudo de lecteur <span class="text-[var(--text-3)] normal-case font-normal">(Optionnel)</span>
                    </label>
                    <input id="pass_name" name="name" type="text" maxlength="30"
                           placeholder="ex: Lecteur-42, SoloReader..."
                           class="input-field text-caption">
                </div>

                <button type="submit" class="btn-primary w-full !py-2.5">
                    Générer mon Pass & Entrer
                </button>
            </form>
        </div>

        <div class="mt-6 pt-4 border-t border-[var(--border)] text-center text-caption text-[var(--text-2)]">
            <span>Vous avez déjà un Pass Secret ?</span>
            <a href="{{ route('login') }}" class="text-[var(--accent)] hover:underline font-semibold ml-1">
                Se connecter →
            </a>
        </div>

    </div>
</x-layouts.public>
