<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('chapters:publish-scheduled')->everyMinute();

Schedule::call(function () {
    $mangaIds = \App\Models\ScraperManga::where('status', 'active')
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

