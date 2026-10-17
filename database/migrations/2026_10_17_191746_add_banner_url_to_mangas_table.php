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
            $after = Schema::hasColumn('mangas', 'cover_url')
                ? 'cover_url'
                : (Schema::hasColumn('mangas', 'banner_image') ? 'banner_image' : 'cover_image');

            $table->string('banner_url')->nullable()->after($after);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mangas', function (Blueprint $table) {
            $table->dropColumn('banner_url');
        });
    }
};
