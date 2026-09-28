<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('comments', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('dislikes_count')->default(0);
        });

        Schema::create('comment_dislikes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('comment_id')->constrained('comments')->cascadeOnDelete();
            $table->string('ip_hash');
            $table->timestamps();
            
            $table->unique(['comment_id', 'ip_hash']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comment_dislikes');
        
        Schema::table('comments', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn(['user_id', 'dislikes_count']);
        });
    }
};
