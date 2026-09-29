<section x-data="{ showPass: false, copied: false }">
    <div style="margin-bottom: 20px;">
        <h3 style="font-size: 16px; font-weight: 700; color: #ffffff; display: flex; align-items: center; gap: 10px;">
            <span style="display: flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 8px; background: rgba(220, 38, 38, 0.15); border: 1px solid rgba(220, 38, 38, 0.3); color: #ef4444;">
                <svg style="width: 18px; height: 18px; stroke: currentColor; fill: none;" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
            </span>
            <span>Mon Pass Secret (Connexion Anonyme)</span>
        </h3>
        <p style="font-size: 13px; color: #8a8aa3; margin-top: 6px; line-height: 1.5;">
            Votre compte est 100% anonyme. Ce code est votre unique identifiant de connexion. Ne le partagez jamais avec des tiers.
        </p>
    </div>

    @if (session('status') === 'pass-regenerated')
        <div style="background: rgba(16, 185, 129, 0.15); border: 1px solid rgba(16, 185, 129, 0.35); border-radius: 12px; padding: 14px 18px; margin-bottom: 20px; display: flex; align-items: center; gap: 12px; color: #34d399; font-size: 13px; font-weight: 600;">
            <svg style="width: 20px; height: 20px; flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            <span>Nouveau Pass généré avec succès ! Votre ancien Pass a été révoqué. Pensez à conserver le nouveau en lieu sûr.</span>
        </div>
    @endif

    {{-- Boîte Spoiler du Pass Secret --}}
    <div style="background: #0d0e15; border: 1px solid #222334; border-radius: 16px; padding: 20px; margin-bottom: 22px; position: relative; overflow: hidden;"
         class="shadow-xl shadow-black/40">
        
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
            <label style="font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.8px; color: #8888aa; display: flex; align-items: center; gap: 6px;">
                <span style="width: 8px; height: 8px; border-radius: 50%; background: #ef4444;"></span>
                Code Secret (Protégé par Spoiler)
            </label>
            <span style="font-size: 11px; color: #707090; font-weight: 600;">
                <span x-show="!showPass">🔒 Masqué</span>
                <span x-show="showPass" x-cloak class="text-emerald-400">🔓 Visible</span>
            </span>
        </div>

        {{-- Zone interactive de clic pour spoiler --}}
        <div @click="showPass = !showPass" 
             style="cursor: pointer; background: #08090d; border: 1px dashed #2b2d42; border-radius: 12px; padding: 18px 20px; transition: all 0.25s ease;"
             class="hover:border-red-500/40 hover:bg-[#0c0d14] group">
            
            <div style="display: flex; flex-direction: column; gap: 8px;">
                {{-- Affichage masqué (Spoiler) --}}
                <div x-show="!showPass" style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <span style="font-family: monospace; font-size: 20px; font-weight: 800; letter-spacing: 4px; color: #4b4f69; filter: blur(4px); user-select: none;">
                            HS-XXXX-XXXX-XXXX
                        </span>
                        <span style="font-size: 11px; background: rgba(220, 38, 38, 0.15); border: 1px solid rgba(220, 38, 38, 0.3); color: #f87171; padding: 3px 8px; border-radius: 6px; font-weight: 700; text-transform: uppercase;">
                            Spoiler
                        </span>
                    </div>
                    <span style="font-size: 12px; font-weight: 600; color: #f87171; display: inline-flex; align-items: center; gap: 6px;" class="group-hover:translate-x-0.5 transition-transform">
                        <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        Cliquez pour révéler le code
                    </span>
                </div>

                {{-- Affichage révélé --}}
                <div x-show="showPass" x-cloak style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
                    <code style="font-family: monospace; font-size: 19px; font-weight: 800; letter-spacing: 2px; color: #ffffff; background: #131520; border: 1px solid rgba(220, 38, 38, 0.4); padding: 8px 14px; border-radius: 8px;" class="select-all">
                        {{ $user->pass_code }}
                    </code>
                    <span style="font-size: 12px; font-weight: 600; color: #8888aa; display: inline-flex; align-items: center; gap: 6px;">
                        <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/></svg>
                        Cliquez pour masquer à nouveau
                    </span>
                </div>
            </div>
        </div>

        {{-- Barre d'actions sous le spoiler --}}
        <div style="margin-top: 14px; display: flex; align-items: center; justify-content: flex-end; gap: 10px; flex-wrap: wrap;">
            {{-- Bouton Toggle Spoiler --}}
            <button type="button" @click="showPass = !showPass" 
                    class="btn-ghost" 
                    style="padding: 7px 14px; font-size: 12px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; cursor: pointer;">
                <span x-text="showPass ? '🙈 Masquer (Spoiler)' : '👁️ Révéler le Pass'"></span>
            </button>

            {{-- Bouton Copier --}}
            <button type="button" 
                    @click="navigator.clipboard.writeText('{{ $user->pass_code }}'); copied = true; setTimeout(() => copied = false, 2500)" 
                    class="btn-save-red" 
                    style="padding: 7px 16px; font-size: 12px; font-weight: 700; display: inline-flex; align-items: center; gap: 6px; cursor: pointer;">
                <svg style="width: 15px; height: 15px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/></svg>
                <span x-text="copied ? 'Copié dans le presse-papiers ! ✓' : 'Copier mon Pass'"></span>
            </button>
        </div>
    </div>

    {{-- Zone Régénération du Pass --}}
    <div style="padding-top: 16px; border-top: 1px solid #1e1e2e; display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap;">
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
