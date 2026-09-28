<section style="margin-top: 24px;">
    <div style="margin-bottom: 16px;">
        <h3 style="font-size: 15px; font-weight: 600; color: #f87171; display: flex; align-items: center; gap: 8px;">
            <svg style="width: 16px; height: 16px; stroke: #f87171; fill: none;" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
            Supprimer définitivement le compte
        </h3>
        <p style="font-size: 12px; color: #7070a0; margin-top: 4px;">
            Une fois votre compte supprimé, toutes vos données (profil, progression, favoris et commentaires) seront définitivement effacées.
        </p>
    </div>

    <button
        type="button"
        x-data=""
        x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
        style="background: rgba(220, 38, 38, 0.15); border: 1px solid rgba(220, 38, 38, 0.3); color: #f87171; font-size: 13px; font-weight: 600; padding: 8px 16px; border-radius: 6px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; transition: all 0.2s;"
        onmouseover="this.style.background='rgba(220, 38, 38, 0.25)'"
        onmouseout="this.style.background='rgba(220, 38, 38, 0.15)'"
    >
        <svg style="width: 14px; height: 14px; stroke: currentColor; fill: none;" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
        Supprimer mon compte
    </button>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('profile.destroy') }}" style="padding: 24px; background: #111118; border: 1px solid #1e1e2e; border-radius: 12px; display: flex; flex-direction: column; gap: 16px; color: #e8e8f0;">
            @csrf
            @method('delete')

            <div>
                <h3 style="font-size: 16px; font-weight: bold; color: #f87171; display: flex; align-items: center; gap: 8px;">
                    <svg style="width: 18px; height: 18px; stroke: #f87171; fill: none;" viewBox="0 0 24 24" stroke-width="2"><path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    Êtes-vous sûr de vouloir supprimer votre compte ?
                </h3>
                <p style="font-size: 13px; color: #7070a0; margin-top: 6px; line-height: 1.5;">
                    Cette action est irréversible. Veuillez entrer votre mot de passe pour confirmer la suppression définitive.
                </p>
            </div>

            <div>
                <label style="font-size: 12px; color: #7070a0; display: block; margin-bottom: 6px;">Mot de passe de confirmation</label>
                <input
                    type="password"
                    name="password"
                    class="input-dark"
                    placeholder="Entrez votre mot de passe"
                />
                <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-2" />
            </div>

            <div style="display: flex; align-items: center; justify-content: flex-end; gap: 10px; margin-top: 8px;">
                <button
                    type="button"
                    x-on:click="$dispatch('close')"
                    class="btn-ghost"
                >
                    Annuler
                </button>

                <button
                    type="submit"
                    class="btn-save-red"
                    style="background-color: #dc2626;"
                >
                    Confirmer la suppression
                </button>
            </div>
        </form>
    </x-modal>
</section>
