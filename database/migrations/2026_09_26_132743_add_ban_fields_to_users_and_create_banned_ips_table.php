<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_banned')->default(false)->after('password');
            $table->timestamp('banned_at')->nullable()->after('is_banned');
            $table->string('ban_reason')->nullable()->after('banned_at');
            $table->boolean('is_comment_banned')->default(false)->after('ban_reason');
            $table->timestamp('comment_banned_at')->nullable()->after('is_comment_banned');
            $table->string('comment_ban_reason')->nullable()->after('comment_banned_at');
        });

        Schema::create('banned_ips', function (Blueprint $table) {
            $table->id();
            $table->string('ip_hash')->index();
            $table->string('ip_address')->nullable();
            $table->string('ban_type')->default('comments'); // 'comments' or 'all'
            $table->string('reason')->nullable();
            $table->foreignId('banned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('banned_ips');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'is_banned',
                'banned_at',
                'ban_reason',
                'is_comment_banned',
                'comment_banned_at',
                'comment_ban_reason',
            ]);
        });
    }
};
