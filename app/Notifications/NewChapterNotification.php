<?php

namespace App\Notifications;

use App\Models\Chapter;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Storage;

class NewChapterNotification extends Notification
{
    use Queueable;

    public Chapter $chapter;

    /**
     * Create a new notification instance.
     */
    public function __construct(Chapter $chapter)
    {
        $this->chapter = $chapter;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $manga = $this->chapter->manga;
        $cover = $manga?->cover_image ? Storage::url($manga->cover_image) : null;
        $pubDate = $this->chapter->published_at ?? $this->chapter->created_at ?? now();

        return [
            'type' => 'new_chapter',
            'chapter_id' => $this->chapter->id,
            'chapter_number' => $this->chapter->number,
            'chapter_title' => $this->chapter->title,
            'chapter_slug' => $this->chapter->slug,
            'manga_id' => $this->chapter->manga_id,
            'manga_title' => $manga?->title ?? 'Manga',
            'manga_slug' => $manga?->slug ?? '',
            'manga_cover' => $cover,
            'manga_type' => $manga?->type?->getLabel() ?? 'Manga',
            'published_at' => $pubDate->toISOString(),
            'published_at_human' => $pubDate->diffForHumans(),
            'url' => route('chapter.show', [$manga?->slug ?? '', $this->chapter->slug]),
            'title' => 'Nouveau chapitre disponible !',
            'message' => "Le chapitre {$this->chapter->number} de {$manga?->title} est maintenant disponible.",
        ];
    }
}
