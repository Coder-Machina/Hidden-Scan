<?php

namespace App\Console\Commands;

use App\Enums\ChapterStatus;
use App\Models\Chapter;
use App\Services\DiscordWebhookService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class PublishScheduledChaptersCommand extends Command
{
    protected $signature = 'chapters:publish-scheduled';
    protected $description = 'Publie automatiquement les chapitres programmés dont la date d\'échéance est atteinte';

    public function handle(DiscordWebhookService $discord): int
    {
        $now = now();
        $scheduledChapters = Chapter::with(['manga', 'pages'])
            ->where('status', ChapterStatus::PROGRAMME)
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', $now)
            ->orderBy('number', 'asc')
            ->get();

        if ($scheduledChapters->isEmpty()) {
            return Command::SUCCESS;
        }

        $this->info("Traitement de {$scheduledChapters->count()} chapitre(s) programmé(s)...");

        $publishedCount = 0;
        foreach ($scheduledChapters as $chapter) {
            $chapter->update([
                'status' => ChapterStatus::PUBLIE,
                'published_at' => $chapter->scheduled_at ?? $now,
            ]);

            // Notifie les utilisateurs ayant mis l'œuvre en favoris
            $chapter->notifyFavoriteUsers();

            // Notifie le webhook Discord
            $discord->sendChapterNotification($chapter);

            Log::info("Chapitre programmé publié avec succès", [
                'chapter_id' => $chapter->id,
                'manga' => $chapter->manga?->title,
                'number' => $chapter->number,
                'scheduled_at' => $chapter->scheduled_at,
            ]);

            $this->line("✓ Chapitre #{$chapter->number} de « {$chapter->manga?->title} » publié !");
            $publishedCount++;
        }

        $this->info("Publication terminée : {$publishedCount} chapitre(s) mis en ligne.");
        return Command::SUCCESS;
    }
}
