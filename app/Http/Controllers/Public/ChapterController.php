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
            ->where('status', 'publie')
            ->with('pages')
            ->firstOrFail();

        $prev = Chapter::where('manga_id', $manga->id)
            ->where('status', 'publie')
            ->where('number', '<', $chapter->number)
            ->orderBy('number', 'desc')
            ->first();

        $next = Chapter::where('manga_id', $manga->id)
            ->where('status', 'publie')
            ->where('number', '>', $chapter->number)
            ->orderBy('number', 'asc')
            ->first();

        $chapter->increment('views_count');

        return view('public.chapter.show', compact('manga', 'chapter', 'prev', 'next'));
    }
}