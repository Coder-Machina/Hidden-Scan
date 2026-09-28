<?php

namespace App\Console\Commands;

use App\Services\DiscordWebhookService;
use Illuminate\Console\Command;

class TestDiscordWebhookCommand extends Command
{
    protected $signature = 'discord:test';
    protected $description = 'Envoie un message de test sur le webhook Discord configuré';

    public function handle(DiscordWebhookService $service): int
    {
        $this->info("Test du webhook Discord en cours...");

        if (!$service->isConfigured()) {
            $this->error("L'URL du Webhook Discord n'est pas configurée.");
            $this->line("Ajoutez DISCORD_WEBHOOK_URL=https://discord.com/api/webhooks/... dans votre fichier .env");
            return Command::FAILURE;
        }

        $result = $service->testWebhook();

        if ($result['success']) {
            $this->info("✓ " . $result['message']);
            return Command::SUCCESS;
        }

        $this->error("✗ " . $result['message']);
        return Command::FAILURE;
    }
}
