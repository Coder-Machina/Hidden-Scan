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
            // Discord & Liens de travail
            $table->string('discord_role_id')->nullable()->after('is_featured');
            $table->string('drive_url', 500)->nullable()->after('discord_role_id');
            $table->string('va_url', 500)->nullable()->after('drive_url');
            $table->decimal('va_chapter_number', 8, 1)->default(0)->after('va_url');
            $table->boolean('is_rush')->default(false)->after('va_chapter_number');

            // Staff assigné (mentions Discord, ex: @Neksyos ou @Klate)
            $table->string('trad_user')->nullable()->after('is_rush');
            $table->string('check_user')->nullable()->after('trad_user');
            $table->string('clean_user')->nullable()->after('check_user');
            $table->string('edit_user')->nullable()->after('clean_user');
            $table->string('qc_user')->nullable()->after('edit_user');

            // Dernier chapitre terminé pour chaque étape
            $table->decimal('step_trad_chapter', 8, 1)->default(0)->after('qc_user');
            $table->decimal('step_check_chapter', 8, 1)->default(0)->after('step_trad_chapter');
            $table->decimal('step_clean_chapter', 8, 1)->default(0)->after('step_check_chapter');
            $table->decimal('step_edit_chapter', 8, 1)->default(0)->after('step_clean_chapter');
            $table->decimal('step_qc_chapter', 8, 1)->default(0)->after('step_edit_chapter');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mangas', function (Blueprint $table) {
            $table->dropColumn([
                'discord_role_id',
                'drive_url',
                'va_url',
                'va_chapter_number',
                'is_rush',
                'trad_user',
                'check_user',
                'clean_user',
                'edit_user',
                'qc_user',
                'step_trad_chapter',
                'step_check_chapter',
                'step_clean_chapter',
                'step_edit_chapter',
                'step_qc_chapter',
            ]);
        });
    }
};
