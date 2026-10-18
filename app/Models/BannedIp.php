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
        'banned_until',
    ];

    protected function casts(): array
    {
        return [
            'banned_until' => 'datetime',
        ];
    }

    public function banner()
    {
        return $this->belongsTo(User::class, 'banned_by');
    }

    /**
     * Vérifie si une adresse IP est activement bannie en tenant compte de l'expiration temporaire.
     */
    public static function isIpBlocked(?string $ip, string $type = 'comments'): bool
    {
        if (! $ip || in_array($ip, ['127.0.0.1', '::1', 'localhost'], true)) {
            return false;
        }

        $sha1 = sha1($ip);
        $sha256 = hash('sha256', $ip . config('app.key'));

        $bannedIp = static::where(function ($query) use ($ip, $sha1, $sha256) {
            $query->where('ip_address', $ip)
                  ->orWhere('ip_hash', $sha1)
                  ->orWhere('ip_hash', $sha256);
        })->when($type !== 'all', function ($q) {
            $q->whereIn('ban_type', ['comments', 'all']);
        }, function ($q) {
            $q->where('ban_type', 'all');
        })->first();

        if (! $bannedIp) {
            return false;
        }

        // Si le ban temporaire est expiré, le supprimer automatiquement
        if ($bannedIp->banned_until && now()->greaterThanOrEqualTo($bannedIp->banned_until)) {
            $bannedIp->delete();
            return false;
        }

        return true;
    }
}
