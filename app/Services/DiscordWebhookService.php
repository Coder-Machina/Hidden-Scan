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
    public function resolveWebhookUrl(?Manga $manga = null): ?string
    {
        if ($manga && !empty($manga->discord_webhook_url)) {
            return $manga->discord_webhook_url;
        }

        if ($manga && $manga->is_external) {
            return config('services.discord.site_webhook_url') ?: config('services.discord.webhook_url');
        }

        return config('services.discord.webhook_url');
    }

    /**
     * Vérifie si l'URL du webhook Discord est configurée.
     */
    public function isConfigured(?Manga $manga = null): bool
    {
        return !empty($this->resolveWebhookUrl($manga));
    }

    /**
     * Envoie une notification Discord pour la publication d'un chapitre.
     */
    public function sendChapterNotification(Chapter $chapter): bool
    {
        $chapter->loadMissing('manga');
        $manga = $chapter->manga;

        if (!$manga) {
            return false;
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

        // Mentions de rôles : Rôle du manga + Rôle global de notification
        $pings = [];
        if (!empty($manga->discord_role_id)) {
            $pings[] = is_numeric($manga->discord_role_id) ? "<@&{$manga->discord_role_id}>" : "@{$manga->discord_role_id}";
        } else {
            $pings[] = "@{$manga->title}";
        }

        if (!empty($allProjectsRoleId)) {
            $pings[] = is_numeric($allProjectsRoleId) ? "<@&{$allProjectsRoleId}>" : "@{$allProjectsRoleId}";
        } elseif (!empty($roleId) && $roleId !== $manga->discord_role_id) {
            $pings[] = is_numeric($roleId) ? "<@&{$roleId}>" : "<@&{$roleId}>";
        } else {
            $pings[] = $isExternal ? "@Sorties Site" : "@Tous les projets";
        }

        $payload = [
            'embeds' => [$embed],
        ];

        if (!empty($pings)) {
            $payload['content'] = implode(' ', $pings);
        }

        // Détection de l'image de couverture pour affichage pleine largeur garanti
        $localFilePath = $this->resolveLocalCoverPath($manga->cover_image);

        try {
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
                if ($manga->cover_image && str_starts_with($manga->cover_image, 'http')) {
                    $payload['embeds'][0]['image'] = ['url' => $manga->cover_image];
                }
                $response = Http::timeout(10)->post($webhookUrl, $payload);
            }

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

        $numbers = collect($chapters)->pluck('number')->sort()->values();
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
        if (!empty($manga->discord_role_id)) {
            $pings[] = is_numeric($manga->discord_role_id) ? "<@&{$manga->discord_role_id}>" : "@{$manga->discord_role_id}";
        } else {
            $pings[] = "@{$manga->title}";
        }

        if (!empty($allProjectsRoleId)) {
            $pings[] = is_numeric($allProjectsRoleId) ? "<@&{$allProjectsRoleId}>" : "@{$allProjectsRoleId}";
        } elseif (!empty($roleId) && $roleId !== $manga->discord_role_id) {
            $pings[] = is_numeric($roleId) ? "<@&{$roleId}>" : "<@&{$roleId}>";
        } else {
            $pings[] = "@Tous les projets";
        }

        $payload = [
            'embeds' => [$embed],
        ];

        if (!empty($pings)) {
            $payload['content'] = implode(' ', $pings);
        }

        $localFilePath = $this->resolveLocalCoverPath($manga->cover_image);

        try {
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
                if ($manga->cover_image && str_starts_with($manga->cover_image, 'http')) {
                    $payload['embeds'][0]['image'] = ['url' => $manga->cover_image];
                }
                $response = Http::timeout(10)->post($webhookUrl, $payload);
            }

            return $response->successful();
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
