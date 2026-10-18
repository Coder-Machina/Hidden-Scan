<?php

namespace App\Console;

use App\Jobs\ScrapeNewChaptersJob;
use App\Models\ScraperManga;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->call(function () {
            $mangaIds = ScraperManga::where('status', 'active')
                ->where(function ($q) {
                    $q->whereNull('last_checked_at')
                      ->orWhere('last_checked_at', '<', now()->subHour());
                })
                ->distinct()
                ->pluck('manga_id');

            foreach ($mangaIds as $mangaId) {
                $manga = \App\Models\Manga::find($mangaId);
                if ($manga) {
                    \App\Jobs\ScrapeMultiSourceMangaJob::dispatch($manga)->onQueue('scraper');
                }
            }
        })->hourly()->name('auto-sync-chapitres');
    }
}
