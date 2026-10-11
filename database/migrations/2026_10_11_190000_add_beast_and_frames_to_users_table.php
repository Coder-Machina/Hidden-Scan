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
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger('qi_shields')->default(0)->after('streak');
            $table->string('spiritual_beast')->default('shadow_crow')->after('qi_shields');
            $table->string('avatar_frame')->default('novice')->after('spiritual_beast');
            $table->string('custom_title_color')->nullable()->after('avatar_frame');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['qi_shields', 'spiritual_beast', 'avatar_frame', 'custom_title_color']);
        });
    }
};
