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
    ];

    protected $casts = [
        'type' => \App\Enums\MangaType::class,
        'status' => \App\Enums\MangaStatus::class,
        'is_featured' => 'boolean',
    ];

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
}