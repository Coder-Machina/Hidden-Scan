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
            ->latest()
            ->take(6)
            ->get();

        $latest_chapters = Chapter::where('status', 'publie')
            ->with('manga')
            ->orderBy('published_at', 'desc')
            ->take(12)
            ->get();

        $popular = Manga::orderBy('views_count', 'desc')
            ->take(12)
            ->get();

        return view('public.home', compact('featured', 'latest_chapters', 'popular'));
    }
}