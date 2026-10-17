<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. "Log Into The Future" est une œuvre officielle de notre team (non externe)
        \Illuminate\Support\Facades\DB::table('mangas')
            ->where('slug', 'log-into-the-future')
            ->update([
                'is_external' => false,
                'discord_role_id' => '1561061869320675529',
                'step_trad_chapter' => 5,
                'step_check_chapter' => 5,
                'step_clean_chapter' => 5,
                'step_edit_chapter' => 5,
                'step_qc_chapter' => 5,
            ]);

        // 2. Rôles et statut des autres œuvres officielles de la team si présentes
        \Illuminate\Support\Facades\DB::table('mangas')
            ->where('slug', 'my-passive-skills-are-invincible')
            ->update([
                'is_external' => false,
                'discord_role_id' => '1535265321328447550',
            ]);

        \Illuminate\Support\Facades\DB::table('mangas')
            ->where('slug', 'all-football-talents-are-mine')
            ->update([
                'is_external' => false,
                'discord_role_id' => '1535265322485944360',
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Pas d'inversion requise
    }
};
