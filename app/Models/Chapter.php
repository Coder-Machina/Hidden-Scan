<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Chapter extends Model
{
    use HasFactory;

    protected $fillable = [
        'manga_id', 'number', 'title', 'slug', 'status',
        'uploaded_by', 'scheduled_at', 'published_at', 'views_count',
    ];

    protected $casts = [
        'status' => \App\Enums\ChapterStatus::class,
        'scheduled_at' => 'datetime',
        'published_at' => 'datetime',
    ];

    public function manga()
    {
        return $this->belongsTo(Manga::class);
    }

    public function pages()
    {
        return $this->hasMany(ChapterPage::class)->orderBy('page_number');
    }

    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}