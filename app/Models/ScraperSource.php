<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ScraperSource extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'base_url',
        'sel_chapter_list',
        'sel_chapter_title',
        'sel_chapter_url',
        'sel_chapter_date',
        'sel_chapter_pages',
        'headers',
        'active',
    ];

    protected $casts = [
        'headers' => 'array',
        'active'  => 'boolean',
    ];

    public function scraperMangas(): HasMany
    {
        return $this->hasMany(ScraperManga::class);
    }
}
