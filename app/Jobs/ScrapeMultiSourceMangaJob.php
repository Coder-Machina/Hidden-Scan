<?php

namespace App\Jobs;

use App\Models\Manga;
use App\Services\ScraperService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ScrapeMultiSourceMangaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 3600;

    public function __construct(
        public Manga $manga
    ) {}

    public function handle(ScraperService $scraperService): void
    {
        @ini_set('max_execution_time', '0');
        @set_time_limit(0);
        @ini_set('memory_limit', '512M');

        Log::info("Début synchronisation multi-sources avec relais intelligent pour « {$this->manga->title} » (ID: {$this->manga->id})");

        $result = $scraperService->syncMangaMultiSources($this->manga);

        Log::info("Fin synchronisation multi-sources pour « {$this->manga->title} » : {$result['imported']} chapitres importés.", [
            'errors' => $result['errors'],
        ]);
    }
}
