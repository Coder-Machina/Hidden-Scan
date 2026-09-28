<section x-data="{ showPass: false, copied: false }">
    <div style="margin-bottom: 20px;">
        <h3 style="font-size: 15px; font-weight: 600; color: #e8e8f0; display: flex; align-items: center; gap: 8px;">
            <svg style="width: 16px; height: 16px; stroke: #dc2626; fill: none;" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
            Mon Pass Secret (Identifiant unique)
        </h3>
        <p style="font-size: 12px; color: #7070a0; margin-top: 4px;">
            Votre Pass remplace l'email et le mot de passe. C'est votre seule clé pour vous reconnecter sur n'importe quel appareil.
        </p>
    </div>

    @if (session('status') === 'pass-regenerated')
        <div style="background: rgba(16, 185, 129, 0.15); border: 1px solid rgba(16, 185, 129, 0.35); border-radius: 10px; padding: 12px 16px; margin-bottom: 16px; display: flex; align-items: center; gap: 10px; color: #34d399; font-size: 13px; font-weight: 600;">
            <span>✓ Nouveau Pass généré avec succès ! Votre ancien Pass ne fonctionne plus. Conservez bien le nouveau.</span>
        </div>
    @endif

    {{-- Boîte d'affichage du Pass Secret --}}
    <div style="background: #0d0e14; border: 1px solid #222332; border-radius: 12px; padding: 18px 20px; margin-bottom: 20px;">
        <label style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #7070a0; display: block; margin-bottom: 8px;">
            Code de connexion actuel
        </label>
        
        <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap;">
            <div style="font-family: monospace; font-size: 18px; font-weight: 800; letter-spacing: 2px; color: #ffffff;">
                <span x-show="showPass">{{ $user->pass_code }}</span>
                <span x-show="!showPass" style="color: #606080;">HS-••••-••••-••••</span>
            </div>

            <div style="display: flex; align-items: center; gap: 8px;">
                {{-- Bouton Afficher / Masquer --}}
                <button type="button" @click="showPass = !showPass" 
                        class="btn-ghost" 
                        style="padding: 6px 12px; font-size: 12px; display: inline-flex; align-items: center; gap: 6px; cursor: pointer;">
                    <span x-text="showPass ? 'Masquer' : 'Afficher'"></span>
                </button>

                {{-- Bouton Copier --}}
                <button type="button" 
                        @click="navigator.clipboard.writeText('{{ $user->pass_code }}'); copied = true; setTimeout(() => copied = false, 2500)" 
                        class="btn-save-red" 
                        style="padding: 6px 14px; font-size: 12px; display: inline-flex; align-items: center; gap: 6px; cursor: pointer;">
                    <svg style="width: 14px; height: 14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/></svg>
                    <span x-text="copied ? 'Copié ! ✓' : 'Copier mon Pass'"></span>
                </button>
            </div>
        </div>
    </div>

    {{-- Zone Régénération du Pass --}}
    <div style="padding-top: 14px; border-top: 1px solid #1e1e2e; display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap;">
        <div>
            <div style="font-size: 13px; font-weight: 600; color: #e8e8f0;">Besoin d'un nouveau Pass ?</div>
            <div style="font-size: 12px; color: #7070a0; margin-top: 2px;">
                Si vous pensez que quelqu'un a vu votre Pass, vous pouvez en générer un nouveau immédiatement.
            </div>
        </div>

        <form method="POST" action="{{ route('profile.pass.regenerate') }}" onsubmit="return confirm('Attention : générer un nouveau Pass désactivera immédiatement votre Pass actuel. Voulez-vous continuer ?');">
            @csrf
            <button type="submit" 
                    class="btn-ghost" 
                    style="border-color: rgba(220, 38, 38, 0.4); color: #f87171; font-size: 12px; font-weight: 700; padding: 7px 14px; cursor: pointer;">
                🔄 Régénérer mon Pass
            </button>
        </form>
    </div>
</section>
