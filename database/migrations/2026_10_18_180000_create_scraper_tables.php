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
        Schema::create('scraper_sources', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('base_url');
            $table->string('sel_chapter_list');
            $table->string('sel_chapter_title');
            $table->string('sel_chapter_url');
            $table->string('sel_chapter_date')->nullable();
            $table->string('sel_chapter_pages');
            $table->json('headers')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('scraper_mangas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manga_id')
                  ->constrained()
                  ->onDelete('cascade');
            $table->foreignId('scraper_source_id')
                  ->constrained('scraper_sources')
                  ->onDelete('cascade');
            $table->string('source_url');
            $table->string('last_chapter_scraped')->nullable();
            $table->timestamp('last_checked_at')->nullable();
            $table->string('status', 30)
                  ->default('active');
            $table->text('last_error')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scraper_mangas');
        Schema::dropIfExists('scraper_sources');
    }
};
