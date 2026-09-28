<section x-data="{
    avatarPreview: '{{ $user->avatar_url }}',
    bannerPreview: '{{ $user->banner_url }}',
    handleAvatarFile(event) {
        const file = event.target.files[0];
        if (file) {
            this.avatarPreview = URL.createObjectURL(file);
        }
    },
    handleBannerFile(event) {
        const file = event.target.files[0];
        if (file) {
            this.bannerPreview = URL.createObjectURL(file);
        }
    }
}">
    <header class="mb-6">
        <h2 class="text-xl font-bold text-white flex items-center gap-2">
            <svg class="w-5 h-5 text-[#5865f2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
            Modifier mon profil
        </h2>
        <p class="mt-1 text-sm text-gray-400">
            Personnalisez votre photo de profil, votre bannière et vos informations publiques.
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('patch')

        {{-- 1. Photo de profil (Cercle avec anneau) --}}
        <div class="p-5 rounded-2xl bg-[#14151e] space-y-4">
            <h3 class="text-xs font-bold text-white uppercase tracking-wider flex items-center gap-2">
                <svg class="w-4 h-4 text-[#5865f2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                Photo de profil
            </h3>

            <div class="flex flex-col sm:flex-row sm:items-center gap-6">
                {{-- Aperçu circulaire exact --}}
                <div class="relative w-24 h-24 sm:w-28 sm:h-28 rounded-full p-[3px] bg-gradient-to-b from-[#ca8a04] via-[#78350f] to-[#451a03] shadow-xl flex-shrink-0 mx-auto sm:mx-0">
                    <div class="w-full h-full rounded-full overflow-hidden bg-[#0c0d10]">
                        <img :src="avatarPreview" alt="Aperçu avatar" class="w-full h-full object-cover">
                    </div>
                </div>

                {{-- Upload & URL --}}
                <div class="flex-1 space-y-3">
                    <div>
                        <label class="cursor-pointer inline-block">
                            <div class="px-4 py-2.5 bg-[#1c1f2a] hover:bg-[#252837] rounded-xl text-xs font-bold text-white transition flex items-center gap-2 shadow-sm">
                                <svg class="w-4 h-4 text-[#5865f2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                                Importer une photo (PNG, JPG, WEBP)
                            </div>
                            <input id="avatar_file" name="avatar_file" type="file" accept="image/*" class="hidden" @change="handleAvatarFile($event)">
                        </label>
                    </div>

                    <x-text-input 
                        id="avatar_url" 
                        name="avatar_url" 
                        type="url" 
                        placeholder="Ou collez un lien URL d'image direct (https://...)"
                        @input="if ($el.value.startsWith('http')) { avatarPreview = $el.value; }"
                    />
                    <x-input-error :messages="$errors->get('avatar_file')" class="mt-1" />
                    <x-input-error :messages="$errors->get('avatar_url')" class="mt-1" />
                </div>
            </div>
        </div>

        {{-- 2. Bannière de couverture --}}
        <div class="p-5 rounded-2xl bg-[#14151e] space-y-4">
            <h3 class="text-xs font-bold text-white uppercase tracking-wider flex items-center gap-2">
                <svg class="w-4 h-4 text-[#5865f2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                Bannière de profil
            </h3>

            {{-- Live preview --}}
            <div class="relative h-28 sm:h-36 rounded-2xl overflow-hidden bg-[#111216] shadow-inner">
                <img :src="bannerPreview" alt="Aperçu bannière" class="w-full h-full object-cover">
                <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent"></div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <label class="cursor-pointer">
                    <div class="px-4 py-2.5 bg-[#1c1f2a] hover:bg-[#252837] rounded-xl text-center text-xs font-bold text-white transition flex items-center justify-center gap-2 shadow-sm">
                        <svg class="w-4 h-4 text-[#5865f2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                        Téléverser une bannière
                    </div>
                    <input id="banner_file" name="banner_file" type="file" accept="image/*" class="hidden" @change="handleBannerFile($event)">
                </label>

                <x-text-input 
                    id="banner_url" 
                    name="banner_url" 
                    type="url" 
                    placeholder="Ou lien URL direct (https://...)"
                    @input="if ($el.value.startsWith('http')) { bannerPreview = $el.value; }"
                />
            </div>
            <x-input-error :messages="$errors->get('banner_file')" class="mt-1" />
            <x-input-error :messages="$errors->get('banner_url')" class="mt-1" />
        </div>

        {{-- 3. Identifiants & Bio --}}
        <div class="space-y-5">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                {{-- Nom / Pseudo --}}
                <div>
                    <x-input-label for="name" value="Pseudo" />
                    <x-text-input id="name" name="name" type="text" class="mt-1" :value="old('name', $user->name)" required autofocus autocomplete="name" />
                    <x-input-error class="mt-1.5" :messages="$errors->get('name')" />
                </div>

                {{-- Email --}}
                <div>
                    <x-input-label for="email" value="Adresse Email" />
                    <x-text-input id="email" name="email" type="email" class="mt-1" :value="old('email', $user->email)" required autocomplete="username" />
                    <x-input-error class="mt-1.5" :messages="$errors->get('email')" />

                    @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                        <div class="mt-2 text-xs text-amber-400">
                            Votre adresse email n'est pas vérifiée.
                            <button form="send-verification" class="underline hover:text-amber-300 ml-1">
                                Renvoyer l'email de confirmation
                            </button>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Biographie --}}
            <div>
                <x-input-label for="bio" value="Biographie / Présentation" />
                <textarea
                    id="bio"
                    name="bio"
                    rows="3"
                    maxlength="300"
                    placeholder="Partagez quelques mots sur vos lectures préférées..."
                    class="w-full bg-[#14151e] border-0 text-white placeholder-gray-500 focus:ring-2 focus:ring-[#5865f2] rounded-xl px-4 py-3 text-sm transition outline-none resize-none shadow-inner"
                >{{ old('bio', $user->bio) }}</textarea>
                <div class="flex justify-between items-center mt-1">
                    <span class="text-xs text-gray-500">Visible sur votre profil.</span>
                    <span class="text-xs text-gray-500" x-data="{ len: {{ strlen($user->bio ?? '') }} }" x-text="len + '/300'"></span>
                </div>
                <x-input-error class="mt-1.5" :messages="$errors->get('bio')" />
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                {{-- Genre favori --}}
                <div>
                    <x-input-label for="favorite_genre" value="Genre favori" />
                    <select
                        id="favorite_genre"
                        name="favorite_genre"
                        class="w-full bg-[#14151e] border-0 text-white focus:ring-2 focus:ring-[#5865f2] rounded-xl px-4 py-2.5 text-sm transition outline-none shadow-inner cursor-pointer"
                    >
                        <option value="" class="bg-[#161820] text-gray-400">-- Sélectionner un genre --</option>
                        @foreach($genres as $genre)
                            <option value="{{ $genre->name }}" @selected(old('favorite_genre', $user->favorite_genre) == $genre->name) class="bg-[#161820] text-white">
                                {{ $genre->name }}
                            </option>
                        @endforeach
                    </select>
                    <x-input-error class="mt-1.5" :messages="$errors->get('favorite_genre')" />
                </div>

                {{-- Mode de lecture par défaut --}}
                <div>
                    <x-input-label for="reader_mode" value="Mode de lecture préféré" />
                    <select
                        id="reader_mode"
                        name="reader_mode"
                        class="w-full bg-[#14151e] border-0 text-white focus:ring-2 focus:ring-[#5865f2] rounded-xl px-4 py-2.5 text-sm transition outline-none shadow-inner cursor-pointer"
                    >
                        <option value="vertical" @selected(old('reader_mode', $user->reader_mode) === 'vertical') class="bg-[#161820] text-white">
                            Défilement vertical continu (Webtoon)
                        </option>
                        <option value="single" @selected(old('reader_mode', $user->reader_mode) === 'single') class="bg-[#161820] text-white">
                            Page par page (Manga traditionnel)
                        </option>
                    </select>
                    <x-input-error class="mt-1.5" :messages="$errors->get('reader_mode')" />
                </div>
            </div>
        </div>

        <div class="flex items-center gap-4 pt-2">
            <x-primary-button>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                Enregistrer les modifications
            </x-primary-button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 3000)"
                    class="text-sm font-semibold text-emerald-400 flex items-center gap-1.5"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Profil mis à jour avec succès !
                </p>
            @endif
        </div>
    </form>
</section>
