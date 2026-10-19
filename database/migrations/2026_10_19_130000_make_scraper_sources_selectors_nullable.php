<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = DB::getDriverName();
        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement("ALTER TABLE scraper_sources MODIFY COLUMN sel_chapter_list VARCHAR(255) NULL");
            DB::statement("ALTER TABLE scraper_sources MODIFY COLUMN sel_chapter_title VARCHAR(255) NULL");
            DB::statement("ALTER TABLE scraper_sources MODIFY COLUMN sel_chapter_url VARCHAR(255) NULL");
            DB::statement("ALTER TABLE scraper_sources MODIFY COLUMN sel_chapter_pages VARCHAR(255) NULL");
        } else {
            Schema::table('scraper_sources', function (Blueprint $table) {
                $table->string('sel_chapter_list')->nullable()->change();
                $table->string('sel_chapter_title')->nullable()->change();
                $table->string('sel_chapter_url')->nullable()->change();
                $table->string('sel_chapter_pages')->nullable()->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::getDriverName();
        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement("ALTER TABLE scraper_sources MODIFY COLUMN sel_chapter_list VARCHAR(255) NOT NULL");
            DB::statement("ALTER TABLE scraper_sources MODIFY COLUMN sel_chapter_title VARCHAR(255) NOT NULL");
            DB::statement("ALTER TABLE scraper_sources MODIFY COLUMN sel_chapter_url VARCHAR(255) NOT NULL");
            DB::statement("ALTER TABLE scraper_sources MODIFY COLUMN sel_chapter_pages VARCHAR(255) NOT NULL");
        } else {
            Schema::table('scraper_sources', function (Blueprint $table) {
                $table->string('sel_chapter_list')->nullable(false)->change();
                $table->string('sel_chapter_title')->nullable(false)->change();
                $table->string('sel_chapter_url')->nullable(false)->change();
                $table->string('sel_chapter_pages')->nullable(false)->change();
            });
        }
    }
};
