<x-layouts.public title="Connexion — Hidden Scan">
    <div class="max-w-md mx-auto mt-10 p-8 bg-panel border border-line/50 rounded-2xl shadow-xl animate-fade-in-up">
        <div class="text-center mb-8">
            <h1 class="font-display text-2xl font-bold text-chalk">Bon retour !</h1>
            <p class="text-mist mt-2">Connectez-vous pour continuer</p>
        </div>

        <x-auth-session-status class="mb-4" :status="session('status')" />

        <form method="POST" action="{{ route('login') }}" class="space-y-5">
            @csrf

            <!-- Email Address -->
            <div>
                <label for="email" class="block text-sm font-semibold text-mist mb-1">Email</label>
                <input id="email" class="input-field" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" />
                <x-input-error :messages="$errors->get('email')" class="mt-2 text-rose text-sm" />
            </div>

            <!-- Password -->
            <div>
                <div class="flex items-center justify-between mb-1">
                    <label for="password" class="block text-sm font-semibold text-mist">Mot de passe</label>
                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}" class="text-xs text-chalk hover:text-mist transition underline decoration-line underline-offset-2">Oublié ?</a>
                    @endif
                </div>
                <input id="password" class="input-field" type="password" name="password" required autocomplete="current-password" />
                <x-input-error :messages="$errors->get('password')" class="mt-2 text-rose text-sm" />
            </div>

            <!-- Remember Me -->
            <div class="flex items-center">
                <input id="remember_me" type="checkbox" class="rounded bg-ink border-line text-chalk focus:ring-0 focus:ring-offset-0 cursor-pointer" name="remember">
                <label for="remember_me" class="ml-2 text-sm text-mist cursor-pointer">Se souvenir de moi</label>
            </div>

            <div class="pt-4">
                <button type="submit" class="btn-primary w-full py-3 !text-base">
                    Se connecter
                </button>
            </div>
        </form>

        <p class="mt-8 text-center text-sm text-mist">
            Pas encore de compte ? 
            <a href="{{ route('register') }}" class="text-chalk font-semibold hover:underline decoration-line underline-offset-2 transition">S'inscrire</a>
        </p>
    </div>
</x-layouts.public>
