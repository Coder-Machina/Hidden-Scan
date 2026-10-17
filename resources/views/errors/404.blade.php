<!DOCTYPE html>
<html lang="fr" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 — Page Introuvable | Hidden Scan</title>
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
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </div>

        <h1 class="font-heading font-semibold text-[32px] text-[var(--text-1)] mb-2">404</h1>
        <h2 class="text-h3 font-medium text-[var(--text-1)] mb-3">Page introuvable</h2>
        <p class="text-body text-[var(--text-2)] text-[14px] leading-relaxed mb-6">
            La page que vous recherchez n'existe pas, a été déplacée ou le chapitre a changé d'adresse.
        </p>

        <div class="flex flex-col sm:flex-row gap-3 justify-center">
            <a href="{{ route('home') }}" class="btn-primary text-xs py-2.5 px-4 text-center">
                Retour à l'accueil
            </a>
            <a href="{{ route('manga.index') }}" class="btn-secondary text-xs py-2.5 px-4 text-center">
                Explorer le catalogue
            </a>
        </div>
    </div>
</body>
</html>
