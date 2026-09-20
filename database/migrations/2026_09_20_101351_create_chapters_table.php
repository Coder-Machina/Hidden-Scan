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
        Schema::create('chapters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manga_id')->constrained()->cascadeOnDelete();
            $table->decimal('number', 8, 1);
            $table->string('title')->nullable();
            $table->string('slug');
            $table->enum('status', ['brouillon', 'controle', 'programme', 'publie'])->default('brouillon');
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->unsignedBigInteger('views_count')->default(0);
            $table->timestamps();

            $table->unique(['manga_id', 'number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chapters');
    }
};
