<?php

namespace App\Models;

use App\Traits\HasAuditLogging;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Manga extends Model
{
    use HasFactory, HasAuditLogging;

   protected $fillable = [
        'title', 'slug', 'synopsis', 'cover_image', 'banner_image',
        'type', 'status',
        'release_year', 'views_count', 'is_featured',
        'average_rating', 'ratings_count',
        'discord_role_id', 'discord_webhook_url', 'drive_url', 'va_url', 'va_chapter_number', 'is_rush',
        'is_external', 'team_name',
        'trad_user', 'check_user', 'clean_user', 'edit_user', 'qc_user',
        'step_trad_chapter', 'step_check_chapter', 'step_clean_chapter', 'step_edit_chapter', 'step_qc_chapter',
    ];

    protected $casts = [
        'type' => \App\Enums\MangaType::class,
        'status' => \App\Enums\MangaStatus::class,
        'is_featured' => 'boolean',
        'is_rush' => 'boolean',
        'is_external' => 'boolean',
    ];

    protected $attributes = [
        'views_count' => 0,
        'average_rating' => 0,
        'ratings_count' => 0,
    ];

    public function setViewsCountAttribute($value): void
    {
        $this->attributes['views_count'] = (int) ($value ?? 0);
    }

    public function getCoverUrlAttribute(): string
    {
        if ($this->cover_image) {
            if (str_starts_with($this->cover_image, 'http://') || str_starts_with($this->cover_image, 'https://') || str_starts_with($this->cover_image, '/')) {
                return $this->cover_image;
            }

            return \Illuminate\Support\Facades\Storage::url($this->cover_image);
        }

        return asset('images/default-cover.jpg');
    }

    public function getBannerUrlAttribute(): ?string
    {
        if ($this->banner_image) {
            if (str_starts_with($this->banner_image, 'http://') || str_starts_with($this->banner_image, 'https://') || str_starts_with($this->banner_image, '/')) {
                return $this->banner_image;
            }

            return \Illuminate\Support\Facades\Storage::url($this->banner_image);
        }

        return null;
    }

    public function authors()
    {
        return $this->belongsToMany(Author::class, 'author_manga');
    }

    public function artists()
    {
        return $this->belongsToMany(Artist::class, 'artist_manga');
    }

    public function genres()
    {   
        return $this->belongsToMany(Genre::class, 'manga_genre');
    }

    public function tags()
    {
         return $this->belongsToMany(Tag::class, 'manga_tag');
    }

    public function chapters()
    {
        return $this->hasMany(Chapter::class)->orderBy('number');
    }

    public function readingProgress()
    {
        return $this->hasMany(ReadingProgress::class);
    }

    public function favorites()
    {
        return $this->hasMany(Favorite::class);
    }

    public function favoritedByUsers()
    {
        return $this->belongsToMany(User::class, 'favorites')->withTimestamps();
    }

    public function reports()
    {
        return $this->hasMany(ChapterReport::class);
    }

    public function staffContributions()
    {
        return $this->hasMany(StaffContribution::class);
    }
}