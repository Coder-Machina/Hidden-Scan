<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

#[Fillable([
    'name', 'email', 'password',
    'pass_code', 'last_ip_address',
    'avatar', 'banner', 'bio', 'xp', 'favorite_genre', 'reader_mode',
    'is_banned', 'banned_at', 'ban_reason',
    'is_comment_banned', 'comment_banned_at', 'comment_ban_reason',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, \Spatie\Permission\Traits\HasRoles;

    /**
     * Génère un code Pass secret unique (ex: HS-7F2A-9K3M-P8X4)
     */
    public static function generateUniquePassCode(): string
    {
        $chars = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
        do {
            $p1 = ''; $p2 = ''; $p3 = '';
            for ($i = 0; $i < 4; $i++) $p1 .= $chars[random_int(0, strlen($chars) - 1)];
            for ($i = 0; $i < 4; $i++) $p2 .= $chars[random_int(0, strlen($chars) - 1)];
            for ($i = 0; $i < 4; $i++) $p3 .= $chars[random_int(0, strlen($chars) - 1)];
            $code = "HS-{$p1}-{$p2}-{$p3}";
        } while (static::where('pass_code', $code)->exists());

        return $code;
    }

    public function canAccessPanel(Panel $panel): bool
    {
        if ($this->isBanned()) {
            return false;
        }

        // Les comptes administrateurs fondateurs ont un accès permanent garanti
        $founderEmails = [
            'meliodasdsama006@gmail.com',
            'meliodasdsala006@gmail.com',
            'admin@hiddenscan.com',
            'admin@hidden-scan.com',
        ];
        if ($this->email && in_array(strtolower($this->email), $founderEmails, true)) {
            return true;
        }

        return $this->hasAnyRole([
            'owner', 'Owner',
            'admin', 'Admin', 'Administrateur',
            'modo', 'Modo', 'Modérateur', 'moderateur',
            'uploader', 'Uploader',
        ]);
    }

    /**
     * Badge Staff : Admin (rouge), Modo (violet), Uploader (bleu ciel)
     */
    public function getStaffBadgeAttribute(): ?array
    {
        $founderEmails = [
            'meliodasdsama006@gmail.com',
            'meliodasdsala006@gmail.com',
            'admin@hiddenscan.com',
            'admin@hidden-scan.com',
        ];
        if (($this->email && in_array(strtolower($this->email), $founderEmails, true)) || $this->hasAnyRole(['owner', 'Owner', 'admin', 'Admin', 'Administrateur'])) {
            return [
                'name' => 'Admin',
                'color' => 'red',
                'bg' => 'bg-red-600/20',
                'text' => 'text-red-400',
                'border' => 'border-red-500/40',
                'hex' => '#dc2626',
            ];
        }

        if ($this->hasAnyRole(['modo', 'Modo', 'Modérateur', 'moderateur'])) {
            return [
                'name' => 'Modo',
                'color' => 'purple',
                'bg' => 'bg-purple-600/20',
                'text' => 'text-purple-300',
                'border' => 'border-purple-500/40',
                'hex' => '#8b5cf6',
            ];
        }

        if ($this->hasAnyRole(['uploader', 'Uploader'])) {
            return [
                'name' => 'Uploader',
                'color' => 'sky',
                'bg' => 'bg-sky-500/20',
                'text' => 'text-sky-300',
                'border' => 'border-sky-400/40',
                'hex' => '#0284c7',
            ];
        }

        return null;
    }    

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'xp' => 'integer',
            'is_banned' => 'boolean',
            'banned_at' => 'datetime',
            'is_comment_banned' => 'boolean',
            'comment_banned_at' => 'datetime',
        ];
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function favorites()
    {
        return $this->hasMany(Favorite::class);
    }

    public function favoriteMangas()
    {
        return $this->belongsToMany(Manga::class, 'favorites')->withTimestamps();
    }

    public function isBanned(): bool
    {
        return (bool) $this->is_banned;
    }

    public function isCommentBanned(): bool
    {
        return (bool) ($this->is_banned || $this->is_comment_banned);
    }

    /**
     * Level calculation (1 Level every 50 XP, minimum level 1)
     */
    public function getLevelAttribute(): int
    {
        return max(1, (int) floor(($this->xp ?? 0) / 50) + 1);
    }

    /**
     * XP progress towards next level (0 - 49)
     */
    public function getXpInCurrentLevelAttribute(): int
    {
        return ($this->xp ?? 0) % 50;
    }

    /**
     * Tier badge based on level
     */
    public function getTierBadgeAttribute(): string
    {
        $lvl = $this->level;
        return match (true) {
            $lvl >= 30 => 'DIAMANT',
            $lvl >= 20 => 'PLATINE',
            $lvl >= 10 => 'OR',
            $lvl >= 5  => 'ARGENT',
            default    => 'BRONZE',
        };
    }

    /**
     * Avatar URL resolver
     */
    public function getAvatarUrlAttribute(): string
    {
        if ($this->avatar) {
            if (str_starts_with($this->avatar, 'http://') || str_starts_with($this->avatar, 'https://')) {
                return $this->avatar;
            }
            if (str_starts_with($this->avatar, 'preset:')) {
                $presetName = substr($this->avatar, 7);
                return asset('images/avatars/' . $presetName . '.jpg');
            }
            return \Illuminate\Support\Facades\Storage::url($this->avatar);
        }

        return 'https://ui-avatars.com/api/?name=' . urlencode($this->name) . '&background=1c2438&color=60a5fa&size=200&bold=true';
    }

    /**
     * Banner URL resolver
     */
    public function getBannerUrlAttribute(): string
    {
        if ($this->banner) {
            if (str_starts_with($this->banner, 'http://') || str_starts_with($this->banner, 'https://')) {
                return $this->banner;
            }
            return \Illuminate\Support\Facades\Storage::url($this->banner);
        }

        return asset('images/banners/default.jpg');
    }

    public function readingProgress()
    {
        return $this->hasMany(ReadingProgress::class);
    }

    /**
     * Send the password reset notification.
     * In local / dev mode (e.g. MAIL_MAILER=log), store the reset url in session for instant browser testing.
     *
     * @param  string  $token
     * @return void
     */
    public function sendPasswordResetNotification($token): void
    {
        if (config('mail.default') === 'log' || app()->environment(['local', 'testing'])) {
            session(['reset_url' => route('password.reset', [
                'token' => $token,
                'email' => $this->email,
            ])]);
        }

        $this->notify(new \Illuminate\Auth\Notifications\ResetPassword($token));
    }
}
