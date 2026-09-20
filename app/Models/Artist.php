<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Artist extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug'];

    public function mangas()
    {
        return $this->hasMany(Manga::class);
    }
}