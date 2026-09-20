@use('Illuminate\Support\Facades\Storage')
<x-layouts.public title="Catalogue — Hidden Scan">

    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold">Catalogue</h1>
        <span class="text-[#a39fc0] text-sm">{{ $mangas->total() }} séries</span>
    </div>

    {{-- Filtres --}}
    <form method="GET" action="{{ route('manga.index') }}" class="bg-[#1d1930] border border-[#3d3660] rounded-xl p-4 mb-8 flex flex-wrap gap-4 items-end">
        <div>
            <label class="block text-xs text-[#a39fc0] mb-1">Recherche</label>
            <input type="text" name="q" value="{{ request('q') }}"
                placeholder="Titre..."
                class="bg-[#14111f] border border-[#3d3660] rounded-lg px-3 py-2 text-sm text-[#ece9f7] placeholder-[#a39fc0] focus:outline-none focus:border-[#9b7bff]">
        </div>
        <div>
            <label class="block text-xs text-[#a39fc0] mb-1">Genre</label>
            <select name="genre" class="bg-[#14111f] border border-[#3d3660] rounded-lg px-3 py-2 text-sm text-[#ece9f7] focus:outline-none focus:border-[#9b7bff]">
                <option value="">Tous</option>
                @foreach($genres as $genre)
                    <option value="{{ $genre->slug }}" {{ request('genre') == $genre->slug ? 'selected' : '' }}>{{ $genre->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs text-[#a39fc0] mb-1">Type</label>
            <select name="type" class="bg-[#14111f] border border-[#3d3660] rounded-lg px-3 py-2 text-sm text-[#ece9f7] focus:outline-none focus:border-[#9b7bff]">
                <option value="">Tous</option>
                <option value="manga" {{ request('type') == 'manga' ? 'selected' : '' }}>Manga</option>
                <option value="manhwa" {{ request('type') == 'manhwa' ? 'selected' : '' }}>Manhwa</option>
                <option value="manhua" {{ request('type') == 'manhua' ? 'selected' : '' }}>Manhua</option>
            </select>
        </div>
        <div>
            <label class="block text-xs text-[#a39fc0] mb-1">Statut</label>
            <select name="status" class="bg-[#14111f] border border-[#3d3660] rounded-lg px-3 py-2 text-sm text-[#ece9f7] focus:outline-none focus:border-[#9b7bff]">
                <option value="">Tous</option>
                <option value="en_cours" {{ request('status') == 'en_cours' ? 'selected' : '' }}>En cours</option>
                <option value="termine" {{ request('status') == 'termine' ? 'selected' : '' }}>Terminé</option>
                <option value="pause" {{ request('status') == 'pause' ? 'selected' : '' }}>En pause</option>
            </select>
        </div>
        <div>
            <label class="block text-xs text-[#a39fc0] mb-1">Trier par</label>
            <select name="sort" class="bg-[#14111f] border border-[#3d3660] rounded-lg px-3 py-2 text-sm text-[#ece9f7] focus:outline-none focus:border-[#9b7bff]">
                <option value="latest" {{ request('sort') == 'latest' ? 'selected' : '' }}>Plus récent</option>
                <option value="popular" {{ request('sort') == 'popular' ? 'selected' : '' }}>Plus populaire</option>
                <option value="title" {{ request('sort') == 'title' ? 'selected' : '' }}>Titre A-Z</option>
            </select>
        </div>
        <button type="submit" class="bg-[#9b7bff] text-white px-4 py-2 rounded-lg text-sm font-semibold hover:bg-[#7c5cff] transition">
            Filtrer
        </button>
        @if(request()->hasAny(['q','genre','type','status','sort']))
            <a href="{{ route('manga.index') }}" class="text-[#a39fc0] text-sm hover:text-[#ece9f7] transition py-2">
                Réinitialiser
            </a>
        @endif
    </form>

    {{-- Grille --}}
    @if($mangas->count())
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4 mb-8">
            @foreach($mangas as $manga)
            <a href="{{ route('manga.show', $manga->slug) }}" class="group">
                <div class="bg-[#1d1930] rounded-lg overflow-hidden border border-[#3d3660] group-hover:border-[#9b7bff] transition">
                    @if($manga->cover_image)
                        <img src="{{ Storage::url($manga->cover_image) }}" alt="{{ $manga->title }}" class="w-full aspect-2/3 object-cover">
                    @else
                        <div class="w-full aspect-2/3 bg-[#2a2445] flex items-center justify-center">
                            <span class="text-[#a39fc0] text-xs text-center px-2">{{ $manga->title }}</span>
                        </div>
                    @endif
                    <div class="p-2">
                        <p class="text-xs font-semibold text-[#ece9f7] truncate">{{ $manga->title }}</p>
                        <span class="text-[10px] px-1.5 py-0.5 rounded bg-[#2a2445] text-[#9b7bff]">{{ ucfirst($manga->type) }}</span>
                    </div>
                </div>
            </a>
            @endforeach
        </div>
        {{ $mangas->withQueryString()->links() }}
    @else
        <div class="text-center py-20 text-[#a39fc0]">
            <p class="text-lg">Aucune série trouvée.</p>
            <a href="{{ route('manga.index') }}" class="text-[#9b7bff] hover:underline mt-2 inline-block">Voir tout le catalogue</a>
        </div>
    @endif

</x-layouts.public>