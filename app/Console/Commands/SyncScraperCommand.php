<?php

namespace App\Console\Commands;

use App\Models\Manga;
use App\Models\ScraperManga;
use App\Services\ScraperService;
use Illuminate\Console\Command;

class SyncScraperCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scraper:sync {manga_id? : ID du manga à synchroniser (optionnel)} {--limit= : Limite de chapitres à importer}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronise intelligemment les chapitres d\'un ou plusieurs mangas depuis leurs sources configurées';

    /**
     * Execute the console command.
     */
    public function handle(ScraperService $scraperService): int
    {
        $mangaId = $this->argument('manga_id');

        if ($mangaId) {
            $manga = Manga::find($mangaId);
            if (!$manga) {
                $this->error("Manga avec l'ID {$mangaId} introuvable.");
                return Command::FAILURE;
            }
            $mangas = collect([$manga]);
        } else {
            $mangaIds = ScraperManga::where('status', 'active')->distinct()->pluck('manga_id');
            $mangas = Manga::whereIn('id', $mangaIds)->get();
        }

        if ($mangas->isEmpty()) {
            $this->info("Aucun manga actif avec source de scraping configurée.");
            return Command::SUCCESS;
        }

        $this->info("Démarrage de la synchronisation pour " . $mangas->count() . " manga(s)...");

        foreach ($mangas as $manga) {
            $this->line("──────────────────────────────────────────");
            $this->info("Manga: {$manga->title} (ID: {$manga->id})");

            $sources = $manga->scraperMangas()->with('scraperSource')->orderBy('priority', 'asc')->get();
            $this->line("  Sources configurées : " . $sources->count());
            foreach ($sources as $idx => $s) {
                $sourceName = $s->scraperSource?->name ?? 'Inconnue';
                $this->line("  [#".($idx+1)."] {$sourceName} : {$s->source_url} ({$s->status})");
            }

            try {
                $result = $scraperService->syncMangaMultiSources($manga);
                $imported = $result['imported'] ?? 0;
                $this->info("  => {$imported} nouveau(x) chapitre(s) importé(s).");
                if (!empty($result['errors'])) {
                    foreach ($result['errors'] as $err) {
                        $this->warn("  [Alerte Relais] " . $err);
                    }
                }
            } catch (\Throwable $e) {
                $this->error("  Erreur fatale : " . $e->getMessage());
            }
        }

        $this->line("──────────────────────────────────────────");
        $this->info("Synchronisation terminée !");

        return Command::SUCCESS;
    }
}
