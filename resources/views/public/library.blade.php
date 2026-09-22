<x-layouts.public title="Ma bibliothèque — Hidden Scan">

    <h1 class="text-2xl font-bold mb-2">Ma bibliothèque</h1>
    <p class="text-[#a39fc0] text-sm mb-8">Tes favoris sont sauvegardés dans ce navigateur. <a href="#" class="text-[#9b7bff] hover:underline" onclick="exportLibrary()">Exporter</a></p>

    <div
        x-data="{ favorites: [], loaded: false }"
        x-init="
            favorites = window.HiddenScan.getFavorites();
            loaded = true;
        "
    >
        <template x-if="loaded && favorites.length === 0">
            <div class="text-center py-20 text-[#a39fc0]">
                <p class="text-lg mb-2">Ta bibliothèque est vide.</p>
                <a href="{{ route('manga.index') }}" class="text-[#9b7bff] hover:underline">Parcourir le catalogue</a>
            </div>
        </template>

        <template x-if="loaded && favorites.length > 0">
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
                <template x-for="fav in favorites" :key="fav.slug">
                    <a :href="'/manga/' + fav.slug" class="group">
                        <div class="bg-[#1d1930] rounded-lg overflow-hidden border border-[#3d3660] group-hover:border-[#9b7bff] transition">
                            <template x-if="fav.cover">
                                <img :src="fav.cover" :alt="fav.title" class="w-full aspect-2/3 object-cover">
                            </template>
                            <template x-if="!fav.cover">
                                <div class="w-full aspect-2/3 bg-[#2a2445] flex items-center justify-center">
                                    <span class="text-[#a39fc0] text-xs text-center px-2" x-text="fav.title"></span>
                                </div>
                            </template>
                            <div class="p-2">
                                <p class="text-xs font-semibold text-[#ece9f7] truncate" x-text="fav.title"></p>
                            </div>
                        </div>
                    </a>
                </template>
            </div>
        </template>
    </div>

    <script>
    function exportLibrary() {
        const data = localStorage.getItem('hiddenscan') || '{}';
        const blob = new Blob([data], { type: 'application/json' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'hiddenscan-bibliotheque.json';
        a.click();
        URL.revokeObjectURL(url);
    }
    </script>

</x-layouts.public>