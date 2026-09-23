<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('comments', function (Blueprint $table) {
            $table->unsignedInteger('likes_count')->default(0);
        });

        Schema::create('comment_likes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('comment_id')->constrained()->cascadeOnDelete();
            $table->string('ip_hash');
            $table->timestamps();

            $table->unique(['comment_id', 'ip_hash']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comment_likes');
        Schema::table('comments', function (Blueprint $table) {
            $table->dropColumn('likes_count');
        });
    }
};
