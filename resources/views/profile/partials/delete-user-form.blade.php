<section class="mt-6 pt-6 border-t border-[var(--border)]">
    <div class="mb-4">
        <h3 class="text-h3 font-semibold text-[var(--accent)] flex items-center gap-2">
            Supprimer définitivement le compte
        </h3>
        <p class="text-caption text-[var(--text-2)] mt-1">
            Une fois votre compte supprimé, toutes vos données (profil, progression, favoris et commentaires) seront définitivement effacées.
        </p>
    </div>

    <button
        type="button"
        x-data=""
        x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
        class="btn-secondary !text-[var(--accent)] !border-[var(--accent)] hover:!bg-[var(--accent)] hover:!text-white"
    >
        Supprimer mon compte
    </button>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('profile.destroy') }}" class="p-6 bg-[var(--bg-surface)] border border-[var(--border)] rounded-[6px] flex flex-col gap-4 text-[var(--text-1)]">
            @csrf
            @method('delete')

            <div>
                <h3 class="text-h3 font-bold text-[var(--accent)] flex items-center gap-2">
                    Êtes-vous sûr de vouloir supprimer votre compte ?
                </h3>
                <p class="text-caption text-[var(--text-2)] mt-1.5 leading-relaxed">
                    @if(Auth::user()->pass_code)
                        Cette action est irréversible. Vos favoris, votre historique de lecture et votre Pass Secret seront immédiatement effacés.
                    @else
                        Cette action est irréversible. Veuillez entrer votre mot de passe pour confirmer la suppression définitive.
                    @endif
                </p>
            </div>

            @if(!Auth::user()->pass_code)
                <div>
                    <label class="text-label text-[var(--text-2)] block mb-1.5">Mot de passe de confirmation</label>
                    <input
                        type="password"
                        name="password"
                        class="input-field text-caption"
                        placeholder="Entrez votre mot de passe"
                    />
                    <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-1" />
                </div>
            @endif

            <div class="flex items-center justify-flex-end gap-3 pt-2">
                <button
                    type="button"
                    x-on:click="$dispatch('close')"
                    class="btn-secondary"
                >
                    Annuler
                </button>

                <button
                    type="submit"
                    class="btn-primary !bg-[var(--accent)] hover:!bg-[var(--accent-hover)]"
                >
                    Confirmer la suppression
                </button>
            </div>
        </form>
    </x-modal>
</section>
