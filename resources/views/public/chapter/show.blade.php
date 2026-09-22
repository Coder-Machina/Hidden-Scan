@use('Illuminate\Support\Facades\Storage')
<x-layouts.public :title="$manga->title . ' — Chapitre ' . $chapter->number">

    {{-- Navigation --}}
    <div class="flex items-center justify-between mb-6 bg-[#1d1930] border border-[#3d3660] rounded-xl px-4 py-3">
        <a href="{{ route('manga.show', $manga->slug) }}" class="text-[#9b7bff] hover:underline text-sm font-semibold">
            ← {{ $manga->title }}
        </a>
        <select onchange="window.location.href=this.value"
            class="bg-[#14111f] border border-[#3d3660] rounded-lg px-3 py-1.5 text-sm text-[#ece9f7] focus:outline-none focus:border-[#9b7bff]">
            @foreach($manga->chapters as $c)
                <option value="{{ route('chapter.show', [$manga->slug, $c->slug]) }}"
                    {{ $c->id === $chapter->id ? 'selected' : '' }}>
                    Chapitre {{ $c->number }} {{ $c->title ? '— ' . $c->title : '' }}
                </option>
            @endforeach
        </select>
        <div class="flex gap-2">
            @if($prev)
                <a href="{{ route('chapter.show', [$manga->slug, $prev->slug]) }}"
                   class="bg-[#2a2445] hover:bg-[#9b7bff] text-[#ece9f7] px-3 py-1.5 rounded-lg text-sm transition">
                    ← Préc.
                </a>
            @endif
            @if($next)
                <a href="{{ route('chapter.show', [$manga->slug, $next->slug]) }}"
                   class="bg-[#9b7bff] hover:bg-[#7c5cff] text-white px-3 py-1.5 rounded-lg text-sm transition">
                    Suiv. →
                </a>
            @endif
        </div>
    </div>

    {{-- Pages du chapitre --}}
    <div class="flex flex-col items-center gap-1" id="reader">
        @forelse($chapter->pages as $page)
            <img
                src="{{ Storage::url($page->image_path) }}"
                alt="Page {{ $page->page_number }}"
                loading="lazy"
                class="w-full max-w-3xl"
            >
        @empty
            <p class="text-[#a39fc0] py-20 text-center">Aucune page disponible pour ce chapitre.</p>
        @endforelse
    </div>

    {{-- Navigation bas de page --}}
    <div class="flex items-center justify-center gap-4 mt-8">
        @if($prev)
            <a href="{{ route('chapter.show', [$manga->slug, $prev->slug]) }}"
               class="bg-[#2a2445] hover:bg-[#9b7bff] text-[#ece9f7] px-6 py-2 rounded-lg font-semibold transition">
                ← Chapitre précédent
            </a>
        @endif
        @if($next)
            <a href="{{ route('chapter.show', [$manga->slug, $next->slug]) }}"
               class="bg-[#9b7bff] hover:bg-[#7c5cff] text-white px-6 py-2 rounded-lg font-semibold transition">
                Chapitre suivant →
            </a>
        @endif
    </div>

    {{-- Sauvegarde progression en localStorage --}}
    <script>
        const progress = {
            manga: "{{ $manga->slug }}",
            chapter: {{ $chapter->number }},
            slug: "{{ $chapter->slug }}",
            updatedAt: new Date().toISOString()
        };
        try {
            const data = JSON.parse(localStorage.getItem('hiddenscan') || '{}');
            if (!data.progress) data.progress = {};
            data.progress[progress.manga] = progress;
            if (!data.history) data.history = [];
            data.history = data.history.filter(h => !(h.manga === progress.manga && h.chapter === progress.chapter));
            data.history.unshift({ manga: progress.manga, chapter: progress.chapter, readAt: progress.updatedAt });
            data.history = data.history.slice(0, 50);
            localStorage.setItem('hiddenscan', JSON.stringify(data));
        } catch(e) {}
    </script>
        {{-- Commentaires --}}
    <div class="max-w-3xl mx-auto mt-8">
        <livewire:public.comment-section
            type="App\Models\Chapter"
            :id="$chapter->id"
        />
    </div>
</x-layouts.public>