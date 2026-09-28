<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chapter_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chapter_id')->constrained('chapters')->cascadeOnDelete();
            $table->foreignId('manga_id')->constrained('mangas')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type')->default('autre');
            $table->unsignedInteger('page_number')->nullable();
            $table->text('message')->nullable();
            $table->string('status')->default('en_attente');
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['manga_id', 'status']);
            $table->index(['chapter_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chapter_reports');
    }
};
