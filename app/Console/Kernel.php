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
            ScraperManga::where('status', 'active')
                ->where(function ($q) {
                    $q->whereNull('last_checked_at')
                      ->orWhere('last_checked_at', '<', now()->subHour());
                })
                ->each(fn($sm) =>
                    ScrapeNewChaptersJob::dispatch($sm)->onQueue('scraper')
                );
        })->hourly()->name('auto-sync-chapitres');
    }
}
