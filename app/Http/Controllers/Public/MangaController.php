<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Manga;
use App\Models\Genre;
use Illuminate\Http\Request;

class MangaController extends Controller
{
    public function index(Request $request)
    {
        $query = Manga::query()->with(['genres', 'author']);

        if ($request->filled('genre')) {
            $query->whereHas('genres', function ($q) use ($request) {
                $q->where('slug', $request->genre);
            });
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('q')) {
            $query->where('title', 'like', '%' . $request->q . '%');
        }

        $sort = $request->get('sort', 'latest');
        match($sort) {
            'popular'   => $query->orderBy('views_count', 'desc'),
            'title'     => $query->orderBy('title', 'asc'),
            default     => $query->latest(),
        };

        $mangas = $query->paginate(24);
        $genres = Genre::orderBy('name')->get();

        return view('public.manga.index', compact('mangas', 'genres'));
    }

    public function show(string $slug)
    {
        $manga = Manga::where('slug', $slug)
            ->with(['author', 'artist', 'genres', 'tags', 'chapters' => function ($q) {
                $q->where('status', 'publie')->orderBy('number', 'desc');
            }])
            ->firstOrFail();

        $manga->increment('views_count');

        return view('public.manga.show', compact('manga'));
    }
}