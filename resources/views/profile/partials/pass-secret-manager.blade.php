<section x-data="{ showPass: false, copied: false }">
    <div class="mb-5">
        <h3 class="text-h3 font-semibold text-[var(--text-1)] flex items-center gap-2.5">
            <span class="w-8 h-8 rounded-[4px] bg-[var(--bg-raised)] border border-[var(--border)] text-[var(--accent)] flex items-center justify-center">
                <x-lucide name="key" class="w-4 h-4 text-[var(--accent)]" />
            </span>
            <span>Mon Pass Secret (Connexion Anonyme)</span>
        </h3>
        <p class="text-caption text-[var(--text-2)] mt-1.5 leading-relaxed">
            Votre compte est 100% anonyme. Ce code est votre unique identifiant de connexion. Ne le partagez jamais avec des tiers.
        </p>
    </div>

    @if (session('status') === 'pass-regenerated')
        <div class="mb-5 p-3 rounded-[4px] bg-[var(--bg-raised)] border border-[var(--border)] text-caption font-semibold text-emerald-400 flex items-center gap-2">
            <x-lucide name="check" class="w-4 h-4 text-emerald-400 shrink-0 stroke-[3]" />
            <span>Nouveau Pass généré avec succès ! Votre ancien Pass a été révoqué. Pensez à conserver le nouveau en lieu sûr.</span>
        </div>
    @endif

    {{-- Boîte Spoiler du Pass Secret --}}
    <div class="p-5 rounded-[6px] bg-[var(--bg-surface)] border border-[var(--border)] mb-5">
        
        <div class="flex items-center justify-between mb-3">
            <span class="text-label text-[var(--text-2)] flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-[var(--accent)]"></span>
                Code Secret (Protégé par Spoiler)
            </span>
            <span class="text-caption text-[var(--text-3)] font-medium">
                <span x-show="!showPass" class="inline-flex items-center gap-1">
                    <x-lucide name="lock" class="w-3.5 h-3.5" />
                    <span>Masqué</span>
                </span>
                <span x-show="showPass" x-cloak class="text-emerald-400 inline-flex items-center gap-1">
                    <x-lucide name="unlock" class="w-3.5 h-3.5" />
                    <span>Visible</span>
                </span>
            </span>
        </div>

        {{-- Zone interactive de clic pour spoiler --}}
        <div @click="showPass = !showPass" 
             class="cursor-pointer bg-[var(--bg-base)] border border-[var(--border)] hover:border-[var(--text-3)] rounded-[4px] p-4 transition-colors">
            
            <div class="flex flex-col gap-2">
                {{-- Affichage masqué (Spoiler) --}}
                <div x-show="!showPass" class="flex items-center justify-between flex-wrap gap-3">
                    <div class="flex items-center gap-3">
                        <span class="font-mono text-lg font-bold tracking-widest text-[var(--text-3)] filter blur-[4px] select-none">
                            HS-XXXX-XXXX-XXXX
                        </span>
                        <span class="badge-tag text-[var(--accent)]">
                            Spoiler
                        </span>
                    </div>
                    <span class="text-caption font-semibold text-[var(--accent)]">
                        Cliquez pour révéler le code
                    </span>
                </div>

                {{-- Affichage révélé --}}
                <div x-show="showPass" x-cloak class="flex items-center justify-between flex-wrap gap-3">
                    <code class="font-mono text-base sm:text-lg font-bold tracking-wider text-[var(--text-1)] select-all bg-[var(--bg-raised)] px-3 py-1 rounded-[4px] border border-[var(--border)]">
                        {{ $user->pass_code }}
                    </code>
                    <span class="text-caption text-[var(--text-3)]">
                        Cliquez pour masquer à nouveau
                    </span>
                </div>
            </div>
        </div>

        {{-- Barre d'actions sous le spoiler --}}
        <div class="mt-4 flex items-center justify-end gap-3 flex-wrap">
            <button type="button" @click="showPass = !showPass" class="btn-secondary !text-caption !py-1.5 !px-3">
                <span x-text="showPass ? 'Masquer' : 'Révéler le Pass'"></span>
            </button>

            <button type="button" 
                    @click="navigator.clipboard.writeText('{{ $user->pass_code }}'); copied = true; setTimeout(() => copied = false, 2500)" 
                    class="btn-primary !text-caption !py-1.5 !px-3">
                <span x-show="!copied">Copier mon Pass</span>
                <span x-show="copied" x-cloak class="inline-flex items-center gap-1 text-white">
                    <x-lucide name="check" class="w-3.5 h-3.5 inline" /> Copié !
                </span>
            </button>
        </div>
    </div>

    {{-- Zone Régénération du Pass --}}
    <div class="pt-4 border-t border-[var(--border)] flex items-center justify-between gap-4 flex-wrap">
        <div>
            <div class="text-caption font-semibold text-[var(--text-1)]">Besoin d'un nouveau Pass ?</div>
            <div class="text-caption text-[var(--text-3)] mt-0.5">
                Si vous pensez que quelqu'un a vu votre Pass, vous pouvez en générer un nouveau immédiatement.
            </div>
        </div>

        <form method="POST" action="{{ route('profile.pass.regenerate') }}" onsubmit="return confirm('Attention : générer un nouveau Pass désactivera immédiatement votre Pass actuel. Voulez-vous continuer ?');">
            @csrf
            <button type="submit" class="btn-secondary !text-[var(--accent)] !border-[var(--border)] hover:!border-[var(--accent)] !text-caption">
                Régénérer mon Pass
            </button>
        </form>
    </div>
</section>
