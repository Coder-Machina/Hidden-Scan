<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BannedIp extends Model
{
    use HasFactory;

    protected $fillable = [
        'ip_hash',
        'ip_address',
        'ban_type',
        'reason',
        'banned_by',
    ];

    public function banner()
    {
        return $this->belongsTo(User::class, 'banned_by');
    }
}
