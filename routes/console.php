<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('chapters:publish-scheduled')->everyMinute();

Schedule::call(function () {
    \App\Models\ScraperManga::where('status', 'active')
        ->where(function ($q) {
            $q->whereNull('last_checked_at')
              ->orWhere('last_checked_at', '<', now()->subHour());
        })
        ->each(fn($sm) =>
            \App\Jobs\ScrapeNewChaptersJob::dispatch($sm)->onQueue('scraper')
        );
})->hourly()->name('auto-sync-chapitres');

