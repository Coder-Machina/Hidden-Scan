@use('Illuminate\Support\Facades\Storage')
<x-layouts.public title="Hidden Scan — Lecture en ligne">

    {{-- Hero --}}
    <section class="mb-12">
        <div class="bg-[#1d1930] rounded-xl p-8 border border-[#3d3660]">
            <h1 class="text-4xl font-bold text-[#9b7bff] mb-2">Hidden Scan</h1>
            <p class="text-[#a39fc0] text-lg">Lis tes mangas, manhwas et manhuas en ligne, gratuitement et sans inscription.</p>
            <a href="{{ route('manga.index') }}" class="inline-block mt-4 bg-[#9b7bff] text-white px-6 py-2 rounded-lg font-semibold hover:bg-[#7c5cff] transition">
                Voir le catalogue
            </a>
        </div>
    </section>

    {{-- Dernières sorties --}}
    @if($latest_chapters->count())
    <section class="mb-12">
        <h2 class="text-xl font-bold mb-4 text-[#ece9f7]">Dernières sorties</h2>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
            @foreach($latest_chapters as $chapter)
            <a href="{{ route('chapter.show', [$chapter->manga->slug, $chapter->slug]) }}" class="group">
                <div class="bg-[#1d1930] rounded-lg overflow-hidden border border-[#3d3660] group-hover:border-[#9b7bff] transition">
                    @if($chapter->manga->cover_image)
                        <img src="{{ Storage::url($chapter->manga->cover_image) }}" alt="{{ $chapter->manga->title }}" class="w-full aspect-2/3 object-cover">
                    @else
                        <div class="w-full aspect2/3] bg-[#2a2445] flex items-center justify-center">
                            <span class="text-[#a39fc0] text-xs text-center px-2">{{ $chapter->manga->title }}</span>
                        </div>
                    @endif
                    <div class="p-2">
                        <p class="text-xs font-semibold text-[#ece9f7] truncate">{{ $chapter->manga->title }}</p>
                        <p class="text-xs text-[#9b7bff]">Chapitre {{ $chapter->number }}</p>
                    </div>
                </div>
            </a>
            @endforeach
        </div>
    </section>
    @endif

    {{-- Populaires --}}
    @if($popular->count())
    <section class="mb-12">
        <h2 class="text-xl font-bold mb-4 text-[#ece9f7]">Populaires</h2>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
            @foreach($popular as $manga)
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
                        <p class="text-xs text-[#a39fc0]">{{ number_format($manga->views_count) }} vues</p>
                    </div>
                </div>
            </a>
            @endforeach
        </div>
    </section>
    @endif

</x-layouts.public>