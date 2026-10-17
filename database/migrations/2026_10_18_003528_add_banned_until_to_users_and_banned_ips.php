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
            if (!Schema::hasColumn('users', 'banned_until')) {
                $table->timestamp('banned_until')->nullable()->after('ban_reason');
            }
            if (!Schema::hasColumn('users', 'comment_banned_until')) {
                $table->timestamp('comment_banned_until')->nullable()->after('comment_ban_reason');
            }
        });

        if (Schema::hasTable('banned_ips')) {
            Schema::table('banned_ips', function (Blueprint $table) {
                if (!Schema::hasColumn('banned_ips', 'banned_until')) {
                    $table->timestamp('banned_until')->nullable()->after('reason');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['banned_until', 'comment_banned_until']);
        });

        if (Schema::hasTable('banned_ips')) {
            Schema::table('banned_ips', function (Blueprint $table) {
                $table->dropColumn(['banned_until']);
            });
        }
    }
};
