<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScraperManga extends Model
{
    use HasFactory;

    protected $fillable = [
        'manga_id',
        'scraper_source_id',
        'source_url',
        'priority',
        'last_chapter_scraped',
        'current_scraping_chapter',
        'total_chapters_detected',
        'last_checked_at',
        'status',
        'cancel_requested',
        'last_error',
    ];

    protected $casts = [
        'priority'                 => 'integer',
        'total_chapters_detected'  => 'integer',
        'cancel_requested'         => 'boolean',
        'last_checked_at'          => 'datetime',
    ];

    public function manga(): BelongsTo
    {
        return $this->belongsTo(Manga::class);
    }

    public function scraperSource(): BelongsTo
    {
        return $this->belongsTo(ScraperSource::class, 'scraper_source_id');
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(ScraperSource::class, 'scraper_source_id');
    }
}
