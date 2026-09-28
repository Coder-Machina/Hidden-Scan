<section>
    <div style="margin-bottom: 20px;">
        <h3 style="font-size: 15px; font-weight: 600; color: #e8e8f0; display: flex; align-items: center; gap: 8px;">
            <svg style="width: 16px; height: 16px; stroke: #dc2626; fill: none;" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
            Modifier le mot de passe
        </h3>
        <p style="font-size: 12px; color: #7070a0; margin-top: 4px;">Assurez-vous que votre compte utilise un mot de passe robuste et sécurisé.</p>
    </div>

    <form method="post" action="{{ route('password.update') }}" style="display: flex; flex-direction: column; gap: 16px;">
        @csrf
        @method('put')

        <div>
            <label style="font-size: 12px; color: #7070a0; display: block; margin-bottom: 6px;">Mot de passe actuel</label>
            <input type="password" name="current_password" class="input-dark" placeholder="••••••••" autocomplete="current-password">
            <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-2" />
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px;">
            <div>
                <label style="font-size: 12px; color: #7070a0; display: block; margin-bottom: 6px;">Nouveau mot de passe</label>
                <input type="password" name="password" class="input-dark" placeholder="Minimum 8 caractères" autocomplete="new-password">
                <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-2" />
            </div>

            <div>
                <label style="font-size: 12px; color: #7070a0; display: block; margin-bottom: 6px;">Confirmer le mot de passe</label>
                <input type="password" name="password_confirmation" class="input-dark" placeholder="••••••••" autocomplete="new-password">
                <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="mt-2" />
            </div>
        </div>

        <div style="display: flex; align-items: center; justify-content: space-between; margin-top: 8px;">
            <div>
                @if (session('status') === 'password-updated')
                    <span style="font-size: 13px; color: #10b981; font-weight: 600;">✓ Mot de passe mis à jour !</span>
                @endif
            </div>
            <button type="submit" class="btn-save-red">Mettre à jour le mot de passe</button>
        </div>
    </form>
</section>
