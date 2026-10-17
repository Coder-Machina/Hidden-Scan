<!DOCTYPE html>
<html lang="fr" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 — Accès Refusé | Hidden Scan</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Nunito:wght@600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])
</head>
<body class="bg-[var(--bg-base)] text-[var(--text-1)] min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full text-center bg-[var(--bg-surface)] border border-[var(--border)] rounded-[6px] p-8">
        {{-- Badge Code 403 --}}
        <div class="w-14 h-14 mx-auto rounded-[4px] bg-[var(--bg-raised)] border border-[var(--border)] flex items-center justify-center text-[var(--accent)] mb-6">
            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m0 0v2m0-2h2m-2 0H10m11-3.5a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </div>

        <h1 class="font-heading font-semibold text-[32px] text-[var(--text-1)] mb-2">403</h1>
        <h2 class="text-h3 font-medium text-[var(--text-1)] mb-3">Accès Refusé</h2>
        <p class="text-body text-[var(--text-2)] text-[14px] leading-relaxed mb-6">
            @if(auth()->check())
                Vous êtes connecté en tant que <strong class="text-[var(--text-1)]">{{ auth()->user()->name }}</strong>. Ce compte ne dispose pas des autorisations nécessaires pour accéder à cette zone.
            @else
                Cette section est strictement réservée à l'équipe Staff de Hidden Scan.
            @endif
        </p>

        <div class="flex flex-col gap-3">
            @if(auth()->check())
                <form method="POST" action="{{ route('logout', ['redirect' => '/admin/login']) }}">
                    @csrf
                    <button type="submit" class="btn-primary w-full text-xs py-2.5 px-4 text-center cursor-pointer">
                        Se déconnecter et aller à la connexion Admin
                    </button>
                </form>
            @else
                <a href="/admin/login" class="btn-primary w-full text-xs py-2.5 px-4 text-center">
                    Connexion Staff & Administration
                </a>
            @endif

            <a href="{{ route('home') }}" class="btn-secondary w-full text-xs py-2.5 px-4 text-center">
                Retourner à l'accueil
            </a>
        </div>
    </div>
</body>
</html>
