<!DOCTYPE html>
<html lang="fr" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance en cours — Hidden Scan</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Nunito:wght@600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])
</head>
<body class="bg-[var(--bg-base)] text-[var(--text-1)] min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full text-center bg-[var(--bg-surface)] border border-[var(--border)] rounded-[6px] p-8">
        {{-- Logo Mascot --}}
        <div class="w-16 h-16 mx-auto rounded-[4px] bg-[var(--bg-raised)] border border-[var(--border)] p-2 mb-6 flex items-center justify-center">
            <img src="{{ asset('images/logo.png') }}" alt="Hidden Scan" class="w-12 h-12 object-contain">
        </div>

        <h1 class="font-heading font-semibold text-[24px] text-[var(--text-1)] mb-2">
            Maintenance en cours
        </h1>
        <p class="text-body text-[var(--text-2)] text-[14px] leading-relaxed mb-6">
            Nous préparons actuellement des optimisations pour améliorer votre expérience de lecture. Le site sera de retour très prochainement.
        </p>

        <div class="flex flex-col gap-3">
            <a href="https://discord.gg/sMUeFSks4" target="_blank" rel="noopener noreferrer"
               class="btn-primary w-full text-xs py-2.5 px-4 text-center inline-flex items-center justify-center gap-2">
                <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24">
                    <path d="M20.317 4.37a19.791 19.791 0 0 0-4.885-1.515.074.074 0 0 0-.079.037c-.21.375-.444.864-.608 1.25a18.27 18.27 0 0 0-5.487 0 12.64 12.64 0 0 0-.617-1.25.077.077 0 0 0-.079-.037A19.736 19.736 0 0 0 3.677 4.37a.07.07 0 0 0-.032.027C.533 9.046-.32 13.58.099 18.057a.082.082 0 0 0 .031.057 19.9 19.9 0 0 0 5.993 3.03.078.078 0 0 0 .084-.028c.462-.63.874-1.295 1.226-1.994.021-.041.001-.09-.041-.106a13.107 13.107 0 0 1-1.872-.892.077.077 0 0 1-.008-.128 10.2 10.2 0 0 0 .372-.292.074.074 0 0 1 .077-.01c3.929 1.793 8.18 1.793 12.061 0a.074.074 0 0 1 .078.01c.12.098.246.198.373.292a.077.077 0 0 1-.006.127 12.299 12.299 0 0 1-1.873.894.077.077 0 0 0-.041.107c.36.698.772 1.362 1.225 1.993a.076.076 0 0 0 .084.028 19.839 19.839 0 0 0 6.002-3.03.077.077 0 0 0 .032-.054c.5-5.177-.838-9.674-3.549-13.66a.061.061 0 0 0-.031-.028zM8.02 15.33c-1.183 0-2.157-1.085-2.157-2.419 0-1.333.956-2.419 2.157-2.419 1.21 0 2.176 1.096 2.157 2.42 0 1.333-.956 2.418-2.157 2.418zm7.975 0c-1.183 0-2.157-1.085-2.157-2.419 0-1.333.955-2.419 2.157-2.419 1.21 0 2.176 1.096 2.157 2.42 0 1.333-.946 2.418-2.157 2.418z"/>
                </svg>
                <span>Rejoindre notre Discord</span>
            </a>

            <button onclick="window.location.reload()"
                    class="btn-secondary w-full text-xs py-2.5 px-4 text-center cursor-pointer">
                Actualiser la page
            </button>
        </div>
    </div>
</body>
</html>
