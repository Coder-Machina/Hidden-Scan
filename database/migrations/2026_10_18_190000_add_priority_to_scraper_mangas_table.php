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
            $table->unsignedInteger('priority')->default(1)->after('source_url');
            $table->index(['manga_id', 'priority']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('scraper_mangas', function (Blueprint $table) {
            $table->dropIndex(['manga_id', 'priority']);
            $table->dropColumn('priority');
        });
    }
};
