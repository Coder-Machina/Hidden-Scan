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
            ->withCount('chapters')
            ->latest()
            ->take(6)
            ->get();

        $latest_updates = Manga::whereHas('chapters', function($q) {
                $q->where('status', \App\Enums\ChapterStatus::PUBLIE);
            })
            ->with(['chapters' => function($q) {
                $q->where('status', \App\Enums\ChapterStatus::PUBLIE)
                  ->orderBy('number', 'desc')
                  ->orderBy('published_at', 'desc');
            }])
            ->withMax(['chapters' => function($q) {
                $q->where('status', \App\Enums\ChapterStatus::PUBLIE);
            }], 'published_at')
            ->orderByDesc('chapters_max_published_at')
            ->take(24)
            ->get();

        $popular = Manga::orderBy('views_count', 'desc')
            ->withCount('chapters')
            ->take(12)
            ->get();

        return view('public.home', compact('featured', 'latest_updates', 'popular'));
    }
}