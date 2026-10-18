<?php

namespace App\Jobs;

use App\Models\ScraperManga;
use App\Services\ScraperService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ScrapeFullMangaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Timeout élevé pour permettre l'import complet en arrière-plan.
     */
    public int $timeout = 3600;

    public function __construct(
        public ScraperManga $scraperManga
    ) {}

    public function handle(ScraperService $scraperService): void
    {
        @ini_set('max_execution_time', '0');
        @set_time_limit(0);
        @ini_set('memory_limit', '512M');

        try {
            $this->scraperManga->update([
                'status'          => 'active',
                'last_checked_at' => now(),
                'last_error'      => null,
            ]);

            $chapters = $scraperService->getChapterList($this->scraperManga);

            Log::info("ScrapeFullMangaJob: " . count($chapters) . " chapitres détectés pour le manga {$this->scraperManga->manga_id}");

            foreach ($chapters as $chapterData) {
                try {
                    $scraperService->scrapeSingleChapter($this->scraperManga, $chapterData);

                    // Pause obligatoire de 2 secondes entre chaque chapitre
                    sleep(2);
                } catch (\Throwable $e) {
                    Log::error("ScrapeFullMangaJob: Échec sur le chapitre {$chapterData['number']} du manga {$this->scraperManga->manga_id}: " . $e->getMessage());
                }
            }

            $this->scraperManga->update([
                'last_checked_at' => now(),
            ]);

            Log::info("ScrapeFullMangaJob terminé pour le manga {$this->scraperManga->manga_id}");
        } catch (\Throwable $e) {
            Log::error("ScrapeFullMangaJob fatal error pour le manga {$this->scraperManga->manga_id}: " . $e->getMessage());

            $this->scraperManga->update([
                'status'     => 'error',
                'last_error' => $e->getMessage(),
            ]);
        }
    }
}
