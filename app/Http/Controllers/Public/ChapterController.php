<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Manga;
use App\Models\Chapter;

class ChapterController extends Controller
{
    public function show(string $manga, string $slug)
    {
        $manga = Manga::where('slug', $manga)->firstOrFail();

        $chapter = Chapter::where('manga_id', $manga->id)
            ->where('slug', $slug)
            ->published()
            ->with('pages')
            ->firstOrFail();

        $prev = Chapter::where('manga_id', $manga->id)
            ->published()
            ->where('number', '<', $chapter->number)
            ->orderBy('number', 'desc')
            ->first();

        $next = Chapter::where('manga_id', $manga->id)
            ->published()
            ->where('number', '>', $chapter->number)
            ->orderBy('number', 'asc')
            ->first();

        return view('public.chapter.show', compact('manga', 'chapter', 'prev', 'next'));
    }

    /**
     * Endpoint pour comptabiliser une vue de lecture active (scroll / temps de lecture)
     */
    public function trackView(Chapter $chapter): \Illuminate\Http\JsonResponse
    {
        $counted = $chapter->recordView();

        // Enregistrer également la progression de lecture si l'utilisateur est authentifié
        if (auth()->check()) {
            \App\Models\ReadingProgress::updateOrCreate(
                ['user_id' => auth()->id(), 'chapter_id' => $chapter->id],
                ['manga_id' => $chapter->manga_id, 'is_read' => true]
            );
        }

        return response()->json([
            'success' => true,
            'counted' => $counted,
            'views_count' => $chapter->views_count,
        ]);
    }
}