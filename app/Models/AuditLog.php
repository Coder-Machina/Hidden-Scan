<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $fillable = [
        'user_id',
        'action',
        'model_type',
        'model_id',
        'old_values',
        'new_values',
        'ip_address',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get a human-readable model type name in French.
     */
    public function getModelNameAttribute(): string
    {
        if (in_array($this->action, ['ban_account', 'ban_comments', 'unban'], true)) {
            return 'Sanction';
        }

        if (!$this->model_type) {
            return '—';
        }

        $base = class_basename($this->model_type);

        return match ($base) {
            'Chapter' => 'Chapitre',
            'Manga' => 'Œuvre',
            'Comment' => 'Commentaire',
            'User' => 'Utilisateur',
            'Artist' => 'Artiste',
            'Author' => 'Auteur',
            'Genre' => 'Genre',
            'Tag' => 'Tag',
            'CommentReport' => 'Signalement',
            'BannedIp' => 'IP Bannie',
            default => $base,
        };
    }

    /**
     * Get the related model instance (if it still exists).
     */
    public function getSubjectAttribute()
    {
        if (!$this->model_type || !$this->model_id) {
            return null;
        }

        if (!class_exists($this->model_type)) {
            return null;
        }

        try {
            return $this->model_type::find($this->model_id);
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Resolve the associated Œuvre (Manga) title if applicable.
     */
    public function getMangaNameAttribute(): ?string
    {
        if (!empty($this->new_values['manga'])) {
            return (string) $this->new_values['manga'];
        }
        if (!empty($this->new_values['manga_title'])) {
            return (string) $this->new_values['manga_title'];
        }

        $base = class_basename($this->model_type ?? '');

        if ($base === 'Chapter') {
            if ($this->subject && $this->subject->manga) {
                return $this->subject->manga->title;
            }

            $mangaId = $this->new_values['manga_id'] ?? $this->old_values['manga_id'] ?? null;
            if ($mangaId) {
                $manga = Manga::find($mangaId);
                if ($manga) {
                    return $manga->title;
                }
            }

            if ($this->model_id) {
                $chapter = Chapter::with('manga')->find($this->model_id);
                if ($chapter?->manga) {
                    return $chapter->manga->title;
                }
            }
        }

        if ($base === 'Manga') {
            if ($this->subject) {
                return $this->subject->title;
            }
            return $this->new_values['title'] ?? $this->old_values['title'] ?? null;
        }

        if ($base === 'Comment') {
            $comment = $this->subject ?? ($this->model_id ? Comment::withTrashed()->find($this->model_id) : null);
            if ($comment) {
                if ($comment->commentable_type === Chapter::class || class_basename($comment->commentable_type) === 'Chapter') {
                    $chapter = Chapter::with('manga')->find($comment->commentable_id);
                    return $chapter?->manga?->title;
                }
                if ($comment->commentable_type === Manga::class || class_basename($comment->commentable_type) === 'Manga') {
                    $manga = Manga::find($comment->commentable_id);
                    return $manga?->title;
                }
            }
        }

        return null;
    }

    /**
     * Resolve a descriptive summary of the targeted item.
     */
    public function getTargetTitleAttribute(): ?string
    {
        // Sanctions de modération (Bannissement compte, ban commentaires, débannissement)
        if (in_array($this->action, ['ban_account', 'ban_comments', 'unban'], true)) {
            $targetUser = $this->new_values['target_user']
                ?? $this->new_values['target']
                ?? $this->new_values['user_name']
                ?? null;

            if (!$targetUser && $this->model_type && class_basename($this->model_type) === 'User') {
                $targetUser = $this->subject?->name ?? ($this->model_id ? User::find($this->model_id)?->name : null);
            }

            if (!$targetUser && $this->model_type && class_basename($this->model_type) === 'Comment') {
                $comment = $this->subject ?? ($this->model_id ? Comment::withTrashed()->find($this->model_id) : null);
                $targetUser = $comment?->pseudo ?? $comment?->user?->name;
            }

            $targetName = $targetUser ?: ('Utilisateur #' . ($this->model_id ?? '?'));
            $reason = $this->new_values['reason'] ?? null;

            return match ($this->action) {
                'ban_account' => "Sanction : Bannissement de {$targetName}" . ($reason ? " (Motif : {$reason})" : ''),
                'ban_comments' => "Sanction : Ban commentaires de {$targetName}" . ($reason ? " (Motif : {$reason})" : ''),
                'unban' => "Sanction : Débannissement de {$targetName}",
                default => "Sanction : {$targetName}",
            };
        }

        $base = class_basename($this->model_type ?? '');

        if ($base === 'Chapter') {
            $chapter = $this->subject ?? ($this->model_id ? Chapter::find($this->model_id) : null);
            $num = $chapter?->number ?? $this->new_values['number'] ?? $this->new_values['chapter_number'] ?? $this->old_values['number'] ?? null;
            $title = $chapter?->title ?? $this->new_values['title'] ?? $this->new_values['chapter_title'] ?? $this->old_values['title'] ?? null;

            if ($num !== null) {
                return 'Chapitre ' . $num . ($title ? ' : ' . $title : '');
            }

            return 'Chapitre #' . $this->model_id;
        }

        if ($base === 'Manga') {
            $manga = $this->subject ?? ($this->model_id ? Manga::find($this->model_id) : null);
            $title = $manga?->title ?? $this->new_values['title'] ?? $this->old_values['title'] ?? null;
            return $title ? 'Œuvre : ' . $title : 'Œuvre #' . $this->model_id;
        }

        if ($base === 'Comment') {
            $comment = $this->subject ?? ($this->model_id ? Comment::withTrashed()->find($this->model_id) : null);
            $author = $comment?->pseudo ?? $comment?->user?->name ?? 'Auteur inconnu';
            $content = $comment?->content ?? $this->new_values['content'] ?? $this->old_values['content'] ?? '';
            $excerpt = !empty($content) ? ' « ' . \Illuminate\Support\Str::limit(strip_tags($content), 30) . ' »' : '';

            return 'Commentaire de ' . $author . $excerpt;
        }

        if ($base === 'User') {
            $user = $this->subject ?? ($this->model_id ? User::find($this->model_id) : null);
            $name = $user?->name ?? $this->new_values['name'] ?? $this->old_values['name'] ?? null;
            return $name ? 'Utilisateur : ' . $name : 'Utilisateur #' . $this->model_id;
        }

        if ($this->action === 'bulk_published') {
            $count = $this->new_values['count'] ?? 0;
            return $count . ' chapitres publiés en lot';
        }

        return null;
    }

    /**
     * Human-readable action labels in French.
     */
    public function getActionLabelAttribute(): string
    {
        return match ($this->action) {
            'created' => 'Création',
            'updated' => 'Modification',
            'deleted' => 'Suppression',
            'published_chapter' => 'Publication chapitre',
            'controlled_chapter' => 'Contrôle chapitre',
            'bulk_published' => 'Publication en masse',
            'ban_account' => 'Bannissement compte',
            'ban_comments' => 'Bannissement commentaires',
            'unban' => 'Débannissement',
            'comment_hidden' => 'Commentaire masqué',
            'comment_restored' => 'Commentaire restauré',
            'comment_deleted' => 'Commentaire supprimé',
            'manga_created' => 'Œuvre ajoutée',
            'manga_updated' => 'Œuvre modifiée',
            'manga_deleted' => 'Œuvre supprimée',
            'role_assigned' => 'Rôle attribué',
            'role_removed' => 'Rôle retiré',
            default => ucfirst(str_replace('_', ' ', $this->action)),
        };
    }

    /**
     * Badge color mapping for Filament.
     */
    public function getActionColorAttribute(): string
    {
        return match ($this->action) {
            'created', 'manga_created', 'published_chapter' => 'success',
            'updated', 'manga_updated', 'controlled_chapter', 'bulk_published' => 'info',
            'deleted', 'manga_deleted', 'comment_deleted' => 'danger',
            'ban_account', 'ban_comments' => 'danger',
            'unban', 'comment_restored' => 'success',
            'comment_hidden' => 'warning',
            'role_assigned', 'role_removed' => 'primary',
            default => 'gray',
        };
    }

    /**
     * Translate database column/property name into user-friendly French.
     */
    public static function translateFieldName(string $key): string
    {
        $translations = [
            'title' => 'Titre',
            'slug' => 'Lien (Slug)',
            'number' => 'Numéro de chapitre',
            'status' => 'Statut',
            'synopsis' => 'Synopsis',
            'cover_image' => 'Image de couverture',
            'banner_image' => 'Image de bannière',
            'release_year' => 'Année de sortie',
            'is_featured' => 'Mis en avant',
            'type' => 'Type d\'œuvre',
            'manga_id' => 'ID de l\'œuvre',
            'manga' => 'Œuvre',
            'manga_title' => 'Titre de l\'œuvre',
            'chapter_number' => 'Numéro de chapitre',
            'chapter_title' => 'Titre du chapitre',
            'uploaded_by' => 'Mis en ligne par (ID)',
            'scheduled_at' => 'Programmé le',
            'published_at' => 'Publié le',
            'views_count' => 'Nombre de vues',
            'content' => 'Contenu',
            'pseudo' => 'Pseudo',
            'is_hidden' => 'Masqué',
            'is_pinned' => 'Épinglé',
            'is_banned' => 'Compte banni',
            'comments_banned' => 'Commentaires interdits',
            'ban_reason' => 'Motif du bannissement',
            'banned_until' => 'Banni jusqu\'au',
            'banned_at' => 'Date de bannissement',
            'email' => 'Adresse email',
            'name' => 'Nom d\'utilisateur',
            'password' => 'Mot de passe',
            'role' => 'Rôle',
            'roles' => 'Rôles',
            'reason' => 'Motif / Raison',
            'duration_days' => 'Durée (jours)',
            'count' => 'Nombre d\'éléments',
            'chapters' => 'Chapitres concernés',
            'ip_address' => 'Adresse IP',
            'ip_hash' => 'Empreinte IP',
            'target_user' => 'Utilisateur ciblé',
            'target' => 'Utilisateur ciblé',
            'ban_type' => 'Type de sanction',
            'comments_hidden' => 'Commentaires masqués',
            'commentable_type' => 'Type d\'élément commenté',
            'commentable_id' => 'ID de l\'élément commenté',
            'parent_id' => 'ID commentaire parent',
            'dislikes_count' => 'Nombre de dislikes',
            'average_rating' => 'Note moyenne',
            'ratings_count' => 'Nombre d\'évaluations',
        ];

        return $translations[$key] ?? ucfirst(str_replace('_', ' ', $key));
    }

    /**
     * Format a raw value into readable French text.
     */
    public static function formatFieldValue(mixed $value): string
    {
        if ($value === null) {
            return '— (vide)';
        }

        if (is_bool($value)) {
            return $value ? 'Oui' : 'Non';
        }

        if ($value instanceof \BackedEnum) {
            $value = $value->value;
        }

        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        $stringVal = (string) $value;

        $valueTranslations = [
            'publie' => 'Publié',
            'brouillon' => 'Brouillon',
            'controle' => 'En contrôle',
            'programme' => 'Programmé',
            'en_cours' => 'En cours',
            'termine' => 'Terminé',
            'abandonne' => 'Abandonné',
            'pause' => 'En pause',
            'manga' => 'Manga',
            'manhwa' => 'Manhwa',
            'manhua' => 'Manhua',
            'owner' => 'Propriétaire',
            'admin' => 'Administrateur',
            'moderator' => 'Modérateur',
            'comment_only' => 'Commentaires uniquement',
            'full_account' => 'Bannissement total du compte',
        ];

        if (isset($valueTranslations[strtolower($stringVal)])) {
            return $valueTranslations[strtolower($stringVal)];
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/', $stringVal)) {
            try {
                return \Carbon\Carbon::parse($stringVal)->format('d/m/Y à H:i:s');
            } catch (\Throwable $e) {
            }
        }

        return $stringVal;
    }

    /**
     * Get old values with French keys and readable values.
     */
    public function getFormattedOldValuesAttribute(): ?array
    {
        if (empty($this->old_values) || !is_array($this->old_values)) {
            return null;
        }

        $formatted = [];
        foreach ($this->old_values as $key => $val) {
            $formatted[static::translateFieldName((string) $key)] = static::formatFieldValue($val);
        }

        return $formatted;
    }

    /**
     * Get new values with French keys and readable values.
     */
    public function getFormattedNewValuesAttribute(): ?array
    {
        if (empty($this->new_values) || !is_array($this->new_values)) {
            return null;
        }

        $formatted = [];
        foreach ($this->new_values as $key => $val) {
            $formatted[static::translateFieldName((string) $key)] = static::formatFieldValue($val);
        }

        return $formatted;
    }

    /**
     * Scope: filter by action category.
     */
    public function scopeModeration($query)
    {
        return $query->whereIn('action', [
            'ban_account', 'ban_comments', 'unban',
            'comment_hidden', 'comment_restored', 'comment_deleted',
        ]);
    }

    public function scopeContent($query)
    {
        return $query->whereIn('action', [
            'created', 'updated', 'deleted',
            'published_chapter', 'controlled_chapter', 'bulk_published',
            'manga_created', 'manga_updated', 'manga_deleted',
        ]);
    }
}
