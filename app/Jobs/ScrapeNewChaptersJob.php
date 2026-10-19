<?php

namespace App\Jobs;

use App\Models\Chapter;
use App\Models\ScraperManga;
use App\Services\ScraperService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ScrapeNewChaptersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 1800;

    public function __construct(
        public ScraperManga $scraperManga
    ) {}

    public function handle(ScraperService $scraperService): void
    {
        @ini_set('max_execution_time', '0');
        @set_time_limit(0);
        @ini_set('memory_limit', '512M');

        try {
            $manga = $this->scraperManga->manga;
            if ($manga) {
                $scraperService->syncMangaMultiSources($manga);
                return;
            }

            $this->scraperManga->update([
                'last_checked_at' => now(),
            ]);

            $chapters = $scraperService->getChapterList($this->scraperManga);

            // Filtre les chapitres non encore existants ou sans pages
            $newChapters = [];
            foreach ($chapters as $ch) {
                $existsWithPages = Chapter::where('manga_id', $this->scraperManga->manga_id)
                    ->where('number', $ch['number'])
                    ->whereHas('pages')
                    ->exists();

                if (!$existsWithPages) {
                    $newChapters[] = $ch;
                }
            }

            Log::info("ScrapeNewChaptersJob: " . count($newChapters) . " nouveaux chapitres à importer pour le manga {$this->scraperManga->manga_id}");

            foreach ($newChapters as $chapterData) {
                try {
                    $scraperService->scrapeSingleChapter($this->scraperManga, $chapterData);

                    // Pause obligatoire de 2 secondes entre chaque chapitre
                    sleep(2);
                } catch (\Throwable $e) {
                    Log::error("ScrapeNewChaptersJob: Échec sur le chapitre {$chapterData['number']} du manga {$this->scraperManga->manga_id}: " . $e->getMessage());
                }
            }

            $this->scraperManga->update([
                'last_checked_at' => now(),
                'status'          => 'active',
                'last_error'      => null,
            ]);

            Log::info("ScrapeNewChaptersJob terminé pour le manga {$this->scraperManga->manga_id}");
        } catch (\Throwable $e) {
            Log::error("ScrapeNewChaptersJob fatal error pour le manga {$this->scraperManga->manga_id}: " . $e->getMessage());

            $this->scraperManga->update([
                'status'     => 'error',
                'last_error' => $e->getMessage(),
            ]);
        }
    }
}
