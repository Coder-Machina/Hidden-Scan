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
        Schema::table('mangas', function (Blueprint $table) {
            $table->boolean('is_external')->default(false)->after('is_rush');
            $table->string('team_name')->nullable()->after('is_external');
            $table->string('discord_webhook_url')->nullable()->after('discord_role_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mangas', function (Blueprint $table) {
            $table->dropColumn(['is_external', 'team_name', 'discord_webhook_url']);
        });
    }
};
