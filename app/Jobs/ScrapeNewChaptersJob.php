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
            $this->scraperManga->refresh();
            if ($this->scraperManga->cancel_requested) {
                $this->scraperManga->update([
                    'status'                   => 'annulé',
                    'cancel_requested'         => false,
                    'current_scraping_chapter' => null,
                ]);
                return;
            }

            $this->scraperManga->update([
                'status'           => 'importing',
                'cancel_requested' => false,
                'last_checked_at'  => now(),
                'last_error'       => null,
            ]);

            $chapters = $scraperService->getChapterList($this->scraperManga);
            $totalDetected = count($chapters);

            $this->scraperManga->update([
                'total_chapters_detected' => $totalDetected,
            ]);

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
                // Dans le job, à chaque début de chapitre, vérifier cancel_requested
                $this->scraperManga->refresh();
                if ($this->scraperManga->cancel_requested) {
                    $this->scraperManga->update([
                        'status'                   => 'annulé',
                        'cancel_requested'         => false,
                        'current_scraping_chapter' => null,
                    ]);
                    Log::info("Arrêt propre de ScrapeNewChaptersJob pour le manga {$this->scraperManga->manga_id} suite à annulation.");
                    return; // arrêt propre
                }

                $this->scraperManga->update([
                    'status'                   => 'importing',
                    'current_scraping_chapter' => (string) $chapterData['number'],
                ]);

                try {
                    $chapter = $scraperService->scrapeSingleChapter($this->scraperManga, $chapterData);

                    // Vérifie après téléchargement des images si annulation survenue
                    $this->scraperManga->refresh();
                    if ($this->scraperManga->cancel_requested || $this->scraperManga->status === 'annulé') {
                        $this->scraperManga->update([
                            'status'                   => 'annulé',
                            'cancel_requested'         => false,
                            'current_scraping_chapter' => null,
                        ]);
                        return; // arrêt propre
                    }

                    if ($chapter && $chapter->pages()->exists()) {
                        $this->scraperManga->update([
                            'last_chapter_scraped' => (string) $chapterData['number'],
                        ]);
                    }

                    // Pause obligatoire de 2 secondes entre chaque chapitre
                    sleep(2);
                } catch (\Throwable $e) {
                    Log::error("ScrapeNewChaptersJob: Échec sur le chapitre {$chapterData['number']} du manga {$this->scraperManga->manga_id}: " . $e->getMessage());
                }
            }

            // Après l'import, remettre cancel_requested à false et status à 'active'
            $this->scraperManga->refresh();
            if (!$this->scraperManga->cancel_requested && $this->scraperManga->status !== 'annulé') {
                $this->scraperManga->update([
                    'last_checked_at'          => now(),
                    'status'                   => 'active',
                    'cancel_requested'         => false,
                    'current_scraping_chapter' => null,
                    'last_error'               => null,
                ]);
            }

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
