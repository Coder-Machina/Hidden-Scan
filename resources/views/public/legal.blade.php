<x-layouts.public title="{{ $title }} - Hidden Scan">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12 sm:py-20 animate-fade-in">
        <div class="mb-10 text-center">
            <h1 class="text-3xl sm:text-5xl font-display font-bold text-chalk mb-4">{{ $title }}</h1>
            <p class="text-mist">Dernière mise à jour : {{ now()->format('d/m/Y') }}</p>
        </div>
        
        <div class="bg-panel rounded-2xl p-6 sm:p-10 border border-line/30 shadow-2xl prose prose-invert prose-violet max-w-none">
            {!! $content !!}
        </div>
    </div>
</x-layouts.public>
