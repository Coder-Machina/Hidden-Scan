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
        Schema::create('release_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('day_of_week'); // lundi, mardi, mercredi, jeudi, vendredi, samedi, dimanche
            $table->foreignId('manga_id')->constrained('mangas')->cascadeOnDelete();
            $table->string('release_time')->default('18h00');
            $table->string('status_label')->default('Régulier');
            $table->boolean('is_priority')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['day_of_week', 'is_active', 'sort_order']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->json('tier_list')->nullable()->after('favorite_genre');
            $table->json('top_mangas')->nullable()->after('tier_list');
            $table->unsignedInteger('streak')->default(0)->after('xp');
            $table->timestamp('last_read_at')->nullable()->after('streak');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('release_schedules');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['tier_list', 'top_mangas', 'streak', 'last_read_at']);
        });
    }
};
