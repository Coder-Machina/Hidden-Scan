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
            $q = $request->q;
            $query->where(function($sub) use ($q) {
                $sub->where('title', 'like', '%' . $q . '%')
                    ->orWhereHas('author', function($qAuthor) use ($q) {
                        $qAuthor->where('name', 'like', '%' . $q . '%');
                    })
                    ->orWhereHas('artist', function($qArtist) use ($q) {
                        $qArtist->where('name', 'like', '%' . $q . '%');
                    })
                    ->orWhereHas('tags', function($qTag) use ($q) {
                        $qTag->where('name', 'like', '%' . $q . '%');
                    });
            });
        }

        $sort = $request->get('sort', 'latest');
        match($sort) {
            'popular'   => $query->orderBy('views_count', 'desc'),
            'title'     => $query->orderBy('title', 'asc'),
            'rating'    => $query->orderBy('average_rating', 'desc'),
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
                $q->where('status', \App\Enums\ChapterStatus::PUBLIE)->orderBy('number', 'desc');
            }])
            ->firstOrFail();

        $manga->increment('views_count');

        return view('public.manga.show', compact('manga'));
    }

    public function rate(string $slug, \Illuminate\Http\Request $request)
    {
        $request->validate(['score' => 'required|integer|min:1|max:5']);

        $manga = Manga::where('slug', $slug)->firstOrFail();
        $score = $request->score;

        // Recalcule la moyenne
        $total = ($manga->average_rating * $manga->ratings_count) + $score;
        $manga->ratings_count += 1;
        $manga->average_rating = round($total / $manga->ratings_count, 1);
        $manga->save();

        return response()->json([
            'average' => $manga->average_rating,
            'count'   => $manga->ratings_count,
        ]);
    }
}