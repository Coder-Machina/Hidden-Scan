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
        <h2 class="text-h3 font-semibold text-[var(--text-1)] flex items-center gap-2">
            Modifier mon profil
        </h2>
        <p class="text-caption text-[var(--text-2)] mt-1">
            Personnalisez votre photo de profil, votre bannière et vos informations publiques.
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('patch')

        {{-- 1. Photo de profil --}}
        <div class="p-4 rounded-[6px] bg-[var(--bg-surface)] border border-[var(--border)] space-y-4">
            <h3 class="text-label text-[var(--text-2)]">
                Photo de profil
            </h3>

            <div class="flex flex-col sm:flex-row sm:items-center gap-6">
                {{-- Aperçu --}}
                <div class="relative w-20 h-20 sm:w-24 sm:h-24 rounded-[6px] border border-[var(--border)] bg-[var(--bg-raised)] overflow-hidden flex-shrink-0 mx-auto sm:mx-0">
                    <img :src="avatarPreview" alt="Aperçu avatar" class="w-full h-full object-cover">
                </div>

                {{-- Upload & URL --}}
                <div class="flex-1 space-y-3">
                    <div>
                        <label class="cursor-pointer inline-block">
                            <span class="btn-secondary !text-caption !py-1.5 !px-3">
                                Importer une photo (PNG, JPG, WEBP)
                            </span>
                            <input id="avatar_file" name="avatar_file" type="file" accept="image/*" class="hidden" @change="handleAvatarFile($event)">
                        </label>
                    </div>

                    <input 
                        id="avatar_url" 
                        name="avatar_url" 
                        type="url" 
                        placeholder="Ou collez un lien URL d'image direct (https://...)"
                        class="input-field text-caption"
                        @input="if ($el.value.startsWith('http')) { avatarPreview = $el.value; }"
                    />
                    <x-input-error :messages="$errors->get('avatar_file')" class="mt-1" />
                    <x-input-error :messages="$errors->get('avatar_url')" class="mt-1" />
                </div>
            </div>
        </div>

        {{-- 2. Bannière de couverture --}}
        <div class="p-4 rounded-[6px] bg-[var(--bg-surface)] border border-[var(--border)] space-y-4">
            <h3 class="text-label text-[var(--text-2)]">
                Bannière de profil
            </h3>

            {{-- Live preview --}}
            <div class="relative h-28 sm:h-36 rounded-[6px] border border-[var(--border)] overflow-hidden bg-[var(--bg-base)]">
                <img :src="bannerPreview" alt="Aperçu bannière" class="w-full h-full object-cover">
                <div class="card-overlay"></div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <label class="cursor-pointer">
                    <span class="btn-secondary !text-caption !py-1.5 !px-3 w-full text-center block">
                        Téléverser une bannière
                    </span>
                    <input id="banner_file" name="banner_file" type="file" accept="image/*" class="hidden" @change="handleBannerFile($event)">
                </label>

                <input 
                    id="banner_url" 
                    name="banner_url" 
                    type="url" 
                    placeholder="Ou lien URL direct (https://...)"
                    class="input-field text-caption"
                    @input="if ($el.value.startsWith('http')) { bannerPreview = $el.value; }"
                />
            </div>
            <x-input-error :messages="$errors->get('banner_file')" class="mt-1" />
            <x-input-error :messages="$errors->get('banner_url')" class="mt-1" />
        </div>

        {{-- 3. Identifiants & Bio --}}
        <div class="space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                {{-- Nom / Pseudo --}}
                <div>
                    <label for="name" class="text-label text-[var(--text-2)] block mb-1.5">Pseudo</label>
                    <input id="name" name="name" type="text" class="input-field text-caption" value="{{ old('name', $user->name) }}" required autofocus autocomplete="name" />
                    <x-input-error class="mt-1" :messages="$errors->get('name')" />
                </div>

                {{-- Email --}}
                <div>
                    <label for="email" class="text-label text-[var(--text-2)] block mb-1.5">Adresse Email</label>
                    <input id="email" name="email" type="email" class="input-field text-caption" value="{{ old('email', $user->email) }}" required autocomplete="username" />
                    <x-input-error class="mt-1" :messages="$errors->get('email')" />

                    @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                        <div class="mt-2 text-xs text-amber-400">
                            Votre adresse email n'est pas vérifiée.
                            <button form="send-verification" class="underline hover:text-amber-300 ml-1 cursor-pointer">
                                Renvoyer l'email de confirmation
                            </button>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Biographie --}}
            <div>
                <label for="bio" class="text-label text-[var(--text-2)] block mb-1.5">Biographie / Présentation</label>
                <textarea
                    id="bio"
                    name="bio"
                    rows="3"
                    maxlength="300"
                    placeholder="Partagez quelques mots sur vos lectures préférées..."
                    class="input-field text-caption resize-none"
                >{{ old('bio', $user->bio) }}</textarea>
                <div class="flex justify-between items-center mt-1">
                    <span class="text-caption text-[var(--text-3)]">Visible sur votre profil.</span>
                    <span class="text-caption text-[var(--text-3)]" x-data="{ len: {{ strlen($user->bio ?? '') }} }" x-text="len + '/300'"></span>
                </div>
                <x-input-error class="mt-1" :messages="$errors->get('bio')" />
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                {{-- Genre favori --}}
                <div>
                    <label for="favorite_genre" class="text-label text-[var(--text-2)] block mb-1.5">Genre favori</label>
                    <select
                        id="favorite_genre"
                        name="favorite_genre"
                        class="input-field text-caption cursor-pointer"
                    >
                        <option value="" class="bg-[var(--bg-surface)] text-[var(--text-3)]">-- Sélectionner un genre --</option>
                        @foreach($genres as $genre)
                            <option value="{{ $genre->name }}" @selected(old('favorite_genre', $user->favorite_genre) == $genre->name) class="bg-[var(--bg-surface)] text-[var(--text-1)]">
                                {{ $genre->name }}
                            </option>
                        @endforeach
                    </select>
                    <x-input-error class="mt-1" :messages="$errors->get('favorite_genre')" />
                </div>

                {{-- Mode de lecture par défaut --}}
                <div>
                    <label for="reader_mode" class="text-label text-[var(--text-2)] block mb-1.5">Mode de lecture préféré</label>
                    <select
                        id="reader_mode"
                        name="reader_mode"
                        class="input-field text-caption cursor-pointer"
                    >
                        <option value="vertical" @selected(old('reader_mode', $user->reader_mode) === 'vertical') class="bg-[var(--bg-surface)] text-[var(--text-1)]">
                            Défilement vertical continu (Webtoon)
                        </option>
                        <option value="single" @selected(old('reader_mode', $user->reader_mode) === 'single') class="bg-[var(--bg-surface)] text-[var(--text-1)]">
                            Page par page (Manga traditionnel)
                        </option>
                    </select>
                    <x-input-error class="mt-1" :messages="$errors->get('reader_mode')" />
                </div>
            </div>
        </div>

        <div class="flex items-center gap-4 pt-2">
            <button type="submit" class="btn-primary">
                Enregistrer les modifications
            </button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 3000)"
                    class="text-caption font-semibold text-emerald-400 flex items-center gap-1.5"
                >
                    Profil mis à jour avec succès !
                </p>
            @endif
        </div>
    </form>
</section>
