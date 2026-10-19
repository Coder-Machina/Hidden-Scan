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
        Schema::table('scraper_mangas', function (Blueprint $table) {
            $table->boolean('cancel_requested')->default(false)->after('status');
            $table->string('current_scraping_chapter')->nullable()->after('last_chapter_scraped');
            $table->unsignedInteger('total_chapters_detected')->nullable()->after('current_scraping_chapter');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('scraper_mangas', function (Blueprint $table) {
            $table->dropColumn(['cancel_requested', 'current_scraping_chapter', 'total_chapters_detected']);
        });
    }
};
