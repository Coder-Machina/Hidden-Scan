<?php

namespace App\Models;

use App\Traits\HasAuditLogging;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Chapter extends Model
{
    use HasFactory, HasAuditLogging;

    protected $fillable = [
        'manga_id', 'number', 'title', 'slug', 'status',
        'uploaded_by', 'scheduled_at', 'published_at', 'views_count',
    ];

    protected $attributes = [
        'views_count' => 0,
    ];

    protected $casts = [
        'status' => \App\Enums\ChapterStatus::class,
        'scheduled_at' => 'datetime',
        'published_at' => 'datetime',
    ];

    protected function number(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: fn ($value) => floor($value) == $value ? (int)$value : (float)$value,
        );
    }

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

    public function readingProgress()
    {
        return $this->hasMany(ReadingProgress::class);
    }

    public function reports()
    {
        return $this->hasMany(ChapterReport::class);
    }

    /**
     * Enregistre une vue réaliste pour ce chapitre et pour le manga parent,
     * avec protection anti-spam par cooldown (IP ou compte connecté).
     */
    public function recordView(?string $ip = null, ?int $userId = null): bool
    {
        $ip = $ip ?? request()->ip();
        $userId = $userId ?? auth()->id();
        $identifier = $userId ? "user_{$userId}" : "ip_" . md5($ip ?? '127.0.0.1');
        $cacheKey = "view_cooldown:chapter_{$this->id}:{$identifier}";

        // Cooldown de 1 heure par utilisateur/IP sur ce chapitre
        if (\Illuminate\Support\Facades\Cache::has($cacheKey)) {
            return false;
        }

        \Illuminate\Support\Facades\Cache::put($cacheKey, true, now()->addHours(1));

        // Incrémente les vues du chapitre
        $this->increment('views_count');

        // Incrémente également les vues globales du manga parent
        if ($this->manga_id) {
            $this->manga()->increment('views_count');
        }

        return true;
    }

    public function scopePublished($query)
    {
        return $query->where('status', \App\Enums\ChapterStatus::PUBLIE)
            ->where(function ($q) {
                $q->whereNull('published_at')->orWhere('published_at', '<=', now()->addMinutes(1));
            });
    }

    protected static function booted(): void
    {
        static::saved(function (Chapter $chapter) {
            if ($chapter->status === \App\Enums\ChapterStatus::PUBLIE) {
                if ($chapter->wasRecentlyCreated || $chapter->wasChanged('status') || $chapter->wasChanged('published_at')) {
                    $chapter->notifyFavoriteUsers();
                    $chapter->notifyDiscord();
                }
            }
        });
    }

    /**
     * Notify Discord Webhook.
     */
    public function notifyDiscord(): void
    {
        try {
            app(\App\Services\DiscordWebhookService::class)->sendChapterNotification($this);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Discord webhook notification failed: ' . $e->getMessage());
        }
    }

    /**
     * Notify all users who have this manga in their favorites.
     */
    public function notifyFavoriteUsers(): void
    {
        $chapter = $this->loadMissing('manga');
        if (!$chapter->manga) {
            return;
        }

        $favoritedUsers = $chapter->manga->favoritedByUsers()->get();

        foreach ($favoritedUsers as $user) {
            $alreadyNotified = $user->notifications()
                ->where('data->chapter_id', $chapter->id)
                ->exists();

            if (!$alreadyNotified) {
                $user->notify(new \App\Notifications\NewChapterNotification($chapter));
            }
        }
    }

    /**
     * Publie le chapitre de manière strictement séquentielle selon l'ordre des numéros de chapitre.
     * Si un chapitre précédent est encore en traitement, ce chapitre attend.
     * Dès que ce chapitre est publié, il publie en cascade les chapitres suivants déjà prêts.
     */
    public function publishSequential(): void
    {
        // 1. Vérifie si un chapitre antérieur (< number) pour ce manga est encore en attente/traitement
        $hasPendingPrior = static::where('manga_id', $this->manga_id)
            ->where('number', '<', $this->number)
            ->where('status', \App\Enums\ChapterStatus::CONTROLE)
            ->exists();

        if ($hasPendingPrior) {
            \Illuminate\Support\Facades\Log::info("Chapitre {$this->number} (ID: {$this->id}) prêt mais en attente du chapitre précédent.");
            return;
        }

        // 2. Aucun chapitre précédent n'est bloquant : on publie ce chapitre
        $this->update([
            'status' => \App\Enums\ChapterStatus::PUBLIE,
            'published_at' => now(),
        ]);

        \Illuminate\Support\Facades\Log::info("Chapitre {$this->number} (ID: {$this->id}) publié séquentiellement.");

        // 3. Déclenche en cascade la publication des chapitres suivants du même manga qui sont déjà prêts en CONTROLE
        $waitingChapters = static::where('manga_id', $this->manga_id)
            ->where('number', '>', $this->number)
            ->where('status', \App\Enums\ChapterStatus::CONTROLE)
            ->whereHas('pages')
            ->orderBy('number', 'asc')
            ->get();

        $delay = 1;
        foreach ($waitingChapters as $nextChapter) {
            $isNextBlocked = static::where('manga_id', $this->manga_id)
                ->where('number', '<', $nextChapter->number)
                ->where('status', '!=', \App\Enums\ChapterStatus::PUBLIE)
                ->exists();

            if ($isNextBlocked) {
                break; // Le chapitre suivant attend son prédécesseur direct
            }

            $nextChapter->update([
                'status' => \App\Enums\ChapterStatus::PUBLIE,
                'published_at' => now()->addSeconds($delay++),
            ]);

            \Illuminate\Support\Facades\Log::info("Cascade : Chapitre {$nextChapter->number} (ID: {$nextChapter->id}) publié dans l'ordre.");
        }
    }
}