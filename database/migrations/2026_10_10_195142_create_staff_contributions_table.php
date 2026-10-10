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
        Schema::create('staff_contributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manga_id')->constrained()->cascadeOnDelete();
            $table->string('step'); // 'trad', 'check', 'clean', 'edit', 'qc'
            $table->decimal('chapter_number', 8, 1);
            $table->string('discord_user_id')->nullable();
            $table->string('discord_user_name')->nullable();
            $table->timestamps();

            $table->index(['manga_id', 'step']);
            $table->index(['discord_user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff_contributions');
    }
};
