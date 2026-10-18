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
        'last_checked_at',
        'status',
        'last_error',
    ];

    protected $casts = [
        'priority'        => 'integer',
        'last_checked_at' => 'datetime',
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
