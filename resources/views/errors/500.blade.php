<!DOCTYPE html>
<html lang="fr" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 — Erreur Serveur | Hidden Scan</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Nunito:wght@600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])
</head>
<body class="bg-[var(--bg-base)] text-[var(--text-1)] min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full text-center bg-[var(--bg-surface)] border border-[var(--border)] rounded-[6px] p-8">
        {{-- Minimal Icon --}}
        <div class="w-14 h-14 mx-auto rounded-[4px] bg-[var(--bg-raised)] border border-[var(--border)] flex items-center justify-center text-[var(--accent)] mb-6">
            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
        </div>

        <h1 class="font-heading font-semibold text-[32px] text-[var(--text-1)] mb-2">500</h1>
        <h2 class="text-h3 font-medium text-[var(--text-1)] mb-3">Erreur Interne du Serveur</h2>
        <p class="text-body text-[var(--text-2)] text-[14px] leading-relaxed mb-6">
            Une erreur inattendue est survenue lors du traitement de votre requête. Nos scribes ont été alertés.
        </p>

        <div class="flex flex-col sm:flex-row gap-3 justify-center">
            <button onclick="window.location.reload()" class="btn-primary text-xs py-2.5 px-4 text-center cursor-pointer">
                Actualiser la page
            </button>
            <a href="{{ route('home') }}" class="btn-secondary text-xs py-2.5 px-4 text-center">
                Retour à l'accueil
            </a>
        </div>
    </div>
</body>
</html>
