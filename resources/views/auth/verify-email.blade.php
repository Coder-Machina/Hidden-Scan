<x-layouts.public title="Vérification de l'email — Hidden Scan">
    <div class="max-w-md mx-auto mt-6 sm:mt-10 p-6 sm:p-8 bg-[#161820] border border-[#252837]/60 rounded-3xl shadow-2xl relative overflow-hidden animate-fade-in-up">
        
        <div class="text-center mb-6">
            <div class="inline-flex p-3 rounded-2xl bg-[#141420] border border-[#262638] mb-4 shadow-lg shadow-black/40">
                <img src="{{ asset('images/logo.png') }}" alt="Hidden Scan" class="w-12 h-12 rounded-xl object-cover">
            </div>
            <h1 class="font-display text-2xl font-bold text-white tracking-tight">Vérification de l'email</h1>
            <p class="text-[#8080a0] text-sm mt-2 leading-relaxed">
                Merci de nous avoir rejoints ! Avant de commencer, veuillez vérifier votre adresse email en cliquant sur le lien que nous venons de vous envoyer.
            </p>
        </div>

        @if (session('status') == 'verification-link-sent')
            <div class="mb-5 p-3.5 rounded-xl bg-emerald-950/40 border border-emerald-500/40 flex items-start gap-2.5 text-xs text-emerald-300">
                <svg class="w-4 h-4 flex-shrink-0 mt-0.5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                <span>Un nouveau lien de vérification vous a été envoyé à l'adresse email renseignée lors de votre inscription.</span>
            </div>
        @endif

        <div class="space-y-4 pt-2">
            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <button type="submit" class="btn-primary w-full py-3 !text-sm !font-bold flex items-center justify-center gap-2 shadow-lg shadow-red-950/50">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    <span>Renvoyer l'email de vérification</span>
                </button>
            </form>

            <form method="POST" action="{{ route('logout') }}" class="text-center">
                @csrf
                <button type="submit" class="text-xs text-[#a0a0c0] hover:text-red-400 transition font-medium underline">
                    Se déconnecter
                </button>
            </form>
        </div>

    </div>
</x-layouts.public>
