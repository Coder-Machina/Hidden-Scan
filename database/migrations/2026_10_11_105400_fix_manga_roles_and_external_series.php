<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Mise à jour des 4 œuvres officielles de Hidden Scan avec leurs rôles Discord officiels vérifiés
        $officialRoles = [
            'genius-grandson-of-the-loan-shark-king' => '1535265318828511234',
            'the-returners-road-to-retirement' => '1535265320133066753',
            'my-passive-skills-are-invincible' => '1535265321328447550',
            'all-football-talents-are-mine' => '1535265322485944360',
        ];

        foreach ($officialRoles as $slug => $roleId) {
            DB::table('mangas')->where('slug', $slug)->update([
                'is_external' => false,
                'discord_role_id' => $roleId,
            ]);
        }

        // 2. Toutes les autres œuvres du catalogue sont des sorties externes ("Sorties Site")
        DB::table('mangas')
            ->whereNotIn('slug', array_keys($officialRoles))
            ->update([
                'is_external' => true,
                'discord_role_id' => null,
            ]);

        // 3. Protection anti-spam : marquer tous les chapitres déjà publiés comme déjà notifiés
        DB::table('chapters')
            ->whereNull('discord_notified_at')
            ->where('status', 'publie')
            ->update(['discord_notified_at' => now()]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Pas d'inversion requise pour cette synchronisation de données
    }
};
