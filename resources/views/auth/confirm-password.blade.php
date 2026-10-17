<x-layouts.public title="Confirmer le mot de passe — Hidden Scan">
    <div class="max-w-md mx-auto mt-8 sm:mt-16 p-6 sm:p-8 bg-[var(--bg-surface)] border border-[var(--border)] rounded-[6px] shadow-sm">
        
        <div class="text-center mb-6">
            <h1 class="text-h2 font-semibold text-[var(--text-1)] mb-1">Zone sécurisée</h1>
            <p class="text-caption text-[var(--text-2)]">
                Veuillez confirmer votre mot de passe pour continuer.
            </p>
        </div>

        <form method="POST" action="{{ route('password.confirm') }}" class="space-y-4">
            @csrf

            <div>
                <label for="password" class="text-label text-[var(--text-2)] block mb-1.5">Mot de passe</label>
                <input id="password" type="password" name="password" placeholder="••••••••" required autocomplete="current-password" autofocus
                       class="input-field text-caption">
                <x-input-error :messages="$errors->get('password')" class="mt-1 text-xs text-red-400" />
            </div>

            <button type="submit" class="btn-primary w-full !py-2.5">
                Confirmer mon mot de passe
            </button>
        </form>

    </div>
</x-layouts.public>
