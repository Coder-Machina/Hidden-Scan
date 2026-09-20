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
        Schema::create('mangas', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('synopsis')->nullable();
            $table->string('cover_image')->nullable();
            $table->string('banner_image')->nullable();
            $table->foreignId('author_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('artist_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('type', ['manga', 'manhwa', 'manhua'])->default('manga');
            $table->enum('status', ['en_cours', 'termine', 'pause', 'abandonne'])->default('en_cours');
            $table->year('release_year')->nullable();
            $table->unsignedBigInteger('views_count')->default(0);
            $table->boolean('is_featured')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mangas');
    }
};
