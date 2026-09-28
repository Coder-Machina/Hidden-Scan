<?php

namespace App\Services;

use App\Models\Chapter;
use App\Models\Manga;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class DiscordWebhookService
{
    /**
     * Vérifie si l'URL du webhook Discord est configurée.
     */
    public function isConfigured(): bool
    {
        return !empty(config('services.discord.webhook_url'));
    }

    /**
     * Envoie une notification Discord pour la publication d'un chapitre.
     */
    public function sendChapterNotification(Chapter $chapter): bool
    {
        if (!$this->isConfigured()) {
            Log::info('Discord Webhook non configuré, notification ignorée pour le chapitre ' . $chapter->id);
            return false;
        }

        $chapter->loadMissing('manga');
        $manga = $chapter->manga;

        if (!$manga) {
            return false;
        }

        $webhookUrl = config('services.discord.webhook_url');
        $roleId = config('services.discord.role_id');

        $chapterUrl = route('chapter.show', [$manga->slug, $chapter->slug]);
        $coverUrl = null;

        if ($manga->cover_image) {
            $coverUrl = str_starts_with($manga->cover_image, 'http')
                ? $manga->cover_image
                : url(Storage::url($manga->cover_image));
        }

        $titleSuffix = $chapter->title ? " : *{$chapter->title}*" : '';
        $description = "🔥 **Chapitre {$chapter->number}**{$titleSuffix}\n\nLe nouveau chapitre de **{$manga->title}** est désormais disponible en lecture libre et haute qualité sur **Hidden Scan** !\n\n👉 [**Cliquez ici pour lire le chapitre**]({$chapterUrl})";

        $embed = [
            'title' => "📖 Nouveau Chapitre : {$manga->title}",
            'description' => $description,
            'url' => $chapterUrl,
            'color' => 0xDC2626, // Rouge #dc2626 (Charte Hidden Scan)
            'fields' => [
                [
                    'name' => 'Type',
                    'value' => ucfirst($manga->type?->value ?? 'Manga'),
                    'inline' => true,
                ],
                [
                    'name' => 'Statut',
                    'value' => $manga->status?->getLabel() ?? 'En cours',
                    'inline' => true,
                ],
                [
                    'name' => 'Pages',
                    'value' => ($chapter->pages()->count() ?: 'En ligne') . ' pages',
                    'inline' => true,
                ],
            ],
            'footer' => [
                'text' => 'Hidden Scan • Le sanctuaire des scans',
            ],
            'timestamp' => now()->toIso8601String(),
        ];

        if ($coverUrl) {
            $embed['thumbnail'] = [
                'url' => $coverUrl,
            ];
        }

        $payload = [
            'embeds' => [$embed],
        ];

        if ($roleId) {
            $payload['content'] = "<@&{$roleId}>";
        }

        try {
            $response = Http::timeout(6)->post($webhookUrl, $payload);

            if ($response->successful()) {
                Log::info("Webhook Discord envoyé avec succès pour le chapitre {$chapter->id} de {$manga->title}");
                return true;
            }

            Log::error("Échec de l'envoi du webhook Discord", [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return false;
        } catch (\Throwable $e) {
            Log::error("Exception lors de l'envoi du webhook Discord : " . $e->getMessage());
            return false;
        }
    }

    /**
     * Envoie une notification groupée pour plusieurs chapitres publiés d'un coup (lot).
     */
    public function sendBatchChaptersNotification(Manga $manga, array|Collection $chapters): bool
    {
        if (!$this->isConfigured()) {
            return false;
        }

        $chaptersCount = count($chapters);
        if ($chaptersCount === 0) {
            return false;
        }

        if ($chaptersCount === 1) {
            $first = is_array($chapters) ? reset($chapters) : $chapters->first();
            return $this->sendChapterNotification($first);
        }

        $webhookUrl = config('services.discord.webhook_url');
        $roleId = config('services.discord.role_id');

        $numbers = collect($chapters)->pluck('number')->sort()->values();
        $min = $numbers->first();
        $max = $numbers->last();

        $mangaUrl = route('manga.show', $manga->slug);
        $coverUrl = null;

        if ($manga->cover_image) {
            $coverUrl = str_starts_with($manga->cover_image, 'http')
                ? $manga->cover_image
                : url(Storage::url($manga->cover_image));
        }

        $description = "🎉 **{$chaptersCount} nouveaux chapitres disponibles !**\n\nLes chapitres **#{$min}** à **#{$max}** de **{$manga->title}** viennent d'être mis en ligne sur **Hidden Scan** !\n\n👉 [**Accéder à la liste des chapitres**]({$mangaUrl})";

        $embed = [
            'title' => "📚 Sortie en masse : {$manga->title}",
            'description' => $description,
            'url' => $mangaUrl,
            'color' => 0xDC2626,
            'fields' => [
                [
                    'name' => 'Type',
                    'value' => ucfirst($manga->type?->value ?? 'Manga'),
                    'inline' => true,
                ],
                [
                    'name' => 'Statut',
                    'value' => $manga->status?->getLabel() ?? 'En cours',
                    'inline' => true,
                ],
                [
                    'name' => 'Lot',
                    'value' => "Chapitres #{$min} à #{$max}",
                    'inline' => true,
                ],
            ],
            'footer' => [
                'text' => 'Hidden Scan • Le sanctuaire des scans',
            ],
            'timestamp' => now()->toIso8601String(),
        ];

        if ($coverUrl) {
            $embed['thumbnail'] = [
                'url' => $coverUrl,
            ];
        }

        $payload = [
            'embeds' => [$embed],
        ];

        if ($roleId) {
            $payload['content'] = "<@&{$roleId}>";
        }

        try {
            $response = Http::timeout(6)->post($webhookUrl, $payload);
            return $response->successful();
        } catch (\Throwable $e) {
            Log::error("Erreur envoi notification Discord en masse : " . $e->getMessage());
            return false;
        }
    }

    /**
     * Envoie un message de test pour valider le webhook.
     */
    public function testWebhook(): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'message' => "L'URL du Webhook Discord n'est pas définie dans .env (DISCORD_WEBHOOK_URL).",
            ];
        }

        $webhookUrl = config('services.discord.webhook_url');

        $embed = [
            'title' => '🤖 Test de connexion Discord — Hidden Scan',
            'description' => "Félicitations ! Le webhook Discord est parfaitement configuré et opérationnel.\n\nChaque nouvelle publication de chapitre sera annoncée automatiquement dans ce salon.",
            'color' => 0x10B981, // Vert #10b981
            'fields' => [
                ['name' => 'Site', 'value' => config('app.url', 'http://127.0.0.1:8000'), 'inline' => true],
                ['name' => 'Statut', 'value' => 'Connecté ✅', 'inline' => true],
            ],
            'footer' => [
                'text' => 'Hidden Scan • Bot de notification',
            ],
            'timestamp' => now()->toIso8601String(),
        ];

        try {
            $response = Http::timeout(6)->post($webhookUrl, ['embeds' => [$embed]]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => 'Message de test envoyé avec succès sur Discord !',
                ];
            }

            return [
                'success' => false,
                'message' => 'Erreur Discord (Code ' . $response->status() . ') : ' . $response->body(),
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => "Erreur de connexion : " . $e->getMessage(),
            ];
        }
    }
}
