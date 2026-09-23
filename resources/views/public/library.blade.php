@use('Illuminate\Support\Facades\Storage')
<x-layouts.public title="Bibliothèque — Hidden Scan">

    <div class="max-w-5xl mx-auto animate-fade-in" x-data="library()">

        {{-- ═══ Header ═══ --}}
        <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4 mb-8">
            <div>
                <h1 class="font-display font-extrabold text-3xl sm:text-4xl tracking-tight flex items-center gap-3">
                    <svg class="w-8 h-8 text-violet" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                    Bibliothèque
                </h1>
                <p class="text-mist mt-1">Gérez vos séries favorites et votre historique de lecture</p>
            </div>

            {{-- Import / Export --}}
            <div class="flex items-center gap-2">
                <button @click="importLibrary()" class="btn-secondary !py-2 !px-4 !text-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                    Importer
                </button>
                <input type="file" id="importFile" class="hidden" accept=".json" @change="handleImport($event)">

                <button @click="window.HiddenScan.exportData()" class="btn-secondary !py-2 !px-4 !text-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    Exporter
                </button>
            </div>
        </div>

        {{-- ═══ Onglets ═══ --}}
        <div class="flex gap-2 p-1 bg-panel border border-line/50 rounded-xl mb-8 overflow-x-auto hide-scrollbar">
            <button @click="activeTab = 'favorites'"
                    class="flex-1 py-2.5 px-4 rounded-lg text-sm font-semibold transition-all whitespace-nowrap"
                    :class="activeTab === 'favorites' ? 'bg-violet text-white shadow-lg shadow-violet/20' : 'text-mist hover:text-chalk hover:bg-panel-hi'">
                Favoris (<span x-text="favorites.length"></span>)
            </button>
            <button @click="activeTab = 'history'"
                    class="flex-1 py-2.5 px-4 rounded-lg text-sm font-semibold transition-all whitespace-nowrap"
                    :class="activeTab === 'history' ? 'bg-violet text-white shadow-lg shadow-violet/20' : 'text-mist hover:text-chalk hover:bg-panel-hi'">
                Historique de lecture
            </button>
        </div>

        {{-- ═══ Contenu Favoris ═══ --}}
        <div x-show="activeTab === 'favorites'" x-cloak class="animate-fade-in-up">
            <template x-if="favorites.length > 0">
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
                    <template x-for="manga in favorites" :key="manga.slug">
                        <div class="manga-card group relative">
                            <a :href="'/manga/' + manga.slug" class="block">
                                <div class="relative overflow-hidden">
                                    <template x-if="manga.cover">
                                        <img :src="manga.cover" :alt="manga.title" class="manga-cover" loading="lazy">
                                    </template>
                                    <template x-if="!manga.cover">
                                        <div class="cover-placeholder">
                                            <span x-text="manga.title"></span>
                                        </div>
                                    </template>
                                    <div class="manga-overlay"></div>
                                </div>
                                <div class="p-3">
                                    <p class="text-sm font-semibold text-chalk truncate group-hover:text-violet transition" x-text="manga.title"></p>

                                    {{-- Progression si existante --}}
                                    <template x-if="progress[manga.slug]">
                                        <div class="mt-2">
                                            <div class="flex items-center justify-between text-[10px] text-mist mb-1">
                                                <span x-text="'Ch. ' + progress[manga.slug].chapter"></span>
                                                <span x-text="progress[manga.slug].percent + '%'"></span>
                                            </div>
                                            <div class="w-full bg-ink-deep rounded-full h-1">
                                                <div class="bg-violet h-1 rounded-full" :style="'width: ' + progress[manga.slug].percent + '%'"></div>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </a>

                            {{-- Bouton supprimer --}}
                            <button @click.prevent="removeFavorite(manga.slug)"
                                    class="absolute top-2 right-2 p-1.5 rounded-md bg-ink/80 text-mist hover:text-rose hover:bg-rose/20 backdrop-blur-sm transition-all opacity-0 group-hover:opacity-100"
                                    title="Retirer des favoris">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </div>
                    </template>
                </div>
            </template>
            <template x-if="favorites.length === 0">
                <div class="text-center py-20 bg-panel border border-line/50 rounded-xl">
                    <svg class="w-16 h-16 mx-auto text-mist/30 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                    <p class="text-lg text-chalk font-semibold mb-2">Aucun favori pour le moment</p>
                    <p class="text-mist mb-6">Ajoutez des séries à vos favoris pour les retrouver facilement ici.</p>
                    <a href="{{ route('manga.index') }}" class="btn-primary">Parcourir le catalogue</a>
                </div>
            </template>
        </div>

        {{-- ═══ Contenu Historique ═══ --}}
        <div x-show="activeTab === 'history'" x-cloak class="animate-fade-in-up">
            <template x-if="history.length > 0">
                <div class="space-y-3">
                    <div class="flex justify-end mb-2">
                        <button @click="clearHistory()" class="text-xs text-mist hover:text-rose transition flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            Effacer l'historique
                        </button>
                    </div>

                    {{-- Liste historique --}}
                    <div class="bg-panel border border-line/50 rounded-xl overflow-hidden divide-y divide-line/30">
                        <template x-for="item in historyDetails" :key="item.slug + item.chapter">
                            <div class="p-4 flex items-center gap-4 hover:bg-panel-hi/50 transition-colors">
                                <template x-if="item.cover">
                                    <img :src="item.cover" class="w-12 h-16 object-cover rounded-md flex-shrink-0" alt="">
                                </template>
                                <template x-if="!item.cover">
                                    <div class="w-12 h-16 bg-ink border border-line rounded-md flex-shrink-0"></div>
                                </template>

                                <div class="flex-1 min-w-0">
                                    <a :href="'/manga/' + item.slug" class="block font-semibold text-chalk hover:text-violet transition truncate" x-text="item.title || item.slug"></a>
                                    <div class="flex items-center gap-2 mt-1">
                                        <a :href="'/manga/' + item.slug + '/chapter/' + item.chapterSlug" class="text-sm text-mist hover:text-chalk transition">
                                            Chapitre <span x-text="item.chapter"></span>
                                        </a>
                                        <span class="text-xs text-mist/50" x-text="formatDate(item.readAt)"></span>
                                    </div>

                                    {{-- Progression --}}
                                    <template x-if="item.percent > 0">
                                        <div class="mt-2 w-full max-w-xs bg-ink-deep rounded-full h-1">
                                            <div class="bg-violet h-1 rounded-full" :style="'width: ' + item.percent + '%'"></div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </template>
            <template x-if="history.length === 0">
                <div class="text-center py-20 bg-panel border border-line/50 rounded-xl">
                    <svg class="w-16 h-16 mx-auto text-mist/30 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <p class="text-lg text-chalk font-semibold mb-2">Aucun historique de lecture</p>
                    <p class="text-mist mb-6">Vos dernières lectures apparaîtront ici.</p>
                </div>
            </template>
        </div>

    </div>

    <script>
    function library() {
        return {
            activeTab: 'favorites',
            favorites: [],
            history: [],
            progress: {},
            historyDetails: [], // Historique enrichi avec infos série

            init() {
                this.loadData();
                this.enrichHistory();
            },

            loadData() {
                this.favorites = window.HiddenScan.getFavorites().sort((a, b) => new Date(b.addedAt) - new Date(a.addedAt));
                this.history = window.HiddenScan.getHistory();
                this.progress = window.HiddenScan.getProgress();
            },

            removeFavorite(slug) {
                if(confirm('Retirer cette série des favoris ?')) {
                    window.HiddenScan.toggleFavorite(slug);
                    this.loadData();
                }
            },

            clearHistory() {
                if(confirm('Effacer tout votre historique de lecture ?')) {
                    const data = window.HiddenScan._getData();
                    data.history = [];
                    window.HiddenScan._save(data);
                    this.loadData();
                    this.enrichHistory();
                }
            },

            importLibrary() {
                document.getElementById('importFile').click();
            },

            handleImport(event) {
                const file = event.target.files[0];
                if (!file) return;

                window.HiddenScan.importData(file).then(() => {
                    alert('Bibliothèque importée avec succès !');
                    this.loadData();
                    this.enrichHistory();
                }).catch(err => {
                    alert('Erreur lors de l\'importation du fichier. Assurez-vous que c\'est un fichier JSON valide.');
                });
                event.target.value = ''; // Reset
            },

            enrichHistory() {
                // Pour l'historique, on va chercher les détails (titre, cover) dans les favoris
                // ou dans le progress si on les a, sinon on utilise juste le slug
                this.historyDetails = this.history.map(item => {
                    const fav = this.favorites.find(f => f.slug === item.manga);
                    const prog = this.progress[item.manga];
                    return {
                        ...item,
                        title: fav ? fav.title : item.manga.replace(/-/g, ' '),
                        cover: fav ? fav.cover : null,
                        percent: prog && prog.chapter === item.chapter ? prog.percent : 100,
                        chapterSlug: prog && prog.chapter === item.chapter ? prog.slug : 'chapitre-' + item.chapter // fallback
                    };
                });
            },

            formatDate(isoString) {
                if(!isoString) return '';
                const date = new Date(isoString);
                return date.toLocaleDateString('fr-FR', { day: 'numeric', month: 'short', hour: '2-digit', minute:'2-digit' });
            }
        };
    }
    </script>

</x-layouts.public>