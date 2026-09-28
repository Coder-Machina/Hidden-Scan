<x-layouts.public title="Inscription — Hidden Scan">
    <div class="max-w-lg mx-auto mt-6 sm:mt-10 p-6 sm:p-8 bg-[#161820] rounded-3xl shadow-2xl border border-[#252837]/60 animate-fade-in-up" x-data="{
        avatarPreview: null,
        handleAvatar(e) {
            const file = e.target.files[0];
            if (file) {
                this.avatarPreview = URL.createObjectURL(file);
            }
        }
    }">
        <div class="text-center mb-6">
            <h1 class="font-display text-2xl sm:text-3xl font-bold text-white">Créer un compte</h1>
            <p class="text-gray-400 text-sm mt-1.5">Rejoignez la communauté Hidden Scan</p>
        </div>

        <form method="POST" action="{{ route('register') }}" enctype="multipart/form-data" class="space-y-5">
            @csrf

            {{-- Photo de profil circulaire optionnelle --}}
            <div class="flex flex-col items-center justify-center text-center pb-1">
                <label for="avatar_file" class="cursor-pointer group relative">
                    <div class="w-20 h-20 rounded-full p-[2px] bg-gradient-to-b from-[#ca8a04] via-[#78350f] to-[#451a03] shadow-lg flex items-center justify-center transition group-hover:scale-105">
                        <div class="w-full h-full rounded-full overflow-hidden bg-[#0c0d10] flex items-center justify-center">
                            <template x-if="avatarPreview">
                                <img :src="avatarPreview" alt="Aperçu avatar" class="w-full h-full object-cover">
                            </template>
                            <template x-if="!avatarPreview">
                                <div class="flex flex-col items-center text-gray-400 group-hover:text-amber-300 transition">
                                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                </div>
                            </template>
                        </div>
                    </div>
                    <div class="mt-2 text-xs font-semibold text-gray-300 group-hover:text-white transition">
                        Ajouter une photo de profil (optionnel)
                    </div>
                    <input id="avatar_file" name="avatar_file" type="file" accept="image/*" class="hidden" @change="handleAvatar($event)">
                </label>
                <x-input-error :messages="$errors->get('avatar_file')" class="mt-1 text-rose text-xs" />
            </div>

            <!-- Name -->
            <div>
                <label for="name" class="block text-sm font-semibold text-gray-300 mb-1">Pseudo</label>
                <input id="name" class="w-full bg-[#14151e] border-0 text-white placeholder-gray-500 focus:ring-2 focus:ring-[#5865f2] rounded-xl px-4 py-2.5 text-sm transition outline-none shadow-inner" type="text" name="name" value="{{ old('name') }}" required autofocus autocomplete="name" placeholder="Votre pseudo" />
                <x-input-error :messages="$errors->get('name')" class="mt-1.5 text-rose text-xs" />
            </div>

            <!-- Email Address -->
            <div>
                <label for="email" class="block text-sm font-semibold text-gray-300 mb-1">Adresse Email</label>
                <input id="email" class="w-full bg-[#14151e] border-0 text-white placeholder-gray-500 focus:ring-2 focus:ring-[#5865f2] rounded-xl px-4 py-2.5 text-sm transition outline-none shadow-inner" type="email" name="email" value="{{ old('email') }}" required autocomplete="username" placeholder="exemple@email.com" />
                <x-input-error :messages="$errors->get('email')" class="mt-1.5 text-rose text-xs" />
            </div>

            <!-- Password -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="password" class="block text-sm font-semibold text-gray-300 mb-1">Mot de passe</label>
                    <input id="password" class="w-full bg-[#14151e] border-0 text-white placeholder-gray-500 focus:ring-2 focus:ring-[#5865f2] rounded-xl px-4 py-2.5 text-sm transition outline-none shadow-inner" type="password" name="password" required autocomplete="new-password" placeholder="••••••••" />
                    <x-input-error :messages="$errors->get('password')" class="mt-1.5 text-rose text-xs" />
                </div>

                <div>
                    <label for="password_confirmation" class="block text-sm font-semibold text-gray-300 mb-1">Confirmation</label>
                    <input id="password_confirmation" class="w-full bg-[#14151e] border-0 text-white placeholder-gray-500 focus:ring-2 focus:ring-[#5865f2] rounded-xl px-4 py-2.5 text-sm transition outline-none shadow-inner" type="password" name="password_confirmation" required autocomplete="new-password" placeholder="••••••••" />
                    <x-input-error :messages="$errors->get('password_confirmation')" class="mt-1.5 text-rose text-xs" />
                </div>
            </div>

            <div class="pt-2">
                <button type="submit" class="w-full py-3 bg-[#5865f2] hover:bg-[#4752c4] text-white font-bold text-sm rounded-xl transition duration-200 shadow-md shadow-[#5865f2]/20 cursor-pointer">
                    Créer mon compte
                </button>
            </div>
        </form>

        <p class="mt-6 text-center text-sm text-gray-400">
            Vous avez déjà un compte ? 
            <a href="{{ route('login') }}" class="text-white font-semibold hover:text-[#5865f2] transition ml-1">Se connecter</a>
        </p>
    </div>
</x-layouts.public>
