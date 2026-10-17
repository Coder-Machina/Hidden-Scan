<x-layouts.public title="{{ $title }} — Hidden Scan">
    <div class="max-w-3xl mx-auto py-8 sm:py-12" data-aos="fade-in" data-aos-duration="400">
        <div class="mb-8">
            <h1 class="text-h1 mb-2">{{ $title }}</h1>
            <p class="text-caption text-[var(--text-3)]">Dernière mise à jour : {{ now()->format('d/m/Y') }}</p>
        </div>
        
        <div class="bg-[var(--bg-surface)] rounded-[6px] p-6 sm:p-8 border border-[var(--border)] prose prose-invert max-w-none text-body text-[var(--text-2)] leading-relaxed">
            {!! $content !!}
        </div>
    </div>
</x-layouts.public>
