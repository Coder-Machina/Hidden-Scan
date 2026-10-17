<x-layouts.public title="Connexion — Hidden Scan">
    <div class="max-w-md mx-auto mt-8 sm:mt-16 p-6 sm:p-8 bg-[var(--bg-surface)] border border-[var(--border)] rounded-[6px] shadow-sm"
         x-data="{
             savedPass: '',
             savedName: '',
             savedAvatar: '',
             init() {
                 if (window.HiddenScan) {
                     const p = window.HiddenScan.getProfile();
                     if (p && p.pass_code) {
                         this.savedPass = p.pass_code;
                         this.savedName = p.name || 'Mon Profil';
                         this.savedAvatar = p.avatar_url || p.avatar || '';
                         const input = document.getElementById('pass_code');
                         if (input && !input.value) {
                             input.value = p.pass_code;
                         }
                     }
                 }
             },
             quickLogin() {
                 const input = document.getElementById('pass_code');
                 if (input) input.value = this.savedPass;
                 this.$refs.loginForm.submit();
             }
         }">
        
        <div class="text-center mb-6">
            <h1 class="text-h2 font-semibold text-[var(--text-1)] mb-1">Connexion</h1>
            <p class="text-caption text-[var(--text-2)]">Saisissez votre Pass Secret pour accéder à votre bibliothèque et vos favoris</p>
        </div>

        <x-auth-session-status class="mb-4" :status="session('status')" />

        {{-- Reprise rapide si profil local sauvegardé --}}
        <template x-if="savedPass">
            <div class="mb-5 p-3 rounded-[4px] bg-[var(--bg-raised)] border border-[var(--border)] flex items-center justify-between gap-3">
                <div class="flex items-center gap-2.5 min-w-0">
                    <template x-if="savedAvatar">
                        <img :src="savedAvatar" class="w-8 h-8 rounded-[4px] object-cover bg-[var(--bg-base)]" alt="">
                    </template>
                    <div class="min-w-0">
                        <div class="text-[11px] text-[var(--text-3)] uppercase font-semibold">Compte mémorisé</div>
                        <div class="text-caption font-semibold text-[var(--text-1)] truncate" x-text="savedName"></div>
                    </div>
                </div>
                <button type="button" @click="quickLogin()" class="btn-primary !py-1 !px-3 !text-caption cursor-pointer">
                    Entrer
                </button>
            </div>
        </template>

        <form x-ref="loginForm" method="POST" action="{{ route('login') }}" class="space-y-4">
            @csrf

            <div>
                <label for="pass_code" class="text-label text-[var(--text-2)] block mb-1.5">
                    Votre Pass Secret
                </label>
                <input id="pass_code" name="pass_code" type="text" value="{{ old('pass_code') }}" autofocus
                       placeholder="HS-••••-••••-••••"
                       class="input-field font-mono uppercase tracking-wider text-caption">
                <x-input-error :messages="$errors->get('pass_code')" class="mt-1 text-xs text-red-400" />
            </div>

            <button type="submit" class="btn-primary w-full !py-2.5">
                Accéder à mon espace
            </button>
        </form>

        <div class="mt-6 pt-4 border-t border-[var(--border)] text-center text-caption text-[var(--text-2)]">
            <span>Vous n'avez pas encore de Pass Secret ?</span>
            <form method="POST" action="{{ route('register.pass') }}" class="inline ml-1">
                @csrf
                <button type="submit" class="text-[var(--accent)] hover:underline font-semibold cursor-pointer">
                    Créer en 1 clic →
                </button>
            </form>
        </div>

    </div>
</x-layouts.public>
