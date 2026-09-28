<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChapterReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'chapter_id',
        'manga_id',
        'user_id',
        'type',
        'page_number',
        'message',
        'status',
        'ip_address',
    ];

    protected $casts = [
        'page_number' => 'integer',
    ];

    public function chapter()
    {
        return $this->belongsTo(Chapter::class);
    }

    public function manga()
    {
        return $this->belongsTo(Manga::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'en_attente');
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'page_manquante' => 'Page manquante',
            'ordre_inverse' => 'Ordre inversé',
            'image_corrompue' => 'Image illisible',
            'mauvaise_traduction' => 'Traduction / Coquille',
            default => 'Autre problème',
        };
    }

    public function getTypeColorAttribute(): string
    {
        return match ($this->type) {
            'page_manquante' => 'danger',
            'image_corrompue' => 'warning',
            'ordre_inverse' => 'info',
            'mauvaise_traduction' => 'purple',
            default => 'gray',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'en_attente' => 'En attente',
            'resolu' => 'Résolu',
            'rejete' => 'Rejeté',
            default => ucfirst($this->status),
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'en_attente' => 'warning',
            'resolu' => 'success',
            'rejete' => 'gray',
            default => 'gray',
        };
    }
}
