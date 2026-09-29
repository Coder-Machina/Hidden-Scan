<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Manga;
use App\Models\Chapter;

class HomeController extends Controller
{
    public function index()
    {
        $featured = Manga::where('is_featured', true)
            ->with(['chapters' => function($q) {
                $q->published()->orderBy('number', 'asc');
            }])
            ->withCount('chapters')
            ->latest()
            ->take(6)
            ->get();

        $latest_updates = Manga::with(['chapters' => function($q) {
                $q->where('status', \App\Enums\ChapterStatus::PUBLIE)
                  ->orderBy('number', 'desc')
                  ->orderBy('published_at', 'desc');
            }])
            ->withMax(['chapters' => function($q) {
                $q->where('status', \App\Enums\ChapterStatus::PUBLIE);
            }], 'published_at')
            ->orderByRaw('COALESCE(chapters_max_published_at, mangas.created_at) DESC')
            ->take(24)
            ->get();

        $popular = Manga::orderBy('views_count', 'desc')
            ->withCount('chapters')
            ->take(12)
            ->get();

        return view('public.home', compact('featured', 'latest_updates', 'popular'));
    }
}