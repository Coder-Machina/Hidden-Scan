<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Services\MurimRankService;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'name', 'username', 'profile_public', 'email', 'password',
    'pass_code', 'last_ip_address',
    'avatar', 'banner', 'bio', 'xp', 'streak', 'qi_shields', 'spiritual_beast', 'avatar_frame', 'custom_title_color', 'last_read_at', 'favorite_genre', 'reader_mode',
    'tier_list', 'top_mangas',
    'is_banned', 'banned_at', 'banned_until', 'ban_reason',
    'is_comment_banned', 'comment_banned_at', 'comment_banned_until', 'comment_ban_reason',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, \Spatie\Permission\Traits\HasRoles;

    protected $fillable = [
        'name', 'username', 'profile_public', 'email', 'password',
        'pass_code', 'last_ip_address',
        'avatar', 'banner', 'bio', 'xp', 'streak', 'qi_shields', 'spiritual_beast', 'avatar_frame', 'custom_title_color', 'last_read_at', 'favorite_genre', 'reader_mode',
        'tier_list', 'top_mangas',
        'is_banned', 'banned_at', 'banned_until', 'ban_reason',
        'is_comment_banned', 'comment_banned_at', 'comment_banned_until', 'comment_ban_reason',
    ];

    /**
     * Le profil est public par défaut si non défini
     */
    public function getProfilePublicAttribute($value): bool
    {
        return $value ?? true;
    }

    /**
     * Génère un code Pass secret unique (ex: HS-7F2A-9K3M-P8X4)
     */
    public static function generateUniquePassCode(): string
    {
        $chars = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
        do {
            $p1 = '';
            $p2 = '';
            $p3 = '';
            for ($i = 0; $i < 4; $i++) {
                $p1 .= $chars[random_int(0, strlen($chars) - 1)];
            }
            for ($i = 0; $i < 4; $i++) {
                $p2 .= $chars[random_int(0, strlen($chars) - 1)];
            }
            for ($i = 0; $i < 4; $i++) {
                $p3 .= $chars[random_int(0, strlen($chars) - 1)];
            }
            $code = "HS-{$p1}-{$p2}-{$p3}";
        } while (static::where('pass_code', $code)->exists());

        return $code;
    }

    protected static function booted(): void
    {
        static::creating(function (User $user) {
            if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'username') && empty($user->username)) {
                $base = \Illuminate\Support\Str::slug($user->name ?: 'user', '');
                if (empty($base)) {
                    $base = 'user';
                }
                $username = strtolower($base);
                $counter = 1;
                while (static::where('username', $username)->exists()) {
                    $username = strtolower($base).$counter;
                    $counter++;
                }
                $user->username = $username;
            }
        });
    }

    /**
     * Vérifie si l'utilisateur est un compte fondateur protégé contre le bannissement
     */
    public function isFounder(): bool
    {
        $founderEmails = [
            'meliodasdsama006@gmail.com',
            'meliodasdsala006@gmail.com',
            'admin@hiddenscan.com',
            'admin@hidden-scan.com',
        ];

        return $this->email && in_array(strtolower($this->email), $founderEmails, true);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        if ($this->isBanned()) {
            return false;
        }

        // Les comptes administrateurs fondateurs ont un accès permanent garanti
        if ($this->isFounder()) {
            return true;
        }

        return $this->hasAnyRole([
            'admin', 'Admin',
            'modo', 'Modo', 'moderateur', 'Modérateur',
            'uploader', 'Uploader',
        ]);
    }

    /**
     * Vérifie si l'utilisateur est un membre du staff (Admin, Modo, Uploader, Fondateur)
     */
    public function isStaffOrAdmin(): bool
    {
        if ($this->isFounder()) {
            return true;
        }

        return $this->hasAnyRole([
            'admin', 'Admin',
            'modo', 'Modo', 'moderateur', 'Modérateur',
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
        if (($this->email && in_array(strtolower($this->email), $founderEmails, true)) || $this->hasAnyRole(['admin', 'Admin'])) {
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
            'streak' => 'integer',
            'last_read_at' => 'datetime',
            'tier_list' => 'array',
            'top_mangas' => 'array',
            'is_banned' => 'boolean',
            'banned_at' => 'datetime',
            'banned_until' => 'datetime',
            'is_comment_banned' => 'boolean',
            'comment_banned_at' => 'datetime',
            'comment_banned_until' => 'datetime',
            'profile_public' => 'boolean',
        ];
    }

    /**
     * Calcule le statut de cultivation Murim de l'utilisateur
     */
    public function getMurimRankAttribute(): array
    {
        $readCount = $this->readingProgress()->where('is_read', true)->count();

        return MurimRankService::calculate($readCount, (int) ($this->streak ?? 0), (int) ($this->xp ?? 0), $this);
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
        if (! $this->is_banned) {
            return false;
        }

        // Si le ban temporaire est expiré, réhabiliter le compte automatiquement
        if ($this->banned_until && now()->greaterThanOrEqualTo($this->banned_until)) {
            $this->updateQuietly([
                'is_banned' => false,
                'banned_at' => null,
                'banned_until' => null,
                'ban_reason' => null,
            ]);

            return false;
        }

        return true;
    }

    public function isCommentBanned(): bool
    {
        if ($this->isBanned()) {
            return true;
        }

        if (! $this->is_comment_banned) {
            return false;
        }

        // Si le ban de commentaire temporaire est expiré, le réhabiliter automatiquement
        if ($this->comment_banned_until && now()->greaterThanOrEqualTo($this->comment_banned_until)) {
            $this->updateQuietly([
                'is_comment_banned' => false,
                'comment_banned_at' => null,
                'comment_banned_until' => null,
                'comment_ban_reason' => null,
            ]);

            return false;
        }

        return true;
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
            $lvl >= 5 => 'ARGENT',
            default => 'BRONZE',
        };
    }

    /**
     * Avatar URL resolver
     */
    public function getAvatarUrlAttribute(): string
    {
        if ($this->avatar) {
            if (str_starts_with($this->avatar, 'http://') || str_starts_with($this->avatar, 'https://') || str_starts_with($this->avatar, 'data:image/')) {
                return $this->avatar;
            }
            if (str_starts_with($this->avatar, '/')) {
                return $this->avatar;
            }
            if (str_starts_with($this->avatar, 'preset:')) {
                $presetName = substr($this->avatar, 7);

                return asset('images/avatars/'.$presetName.'.jpg');
            }

            return Storage::url($this->avatar);
        }

        if (($this->name === 'Meliodas' || str_contains(strtolower($this->email ?? ''), 'meliodas')) && file_exists(public_path('images/avatars/meliodas.png'))) {
            return asset('images/avatars/meliodas.png');
        }

        return 'https://ui-avatars.com/api/?name='.urlencode($this->name).'&background=1c2438&color=60a5fa&size=200&bold=true';
    }

    /**
     * Banner URL resolver
     */
    public function getBannerUrlAttribute(): string
    {
        if ($this->banner) {
            if (str_starts_with($this->banner, 'http://') || str_starts_with($this->banner, 'https://') || str_starts_with($this->banner, 'data:image/')) {
                return $this->banner;
            }

            return Storage::url($this->banner);
        }

        return asset('images/banners/default.jpg');
    }

    public function readingProgress()
    {
        return $this->hasMany(ReadingProgress::class);
    }

    public function readingHistory()
    {
        return $this->readingProgress();
    }

    /**
     * Send the password reset notification.
     * In local / dev mode (e.g. MAIL_MAILER=log), store the reset url in session for instant browser testing.
     *
     * @param  string  $token
     */
    public function sendPasswordResetNotification($token): void
    {
        if (config('mail.default') === 'log' || app()->environment(['local', 'testing'])) {
            session(['reset_url' => route('password.reset', [
                'token' => $token,
                'email' => $this->email,
            ])]);
        }

        $this->notify(new ResetPassword($token));
    }
}
