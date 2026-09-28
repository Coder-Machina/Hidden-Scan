<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Create pivot table for authors
        Schema::create('author_manga', function (Blueprint $table) {
            $table->id();
            $table->foreignId('author_id')->constrained()->cascadeOnDelete();
            $table->foreignId('manga_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['author_id', 'manga_id']);
        });

        // 2. Create pivot table for artists
        Schema::create('artist_manga', function (Blueprint $table) {
            $table->id();
            $table->foreignId('artist_id')->constrained()->cascadeOnDelete();
            $table->foreignId('manga_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['artist_id', 'manga_id']);
        });

        // 3. Migrate existing data from author_id column to pivot table
        $mangas = DB::table('mangas')->whereNotNull('author_id')->get();
        foreach ($mangas as $manga) {
            DB::table('author_manga')->insert([
                'author_id' => $manga->author_id,
                'manga_id' => $manga->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 4. Migrate existing data from artist_id column to pivot table
        $mangas = DB::table('mangas')->whereNotNull('artist_id')->get();
        foreach ($mangas as $manga) {
            DB::table('artist_manga')->insert([
                'artist_id' => $manga->artist_id,
                'manga_id' => $manga->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 5. Drop old foreign key columns
        Schema::table('mangas', function (Blueprint $table) {
            $table->dropForeign(['author_id']);
            $table->dropForeign(['artist_id']);
            $table->dropColumn(['author_id', 'artist_id']);
        });
    }

    public function down(): void
    {
        // Re-add columns
        Schema::table('mangas', function (Blueprint $table) {
            $table->foreignId('author_id')->nullable()->after('banner_image')->constrained()->nullOnDelete();
            $table->foreignId('artist_id')->nullable()->after('author_id')->constrained()->nullOnDelete();
        });

        // Migrate data back (take first author/artist per manga)
        $authorMangas = DB::table('author_manga')
            ->selectRaw('manga_id, MIN(author_id) as author_id')
            ->groupBy('manga_id')
            ->get();
        foreach ($authorMangas as $row) {
            DB::table('mangas')->where('id', $row->manga_id)->update(['author_id' => $row->author_id]);
        }

        $artistMangas = DB::table('artist_manga')
            ->selectRaw('manga_id, MIN(artist_id) as artist_id')
            ->groupBy('manga_id')
            ->get();
        foreach ($artistMangas as $row) {
            DB::table('mangas')->where('id', $row->manga_id)->update(['artist_id' => $row->artist_id]);
        }

        Schema::dropIfExists('author_manga');
        Schema::dropIfExists('artist_manga');
    }
};
