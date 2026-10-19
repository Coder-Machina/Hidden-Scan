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
            DB::statement("ALTER TABLE scraper_mangas MODIFY COLUMN status VARCHAR(30) NOT NULL DEFAULT 'active'");
        } else {
            Schema::table('scraper_mangas', function (Blueprint $table) {
                $table->string('status', 30)->default('active')->change();
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
            DB::statement("ALTER TABLE scraper_mangas MODIFY COLUMN status ENUM('active', 'paused', 'error') NOT NULL DEFAULT 'active'");
        } else {
            Schema::table('scraper_mangas', function (Blueprint $table) {
                $table->string('status', 30)->default('active')->change();
            });
        }
    }
};
