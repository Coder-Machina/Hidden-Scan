@use('Illuminate\Support\Facades\Storage')
<x-layouts.public :title="$manga->title . ' — Hidden Scan'">

    {{-- Hero --}}
    <div class="relative mb-8">
        @if($manga->banner_image)
            <img src="{{ Storage::url($manga->banner_image) }}" alt="" class="w-full h-48 object-cover rounded-xl opacity-40">
        @else
            <div class="w-full h-48 bg-[#1d1930] rounded-xl"></div>
        @endif

        <div class="absolute bottom-0 left-0 p-6 flex gap-6 items-end">
            @if($manga->cover_image)
                <img src="{{ Storage::url($manga->cover_image) }}" alt="{{ $manga->title }}" class="w-32 rounded-lg border-2 border-[#3d3660] shadow-xl">
            @else
                <div class="w-32 h-48 bg-[#2a2445] rounded-lg border-2 border-[#3d3660] flex items-center justify-center">
                    <span class="text-[#a39fc0] text-xs text-center px-2">{{ $manga->title }}</span>
                </div>
            @endif
            <div>
                <span class="text-xs px-2 py-0.5 rounded bg-[#9b7bff] text-white mb-2 inline-block">{{ ucfirst($manga->type) }}</span>
                <h1 class="text-3xl font-bold text-white">{{ $manga->title }}</h1>
                    <<button
                        id="fav-btn"
                        onclick="toggleFav()"
                        class="mt-3 flex items-center gap-2 px-4 py-2 rounded-lg border transition text-sm font-semibold border-[#3d3660] text-[#a39fc0] hover:border-[#9b7bff]"
                     >
                        ♡ Ajouter aux favoris
                    </button>

                <script>
                    (function() {
                        const slug = "{{ $manga->slug }}";
                        const title = "{{ addslashes($manga->title) }}";
                        const cover = "{{ $manga->cover_image ? Storage::url($manga->cover_image) : '' }}";
                        const btn = document.getElementById('fav-btn');

                        function update(isFav) {
                            btn.textContent = isFav ? '♥ Dans vos favoris' : '♡ Ajouter aux favoris';
                            btn.className = 'mt-3 flex items-center gap-2 px-4 py-2 rounded-lg border transition text-sm font-semibold '
                                + (isFav ? 'bg-[#9b7bff] border-[#9b7bff] text-white' : 'border-[#3d3660] text-[#a39fc0] hover:border-[#9b7bff]');
                        }

                        window.toggleFav = function() {
                            const isFav = window.HiddenScan.toggleFavorite(slug, title, cover);
                            update(isFav);
                        };

                        update(window.HiddenScan.isFavorite(slug));
                    })();
                </script>
                @if($manga->author)
                    <p class="text-[#a39fc0] text-sm mt-1">{{ $manga->author->name }}</p>
                @endif
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

        {{-- Infos --}}
        <div class="lg:col-span-1 space-y-4">
            <div class="bg-[#1d1930] border border-[#3d3660] rounded-xl p-4">
                <h2 class="font-bold mb-3 text-[#ece9f7]">Informations</h2>
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-[#a39fc0]">Statut</dt>
                        <dd class="font-semibold">{{ ucfirst(str_replace('_', ' ', $manga->status)) }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-[#a39fc0]">Type</dt>
                        <dd class="font-semibold">{{ ucfirst($manga->type) }}</dd>
                    </div>
                    @if($manga->release_year)
                    <div class="flex justify-between">
                        <dt class="text-[#a39fc0]">Année</dt>
                        <dd class="font-semibold">{{ $manga->release_year }}</dd>
                    </div>
                    @endif
                    <div class="flex justify-between">
                        <dt class="text-[#a39fc0]">Vues</dt>
                        <dd class="font-semibold">{{ number_format($manga->views_count) }}</dd>
                    </div>
                </dl>
            </div>

            @if($manga->genres->count())
            <div class="bg-[#1d1930] border border-[#3d3660] rounded-xl p-4">
                <h2 class="font-bold mb-3 text-[#ece9f7]">Genres</h2>
                <div class="flex flex-wrap gap-2">
                    @foreach($manga->genres as $genre)
                        <a href="{{ route('manga.index', ['genre' => $genre->slug]) }}"
                           class="text-xs px-2 py-1 rounded bg-[#2a2445] text-[#9b7bff] hover:bg-[#9b7bff] hover:text-white transition">
                            {{ $genre->name }}
                        </a>
                    @endforeach
                </div>
            </div>
            @endif
        </div>

        {{-- Synopsis + Chapitres --}}
        <div class="lg:col-span-2 space-y-6">
            @if($manga->synopsis)
            <div class="bg-[#1d1930] border border-[#3d3660] rounded-xl p-4">
                <h2 class="font-bold mb-3 text-[#ece9f7]">Synopsis</h2>
                <p class="text-[#a39fc0] text-sm leading-relaxed">{{ $manga->synopsis }}</p>
            </div>
            @endif

            <div class="bg-[#1d1930] border border-[#3d3660] rounded-xl p-4">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="font-bold text-[#ece9f7]">Chapitres</h2>
                    <span class="text-xs text-[#a39fc0]">{{ $manga->chapters->count() }} chapitres</span>
                </div>

                @if($manga->chapters->count())
                    @if($manga->chapters->first())
                        <a href="{{ route('chapter.show', [$manga->slug, $manga->chapters->first()->slug]) }}"
                           class="block w-full text-center bg-[#9b7bff] text-white py-2 rounded-lg font-semibold hover:bg-[#7c5cff] transition mb-4">
                            Commencer à lire
                        </a>
                    @endif
                    <div class="space-y-1 max-h-96 overflow-y-auto">
                        @foreach($manga->chapters as $chapter)
                        <a href="{{ route('chapter.show', [$manga->slug, $chapter->slug]) }}"
                           class="flex items-center justify-between px-3 py-2 rounded-lg hover:bg-[#2a2445] transition">
                            <span class="text-sm">Chapitre {{ $chapter->number }}
                                @if($chapter->title) — {{ $chapter->title }} @endif
                            </span>
                            @if($chapter->published_at)
                                <span class="text-xs text-[#a39fc0]">{{ $chapter->published_at->diffForHumans() }}</span>
                            @endif
                        </a>
                        @endforeach
                    </div>
                @else
                    <p class="text-[#a39fc0] text-sm text-center py-4">Aucun chapitre disponible pour le moment.</p>
                @endif
            </div>
        </div>
    </div>

</x-layouts.public>