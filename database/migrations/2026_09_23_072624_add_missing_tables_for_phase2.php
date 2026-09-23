<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Ratings (Notation)
        Schema::create('ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manga_id')->constrained()->cascadeOnDelete();
            $table->string('ip_address')->nullable()->index(); // Pour éviter les votes multiples sans compte
            $table->string('fingerprint')->nullable()->index(); // ID unique via localStorage (fallback si IP non fiable)
            $table->tinyInteger('score')->unsigned()->comment('1 to 5');
            $table->timestamps();
            
            $table->unique(['manga_id', 'ip_address', 'fingerprint']);
        });

        // 2. Manga Views (Analytics détaillées)
        Schema::create('manga_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manga_id')->constrained()->cascadeOnDelete();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->date('viewed_at')->index();
            
            $table->unique(['manga_id', 'ip_address', 'viewed_at']);
        });

        // 3. Chapter Views (Analytics détaillées)
        Schema::create('chapter_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chapter_id')->constrained()->cascadeOnDelete();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->date('viewed_at')->index();
            
            $table->unique(['chapter_id', 'ip_address', 'viewed_at']);
        });

        // 4. Daily Statistics (Agrégation pour le dashboard admin)
        Schema::create('daily_statistics', function (Blueprint $table) {
            $table->id();
            $table->date('date')->unique();
            $table->unsignedBigInteger('total_manga_views')->default(0);
            $table->unsignedBigInteger('total_chapter_views')->default(0);
            $table->unsignedInteger('unique_visitors')->default(0);
            $table->unsignedInteger('new_comments')->default(0);
            $table->timestamps();
        });

        // 5. Comment Reports (Modération)
        Schema::create('comment_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('comment_id')->constrained()->cascadeOnDelete();
            $table->string('ip_address')->nullable();
            $table->string('reason');
            $table->boolean('is_resolved')->default(false);
            $table->foreignId('resolved_by')->nullable()->constrained('users');
            $table->timestamps();
        });

        // 6. Manga Alternatives (Titres alternatifs pour la recherche)
        Schema::create('manga_alternatives', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manga_id')->constrained()->cascadeOnDelete();
            $table->string('title')->index();
            $table->timestamps();
        });
        
        // 7. Audit Logs
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained();
            $table->string('action'); // ex: 'published_chapter', 'deleted_manga', 'resolved_report'
            $table->string('model_type')->nullable(); // ex: App\Models\Chapter
            $table->unsignedBigInteger('model_id')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('manga_alternatives');
        Schema::dropIfExists('comment_reports');
        Schema::dropIfExists('daily_statistics');
        Schema::dropIfExists('chapter_views');
        Schema::dropIfExists('manga_views');
        Schema::dropIfExists('ratings');
    }
};
