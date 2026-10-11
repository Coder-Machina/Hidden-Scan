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
     * Résout l'URL du webhook selon la source de l'œuvre (interne Hidden Scan ou externe / autre team).
     */
    public const ACTIVE_WEBHOOK_URL = 'https://discord.com/api/webhooks/1558768915629478018/xip0NUzOWQk1QQ6YM0AZTkVzfCc7c7BCpXm8lPA59_bU8bYkE28SUNmjGnc6enG6WAnq';
    public const DEAD_WEBHOOK_ID = '1558548282387468388';

    /**
     * Résout l'URL du webhook selon la source de l'œuvre (interne Hidden Scan ou externe / autre team).
     */
    public function resolveWebhookUrl(?Manga $manga = null): ?string
    {
        if ($manga && !empty($manga->discord_webhook_url)) {
            $url = $manga->discord_webhook_url;
        } elseif ($manga && $manga->is_external) {
            $url = config('services.discord.site_webhook_url') ?: config('services.discord.webhook_url');
        } else {
            $url = config('services.discord.webhook_url');
        }

        if (!empty($url) && str_contains($url, self::DEAD_WEBHOOK_ID)) {
            $url = self::ACTIVE_WEBHOOK_URL;
        }

        return $url;
    }

    /**
     * Vérifie si l'URL du webhook Discord est configurée.
     */
    public function isConfigured(?Manga $manga = null): bool
    {
        return !empty($this->resolveWebhookUrl($manga));
    }

    /**
     * Envoie une requête vers un Webhook Discord avec gestion de pièce jointe et auto-secours sur 404.
     */
    protected function postToWebhook(string $webhookUrl, array $payload, ?string $localFilePath = null): \Illuminate\Http\Client\Response
    {
        if ($localFilePath && file_exists($localFilePath)) {
            $ext = pathinfo($localFilePath, PATHINFO_EXTENSION) ?: 'png';
            $filename = 'cover.' . $ext;
            $payload['embeds'][0]['image'] = ['url' => 'attachment://' . $filename];

            $response = Http::timeout(10)
                ->attach('files[0]', file_get_contents($localFilePath), $filename)
                ->post($webhookUrl, [
                    'payload_json' => json_encode($payload),
                ]);
        } else {
            $response = Http::timeout(10)->post($webhookUrl, $payload);
        }

        // Auto-récupération immédiate si le webhook configuré a été supprimé sur Discord (erreur 404)
        if ($response->status() === 404 && $webhookUrl !== self::ACTIVE_WEBHOOK_URL) {
            Log::warning("Discord Webhook 404 sur {$webhookUrl}, bascule automatique vers le webhook actif : " . self::ACTIVE_WEBHOOK_URL);
            return $this->postToWebhook(self::ACTIVE_WEBHOOK_URL, $payload, $localFilePath);
        }

        return $response;
    }

    /**
     * Envoie une notification Discord pour la publication d'un chapitre.
     */
    public function sendChapterNotification(Chapter $chapter, bool $force = false): bool
    {
        $chapter->loadMissing('manga');
        $manga = $chapter->manga;

        if (!$manga) {
            return false;
        }

        // 1. Protection Anti-Doublon : ne jamais renvoyer de notification pour un chapitre déjà notifié (sauf si forcé)
        if (!$force && !is_null($chapter->discord_notified_at)) {
            Log::info("Discord webhook ignoré pour le chapitre {$chapter->id} de {$manga->title} : déjà notifié le {$chapter->discord_notified_at}");
            return true;
        }

        $webhookUrl = $this->resolveWebhookUrl($manga);
        if (empty($webhookUrl)) {
            Log::info('Discord Webhook non configuré, notification ignorée pour le chapitre ' . $chapter->id);
            return false;
        }

        $isExternal = (bool) ($manga->is_external ?? false);
        $baseUrl = config('services.discord.base_url', config('app.url'));
        $roleId = config('services.discord.role_id');
        $allProjectsRoleId = $isExternal
            ? (config('services.discord.site_role_id') ?: config('services.discord.all_projects_role_id'))
            : config('services.discord.all_projects_role_id');

        // URL du chapitre sur le site
        $chapterUrl = rtrim($baseUrl, '/') . '/manga/' . $manga->slug . '/' . $chapter->slug;

        $titleSuffix = $chapter->title ? " : {$chapter->title}" : '';
        $embedTitle = "{$manga->title} - Chapitre {$chapter->number}{$titleSuffix}";

        // Format 100% fidèle à Poseidon Scans & Rimu Scans
        $description = "➡️ [**Cliquer ici pour lire**]({$chapterUrl})";

        // Footer selon la source
        $footerPrefix = 'Hidden Scan';
        if ($isExternal) {
            $footerPrefix = !empty($manga->team_name) ? "{$manga->team_name} • Sortie Site" : 'Sortie Site';
        }

        // Couleur : Bleu royal pour Hidden Scan (0x2563EB), Ambre pour Sortie Site (0xF59E0B)
        $embedColor = $isExternal ? 0xF59E0B : 0x2563EB;

        $embed = [
            'title' => $embedTitle,
            'description' => $description,
            'color' => $embedColor,
            'footer' => [
                'text' => $footerPrefix . ' • ' . now()->locale('fr')->isoFormat('D MMMM [à] HH:mm'),
            ],
            'timestamp' => now()->toIso8601String(),
        ];

        // Mentions de rôles : Rôle du manga (uniquement si série officielle avec ID valide) + Rôle global de notification
        $pings = [];
        if (!$isExternal && !empty($manga->discord_role_id) && is_numeric($manga->discord_role_id)) {
            $pings[] = "<@&{$manga->discord_role_id}>";
        }

        if (!empty($allProjectsRoleId) && is_numeric($allProjectsRoleId)) {
            $pings[] = "<@&{$allProjectsRoleId}>";
        } elseif (!empty($roleId) && is_numeric($roleId) && $roleId !== $manga->discord_role_id) {
            $pings[] = "<@&{$roleId}>";
        }

        $payload = [
            'embeds' => [$embed],
        ];

        if (!empty($pings)) {
            $payload['content'] = implode(' ', $pings);
        }

        // Image de couverture
        if ($manga->cover_image && str_starts_with($manga->cover_image, 'http')) {
            $payload['embeds'][0]['image'] = ['url' => $manga->cover_image];
        }

        $localFilePath = $this->resolveLocalCoverPath($manga->cover_image);

        try {
            $response = $this->postToWebhook($webhookUrl, $payload, $localFilePath);

            if ($response->successful()) {
                $chapter->updateQuietly(['discord_notified_at' => now()]);
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
    public function sendBatchChaptersNotification(Manga $manga, array|Collection $chapters, bool $force = false): bool
    {
        if (!$this->isConfigured($manga)) {
            return false;
        }

        $chaptersCount = count($chapters);
        if ($chaptersCount === 0) {
            return false;
        }

        // Filtre les chapitres qui n'ont pas encore été notifiés sur Discord (sauf si forcé)
        $chaptersToNotify = collect($chapters);
        if (!$force) {
            $chaptersToNotify = $chaptersToNotify->filter(fn($c) => is_null($c->discord_notified_at));
            if ($chaptersToNotify->isEmpty()) {
                Log::info("Tous les chapitres du lot ont déjà été notifiés sur Discord pour {$manga->title}, lot ignoré.");
                return true;
            }
        }

        if ($chaptersToNotify->count() === 1) {
            return $this->sendChapterNotification($chaptersToNotify->first(), force: $force);
        }

        $webhookUrl = $this->resolveWebhookUrl($manga);
        if (empty($webhookUrl)) {
            return false;
        }

        $isExternal = (bool) ($manga->is_external ?? false);
        $baseUrl = config('services.discord.base_url', config('app.url'));
        $roleId = config('services.discord.role_id');
        $allProjectsRoleId = $isExternal
            ? (config('services.discord.site_role_id') ?: config('services.discord.all_projects_role_id'))
            : config('services.discord.all_projects_role_id');

        $numbers = $chaptersToNotify->pluck('number')->sort()->values();
        $min = $numbers->first();
        $max = $numbers->last();

        $mangaUrl = rtrim($baseUrl, '/') . '/manga/' . $manga->slug;
        $embedTitle = "{$manga->title} - Chapitres {$min} à {$max}";
        $description = "➡️ [**Cliquer ici pour lire**]({$mangaUrl})";

        $footerPrefix = 'Hidden Scan';
        if ($isExternal) {
            $footerPrefix = !empty($manga->team_name) ? "{$manga->team_name} • Sortie Site" : 'Sortie Site';
        }
        $embedColor = $isExternal ? 0xF59E0B : 0x2563EB;

        $embed = [
            'title' => $embedTitle,
            'description' => $description,
            'color' => $embedColor,
            'footer' => [
                'text' => $footerPrefix . ' • ' . now()->locale('fr')->isoFormat('D MMMM [à] HH:mm'),
            ],
            'timestamp' => now()->toIso8601String(),
        ];

        $pings = [];
        if (!$isExternal && !empty($manga->discord_role_id) && is_numeric($manga->discord_role_id)) {
            $pings[] = "<@&{$manga->discord_role_id}>";
        }

        if (!empty($allProjectsRoleId) && is_numeric($allProjectsRoleId)) {
            $pings[] = "<@&{$allProjectsRoleId}>";
        } elseif (!empty($roleId) && is_numeric($roleId) && $roleId !== $manga->discord_role_id) {
            $pings[] = "<@&{$roleId}>";
        }

        $payload = [
            'embeds' => [$embed],
        ];

        if (!empty($pings)) {
            $payload['content'] = implode(' ', $pings);
        }

        if ($manga->cover_image && str_starts_with($manga->cover_image, 'http')) {
            $payload['embeds'][0]['image'] = ['url' => $manga->cover_image];
        }

        $localFilePath = $this->resolveLocalCoverPath($manga->cover_image);

        try {
            $response = $this->postToWebhook($webhookUrl, $payload, $localFilePath);

            if ($response->successful()) {
                foreach ($chaptersToNotify as $c) {
                    $c->updateQuietly(['discord_notified_at' => now()]);
                }
                return true;
            }

            Log::error("Échec de l'envoi du webhook Discord en masse", [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return false;
        } catch (\Throwable $e) {
            Log::error("Erreur envoi notification Discord en masse : " . $e->getMessage());
            return false;
        }
    }


    /**
     * Résout le chemin absolu sur le disque local pour la couverture du manga.
     */
    protected function resolveLocalCoverPath(?string $coverImage): ?string
    {
        if (!$coverImage || str_starts_with($coverImage, 'http')) {
            return null;
        }

        $disk = config('filesystems.default', 'public');
        if (Storage::disk($disk)->exists($coverImage)) {
            return Storage::disk($disk)->path($coverImage);
        }

        $publicPath = storage_path('app/public/' . ltrim($coverImage, '/'));
        if (file_exists($publicPath)) {
            return $publicPath;
        }

        return null;
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

        $webhookUrl = $this->resolveWebhookUrl();

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
            $response = $this->postToWebhook($webhookUrl, ['embeds' => [$embed]]);

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

    /**
     * Envoie un exemple réel de sortie de chapitre pour prévisualiser le format Poseidon / Rimu Scans.
     */
    public function sendSampleReleaseNotification(): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'message' => "L'URL du Webhook Discord n'est pas définie dans .env.",
            ];
        }

        $manga = \App\Models\Manga::whereNotNull('cover_image')->first() ?: \App\Models\Manga::first();
        if (!$manga) {
            return [
                'success' => false,
                'message' => "Aucun manga trouvé en base de données pour générer un exemple.",
            ];
        }

        $latestChapter = $manga->chapters()->latest('number')->first();
        $chapterNumber = $latestChapter ? $latestChapter->number : 1;

        $mockChapter = new \App\Models\Chapter([
            'number' => $chapterNumber,
            'slug' => 'chapitre-' . $chapterNumber,
            'title' => null,
            'is_published' => true,
        ]);
        $mockChapter->setRelation('manga', $manga);

        $sent = $this->sendChapterNotification($mockChapter);

        return [
            'success' => $sent,
            'message' => $sent
                ? "Notification exemple style Poseidon/Rimu pour « {$manga->title} » envoyée avec succès sur Discord !"
                : "Échec de l'envoi de la notification exemple.",
        ];
    }
}
