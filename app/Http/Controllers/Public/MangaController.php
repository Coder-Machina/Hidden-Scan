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
        $query = Manga::query()->with(['genres', 'authors']);

        // Inclusion de genres (AND ou OR, ici on fait un AND strict pour les recherches avancées)
        if ($request->filled('include_genres') && is_array($request->include_genres)) {
            foreach ($request->include_genres as $genreSlug) {
                $query->whereHas('genres', function ($q) use ($genreSlug) {
                    $q->where('slug', $genreSlug);
                });
            }
        } elseif ($request->filled('genre')) { // Backward compatibility
            $query->whereHas('genres', function ($q) use ($request) {
                $q->where('slug', $request->genre);
            });
        }

        // Exclusion de genres
        if ($request->filled('exclude_genres') && is_array($request->exclude_genres)) {
            $query->whereDoesntHave('genres', function ($q) use ($request) {
                $q->whereIn('slug', $request->exclude_genres);
            });
        }

        // Inclusion de tags
        if ($request->filled('include_tags') && is_array($request->include_tags)) {
            foreach ($request->include_tags as $tagSlug) {
                $query->whereHas('tags', function ($q) use ($tagSlug) {
                    $q->where('slug', $tagSlug);
                });
            }
        } elseif ($request->filled('tag')) {
            $query->whereHas('tags', function ($q) use ($request) {
                $q->where('slug', $request->tag);
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
                    ->orWhereHas('authors', function($qAuthor) use ($q) {
                        $qAuthor->where('name', 'like', '%' . $q . '%');
                    })
                    ->orWhereHas('artists', function($qArtist) use ($q) {
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
        $tags = \App\Models\Tag::orderBy('name')->get();

        return view('public.manga.index', compact('mangas', 'genres', 'tags'));
    }

    public function show(string $slug)
    {
        $manga = Manga::where('slug', $slug)
            ->with(['authors', 'artists', 'genres', 'tags', 'chapters' => function ($q) {
                $q->where('status', \App\Enums\ChapterStatus::PUBLIE)->orderBy('number', 'desc');
            }])
            ->firstOrFail();

        $readChapterIds = [];
        $firstUnreadChapter = null;
        if (auth()->check()) {
            $readChapterIds = \App\Models\ReadingProgress::where('user_id', auth()->id())
                ->where('manga_id', $manga->id)
                ->where('is_read', true)
                ->pluck('chapter_id')
                ->toArray();

            $firstUnreadChapter = $manga->chapters
                ->whereNotIn('id', $readChapterIds)
                ->sortBy('number')
                ->first();
        }

        $firstChapter = $manga->chapters->sortBy('number')->first();
        $resumeChapter = $firstUnreadChapter ?? $firstChapter;

        return view('public.manga.show', compact('manga', 'readChapterIds', 'resumeChapter'));
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

    /**
     * Lance un manga aléatoire ciblé selon les genres préférés de l'utilisateur.
     */
    public function random(Request $request)
    {
        $user = auth()->user();
        $targetGenreIds = collect();
        $genreNames = collect();

        // 1. Si un genre spécifique est demandé (?genre=action)
        if ($request->filled('genre')) {
            $specificGenre = Genre::where('slug', $request->genre)
                ->orWhereRaw('LOWER(TRIM(name)) = ?', [strtolower(trim($request->genre))])
                ->first();
            if ($specificGenre) {
                $targetGenreIds->push($specificGenre->id);
                $genreNames->push($specificGenre->name);
            }
        }

        // 2. Si l'utilisateur est connecté
        if ($user) {
            // A. Genre favori explicite dans le profil
            if (!empty($user->favorite_genre)) {
                $favGenre = Genre::whereRaw('LOWER(TRIM(name)) = ?', [strtolower(trim($user->favorite_genre))])
                    ->orWhere('slug', strtolower(trim($user->favorite_genre)))
                    ->first();
                if ($favGenre) {
                    $targetGenreIds->push($favGenre->id);
                    $genreNames->push($favGenre->name);
                }
            }

            // B. Genres issus des séries en favoris
            $userFavoriteMangaIds = $user->favorites()->pluck('manga_id');
            if ($userFavoriteMangaIds->isNotEmpty()) {
                $favoriteGenres = Genre::whereHas('mangas', function ($q) use ($userFavoriteMangaIds) {
                    $q->whereIn('mangas.id', $userFavoriteMangaIds);
                })->pluck('name', 'id');

                foreach ($favoriteGenres as $id => $name) {
                    $targetGenreIds->push($id);
                    $genreNames->push($name);
                }
            }

            // C. Genres issus de l'historique de lecture
            $userProgressMangaIds = $user->readingProgress()->pluck('manga_id');
            if ($userProgressMangaIds->isNotEmpty()) {
                $progressGenres = Genre::whereHas('mangas', function ($q) use ($userProgressMangaIds) {
                    $q->whereIn('mangas.id', $userProgressMangaIds);
                })->pluck('name', 'id');

                foreach ($progressGenres as $id => $name) {
                    $targetGenreIds->push($id);
                    $genreNames->push($name);
                }
            }
        } else {
            // 3. Si visiteur : vérifie si des favoris localStorage sont transmis
            if ($request->filled('favs')) {
                $favSlugs = array_filter(explode(',', (string) $request->favs));
                if (!empty($favSlugs)) {
                    $guestGenres = Genre::whereHas('mangas', function ($q) use ($favSlugs) {
                        $q->whereIn('mangas.slug', $favSlugs);
                    })->pluck('name', 'id');

                    foreach ($guestGenres as $id => $name) {
                        $targetGenreIds->push($id);
                        $genreNames->push($name);
                    }
                }
            }
        }

        $targetGenreIds = $targetGenreIds->unique()->values();
        $genreNames = $genreNames->unique()->values();

        $manga = null;
        $reason = null;

        // Requête ciblée si des genres préférés sont disponibles
        if ($targetGenreIds->isNotEmpty()) {
            $targetedQuery = Manga::query()->whereHas('genres', function ($q) use ($targetGenreIds) {
                $q->whereIn('genres.id', $targetGenreIds);
            })->with('genres');

            // Privilégier une série que l'utilisateur n'a pas encore ajoutée à ses favoris
            if ($user && isset($userFavoriteMangaIds) && $userFavoriteMangaIds->isNotEmpty()) {
                $unfavorited = (clone $targetedQuery)
                    ->whereNotIn('id', $userFavoriteMangaIds)
                    ->inRandomOrder()
                    ->first();
                if ($unfavorited) {
                    $manga = $unfavorited;
                }
            }

            if (!$manga) {
                $manga = $targetedQuery->inRandomOrder()->first();
            }

            if ($manga) {
                $matchedNames = $manga->genres->whereIn('id', $targetGenreIds)->pluck('name')->implode(', ');
                $reason = $matchedNames 
                    ? "Sélectionné selon vos genres préférés : {$matchedNames}" 
                    : "Sélectionné selon vos préférences de lecture";
            }
        }

        // Repli : tirage aléatoire complet du catalogue
        if (!$manga) {
            $manga = Manga::with('genres')->inRandomOrder()->first();
            $reason = "Découverte aléatoire du catalogue";
        }

        if (!$manga) {
            return redirect()->route('manga.index')->with('error', 'Aucun manga disponible actuellement.');
        }

        return redirect()->route('manga.show', $manga->slug)->with('surprise_info', $reason);
    }
}