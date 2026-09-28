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
    'avatar', 'banner', 'bio', 'xp', 'favorite_genre', 'reader_mode',
    'is_banned', 'banned_at', 'ban_reason',
    'is_comment_banned', 'comment_banned_at', 'comment_ban_reason',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, \Spatie\Permission\Traits\HasRoles;

    public function canAccessPanel(Panel $panel): bool
    {
        return ! $this->isBanned();
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
