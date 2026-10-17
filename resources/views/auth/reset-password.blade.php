<x-layouts.public title="Nouveau mot de passe — Hidden Scan">
    <div class="max-w-md mx-auto mt-8 sm:mt-16 p-6 sm:p-8 bg-[var(--bg-surface)] border border-[var(--border)] rounded-[6px] shadow-sm">
        
        <div class="text-center mb-6">
            <h1 class="text-h2 font-semibold text-[var(--text-1)] mb-1">Nouveau mot de passe</h1>
            <p class="text-caption text-[var(--text-2)]">
                Définissez un nouveau mot de passe sécurisé pour votre compte.
            </p>
        </div>

        <form method="POST" action="{{ route('password.store') }}" class="space-y-4">
            @csrf

            <input type="hidden" name="token" value="{{ $request->route('token') }}">

            <div>
                <label for="email" class="text-label text-[var(--text-2)] block mb-1.5">Adresse Email</label>
                <input id="email" type="email" name="email" value="{{ old('email', $request->email) }}" required autofocus autocomplete="username"
                       class="input-field text-caption">
                <x-input-error :messages="$errors->get('email')" class="mt-1 text-xs text-red-400" />
            </div>

            <div>
                <label for="password" class="text-label text-[var(--text-2)] block mb-1.5">Nouveau mot de passe</label>
                <input id="password" type="password" name="password" required autocomplete="new-password"
                       placeholder="Minimum 8 caractères"
                       class="input-field text-caption">
                <x-input-error :messages="$errors->get('password')" class="mt-1 text-xs text-red-400" />
            </div>

            <div>
                <label for="password_confirmation" class="text-label text-[var(--text-2)] block mb-1.5">Confirmer le mot de passe</label>
                <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password"
                       class="input-field text-caption">
                <x-input-error :messages="$errors->get('password_confirmation')" class="mt-1 text-xs text-red-400" />
            </div>

            <button type="submit" class="btn-primary w-full !py-2.5">
                Enregistrer mon nouveau mot de passe
            </button>
        </form>

    </div>
</x-layouts.public>
