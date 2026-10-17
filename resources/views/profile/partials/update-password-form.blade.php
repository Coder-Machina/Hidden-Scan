<section>
    <div class="mb-5">
        <h3 class="text-h3 font-semibold text-[var(--text-1)] flex items-center gap-2">
            Modifier le mot de passe
        </h3>
        <p class="text-caption text-[var(--text-2)] mt-1">Assurez-vous que votre compte utilise un mot de passe robuste et sécurisé.</p>
    </div>

    <form method="post" action="{{ route('password.update') }}" class="space-y-4">
        @csrf
        @method('put')

        <div>
            <label class="text-label text-[var(--text-2)] block mb-1.5">Mot de passe actuel</label>
            <input type="password" name="current_password" class="input-field text-caption" placeholder="••••••••" autocomplete="current-password">
            <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-1" />
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="text-label text-[var(--text-2)] block mb-1.5">Nouveau mot de passe</label>
                <input type="password" name="password" class="input-field text-caption" placeholder="Minimum 8 caractères" autocomplete="new-password">
                <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-1" />
            </div>

            <div>
                <label class="text-label text-[var(--text-2)] block mb-1.5">Confirmer le mot de passe</label>
                <input type="password" name="password_confirmation" class="input-field text-caption" placeholder="••••••••" autocomplete="new-password">
                <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="mt-1" />
            </div>
        </div>

        <div class="flex items-center justify-between pt-2">
            <div>
                @if (session('status') === 'password-updated')
                    <span class="text-caption font-semibold text-emerald-400 inline-flex items-center gap-1.5">
                        <x-lucide name="check" class="w-4 h-4 stroke-[3]" />
                        <span>Mot de passe mis à jour !</span>
                    </span>
                @endif
            </div>
            <button type="submit" class="btn-primary">Mettre à jour le mot de passe</button>
        </div>
    </form>
</section>
