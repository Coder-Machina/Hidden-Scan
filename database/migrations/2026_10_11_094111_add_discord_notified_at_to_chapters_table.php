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
        Schema::table('chapters', function (Blueprint $table) {
            $table->timestamp('discord_notified_at')->nullable()->after('published_at');
        });

        // Marque tous les chapitres déjà publiés comme déjà notifiés pour éviter tout doublon/spam lors des déploiements
        \Illuminate\Support\Facades\DB::table('chapters')
            ->where('status', \App\Enums\ChapterStatus::PUBLIE->value)
            ->update(['discord_notified_at' => now()]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chapters', function (Blueprint $table) {
            $table->dropColumn('discord_notified_at');
        });
    }
};
