<?php

namespace App\Models;

use App\Traits\HasAuditLogging;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Comment extends Model
{
    use HasFactory, SoftDeletes, HasAuditLogging;

    protected $fillable = [
    'commentable_type', 'commentable_id', 'user_id',
    'pseudo', 'content', 'ip_hash', 'is_hidden', 'is_pinned', 'parent_id', 'dislikes_count',
];

    protected $appends = ['rendered_content'];

    /**
     * Render comment content with [img] bbcode support.
     * Escapes all HTML except for whitelisted image tags.
     */
    public function getRenderedContentAttribute(): string
    {
        // First, escape all HTML
        $safe = e($this->content);

        // Convert [img]URL[/img] to actual <img> tags (only allow http(s) URLs)
        $safe = preg_replace(
            '/\[img\](https?:\/\/[^\s\[\]]+)\[\/img\]/i',
            '<img src="$1" alt="Image" class="max-w-full max-h-72 rounded-xl mt-2 mb-1 border border-white/10" loading="lazy">',
            $safe
        );

        // Bold: **text**
        $safe = preg_replace('/\*\*(.+?)\*\*/s', '<strong class="font-bold text-white">$1</strong>', $safe);

        // Italic: *text*
        $safe = preg_replace('/(?<!\*)\*(?!\*)(.+?)(?<!\*)\*(?!\*)/s', '<em class="italic text-mist/90">$1</em>', $safe);

        // Spoiler: ||text||
        $safe = preg_replace(
            '/\|\|(.+?)\|\|/s',
            '<span class="cursor-pointer bg-neutral-800 text-transparent hover:text-white rounded px-1.5 py-0.5 select-none transition-colors" title="Cliquer pour afficher le spoiler" onclick="this.classList.remove(\'text-transparent\')">$1</span>',
            $safe
        );

        // Strike-through: ~~text~~
        $safe = preg_replace('/~~(.+?)~~/s', '<del class="line-through opacity-75">$1</del>', $safe);

        // Quotes: lines starting with >
        $safe = preg_replace('/^&gt;\s*(.+)$/m', '<blockquote class="border-l-2 border-[#5b6ef5]/50 pl-2.5 py-0.5 text-[#a0a0c0] italic my-1 bg-white/[0.02] rounded-r">$1</blockquote>', $safe);

        // Mentions: @pseudo
        $safe = preg_replace('/@([a-zA-Z0-9_\-]+)/', '<span class="text-[#7b8ef8] font-semibold">@$1</span>', $safe);

        return $safe;
    }

    public function commentable()
    {
        return $this->morphTo();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function parent()
    {
        return $this->belongsTo(Comment::class, 'parent_id');
    }

    public function replies()
    {
        return $this->hasMany(Comment::class, 'parent_id');
    }
}