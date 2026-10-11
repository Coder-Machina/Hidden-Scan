<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReleaseSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'day_of_week',
        'manga_id',
        'release_time',
        'status_label',
        'is_priority',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_priority' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function manga(): BelongsTo
    {
        return $this->belongsTo(Manga::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForDay($query, string $day)
    {
        return $query->where('day_of_week', strtolower($day));
    }
}
