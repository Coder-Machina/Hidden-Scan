<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Manga extends Model
{
    use HasFactory;

    protected $fillable = [
        'title', 'slug', 'synopsis', 'cover_image', 'banner_image',
        'author_id', 'artist_id', 'type', 'status',
        'release_year', 'views_count', 'is_featured',
    ];

    public function author()
    {
        return $this->belongsTo(Author::class);
    }

    public function artist()
    {
        return $this->belongsTo(Artist::class);
    }

    public function genres()
    {
        return $this->belongsToMany(Genre::class);
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class);
    }

    public function chapters()
    {
        return $this->hasMany(Chapter::class)->orderBy('number');
    }
}