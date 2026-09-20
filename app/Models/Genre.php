<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Genre extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug'];

    public function mangas()
    {
        return $this->belongsToMany(Manga::class, 'manga_genre');
    }
}