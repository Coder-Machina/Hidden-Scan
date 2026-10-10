<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StaffContribution extends Model
{
    use HasFactory;

    protected $fillable = [
        'manga_id',
        'step',
        'chapter_number',
        'discord_user_id',
        'discord_user_name',
    ];

    public function manga()
    {
        return $this->belongsTo(Manga::class);
    }
}
