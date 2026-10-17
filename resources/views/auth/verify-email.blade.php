<x-layouts.public title="Vérification de l'email — Hidden Scan">
    <div class="max-w-md mx-auto mt-8 sm:mt-16 p-6 sm:p-8 bg-[var(--bg-surface)] border border-[var(--border)] rounded-[6px] shadow-sm">
        
        <div class="text-center mb-6">
            <h1 class="text-h2 font-semibold text-[var(--text-1)] mb-1">Vérification de l'email</h1>
            <p class="text-caption text-[var(--text-2)]">
                Merci de nous avoir rejoints. Veuillez vérifier votre adresse email en cliquant sur le lien reçu.
            </p>
        </div>

        @if (session('status') == 'verification-link-sent')
            <div class="mb-5 p-3 rounded-[4px] bg-[var(--bg-raised)] border border-[var(--border)] text-caption text-emerald-400">
                Un nouveau lien de vérification vous a été envoyé par email.
            </div>
        @endif

        <div class="space-y-4 pt-2">
            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <button type="submit" class="btn-primary w-full !py-2.5">
                    Renvoyer l'email de vérification
                </button>
            </form>

            <form method="POST" action="{{ route('logout') }}" class="text-center pt-2">
                @csrf
                <button type="submit" class="text-caption text-[var(--text-3)] hover:text-[var(--accent)] transition cursor-pointer">
                    Se déconnecter
                </button>
            </form>
        </div>

    </div>
</x-layouts.public>
