<?php

namespace App\Services;

use App\Enums\ChapterStatus;
use App\Models\Manga;
use App\Models\ReadingProgress;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class RecommendationService
{
    /**
     * Calcule les recommandations "Si t'as aimé X, lis Y" pour un manga source donné.
     *
     * @param Manga $sourceManga
     * @param array<int> $excludeMangaIds
     * @param int $limit
     * @return array
     */
    public function getRecommendationsForManga(Manga $sourceManga, array $excludeMangaIds = [], int $limit = 4): array
    {
        $sourceManga->loadMissing(['genres', 'authors', 'artists', 'tags']);

        $sourceGenreIds = $sourceManga->genres->pluck('id')->toArray();
        $sourceAuthorIds = $sourceManga->authors->pluck('id')->toArray();
        $sourceArtistIds = $sourceManga->artists->pluck('id')->toArray();
        $sourceTagIds = $sourceManga->tags->pluck('id')->toArray();

        // Cherche tous les mangas du catalogue différents du manga source
        $candidatesQuery = Manga::where('id', '!=', $sourceManga->id)
            ->with(['genres', 'authors', 'artists', 'tags', 'chapters' => function ($q) {
                $q->where('status', ChapterStatus::PUBLIE)->orderBy('number', 'asc');
            }]);

        // Priorise les mangas non encore lus si des exclusions existent
        if (!empty($excludeMangaIds)) {
            // S'il existe des candidats en dehors de la liste déjà lue, on filtre
            $unreadCandidatesCount = (clone $candidatesQuery)->whereNotIn('id', $excludeMangaIds)->count();
            if ($unreadCandidatesCount > 0) {
                $candidatesQuery->whereNotIn('id', $excludeMangaIds);
            }
        }

        $candidates = $candidatesQuery->get();

        if ($candidates->isEmpty()) {
            return [];
        }

        $scoredCandidates = [];

        foreach ($candidates as $candidate) {
            $score = 0;
            $reasons = [];

            // 1. Même auteur (+10 pts)
            $commonAuthors = $candidate->authors->whereIn('id', $sourceAuthorIds);
            if ($commonAuthors->isNotEmpty()) {
                $score += $commonAuthors->count() * 10;
                $reasons[] = "Même auteur (" . $commonAuthors->pluck('name')->implode(', ') . ")";
            }

            // 2. Même artiste (+8 pts)
            $commonArtists = $candidate->artists->whereIn('id', $sourceArtistIds);
            if ($commonArtists->isNotEmpty()) {
                $score += $commonArtists->count() * 8;
                if ($commonAuthors->isEmpty()) {
                    $reasons[] = "Même illustrateur (" . $commonArtists->pluck('name')->implode(', ') . ")";
                }
            }

            // 3. Genres en commun (+5 pts par genre)
            $commonGenres = $candidate->genres->whereIn('id', $sourceGenreIds);
            if ($commonGenres->isNotEmpty()) {
                $score += $commonGenres->count() * 5;
                if ($commonGenres->count() >= 2) {
                    $reasons[] = "Genres en commun : " . $commonGenres->take(3)->pluck('name')->implode(', ');
                } else {
                    $reasons[] = "Genre similaire : " . $commonGenres->first()->name;
                }
            }

            // 4. Même type (Manhwa / Manga / Manhua) (+4 pts)
            if ($candidate->type && $sourceManga->type && $candidate->type === $sourceManga->type) {
                $score += 4;
                if (empty($reasons)) {
                    $reasons[] = "Même univers " . $candidate->type->getLabel();
                }
            }

            // 5. Tags en commun (+2 pts par tag)
            $commonTags = $candidate->tags->whereIn('id', $sourceTagIds);
            if ($commonTags->isNotEmpty()) {
                $score += $commonTags->count() * 2;
            }

            // 6. Bonus qualité & popularité (note moyenne et vues)
            if ($candidate->average_rating > 0) {
                $score += (float) $candidate->average_rating;
            }
            if ($candidate->views_count > 0) {
                $score += min(4, $candidate->views_count / 200);
            }

            // Pourcentage de compatibilité (entre 72% et 99%)
            $matchPercent = min(99, max(72, (int) round(72 + ($score * 1.5))));

            $firstChapter = $candidate->chapters->first();

            $scoredCandidates[] = [
                'id' => $candidate->id,
                'title' => $candidate->title,
                'slug' => $candidate->slug,
                'synopsis' => $candidate->synopsis,
                'cover_image' => $candidate->cover_image ? Storage::url($candidate->cover_image) : null,
                'type' => $candidate->type?->getLabel() ?? 'Manga',
                'type_code' => $candidate->type?->value ?? 'manga',
                'score' => $score,
                'match_percent' => $matchPercent,
                'reason' => !empty($reasons) ? implode(' • ', $reasons) : "Recommandé pour vous",
                'shared_genres' => $commonGenres->pluck('name')->values()->all(),
                'chapters_count' => $candidate->chapters->count(),
                'rating' => $candidate->average_rating > 0 ? number_format($candidate->average_rating, 1) : null,
                'url' => route('manga.show', $candidate->slug),
                'first_chapter_url' => $firstChapter ? route('chapter.show', [$candidate->slug, $firstChapter->slug]) : route('manga.show', $candidate->slug),
            ];
        }

        // Tri par score de similarité décroissant
        usort($scoredCandidates, fn($a, $b) => $b['score'] <=> $a['score']);

        return array_slice($scoredCandidates, 0, $limit);
    }

    /**
     * Récupère les recommandations pour l'ensemble des lectures d'un utilisateur connecté.
     *
     * @param User $user
     * @param int $maxSources
     * @param int $limitPerSource
     * @return array
     */
    public function getRecommendationsForUser(User $user, int $maxSources = 6, int $limitPerSource = 4): array
    {
        // 1. Récupère les mangas où l'utilisateur a lu des chapitres
        $readMangaIds = ReadingProgress::where('user_id', $user->id)
            ->where('is_read', true)
            ->pluck('manga_id')
            ->unique()
            ->toArray();

        // Si l'utilisateur n'a pas encore coché de chapitres lus, repli sur ses favoris
        if (empty($readMangaIds)) {
            $readMangaIds = $user->favorites()->pluck('manga_id')->toArray();
        }

        if (empty($readMangaIds)) {
            return [];
        }

        $sourceMangas = Manga::whereIn('id', $readMangaIds)
            ->with(['genres', 'authors', 'artists', 'tags'])
            ->take($maxSources)
            ->get();

        $results = [];

        foreach ($sourceMangas as $sourceManga) {
            $recommendations = $this->getRecommendationsForManga($sourceManga, $readMangaIds, $limitPerSource);

            if (!empty($recommendations)) {
                $results[] = [
                    'source_manga' => [
                        'id' => $sourceManga->id,
                        'title' => $sourceManga->title,
                        'slug' => $sourceManga->slug,
                        'cover_image' => $sourceManga->cover_image ? Storage::url($sourceManga->cover_image) : null,
                        'type' => $sourceManga->type?->getLabel() ?? 'Manga',
                        'url' => route('manga.show', $sourceManga->slug),
                    ],
                    'recommendations' => $recommendations,
                ];
            }
        }

        return $results;
    }

    /**
     * Récupère les recommandations basées sur une liste de slugs (pour les invités via localStorage).
     *
     * @param array<string> $slugs
     * @param int $maxSources
     * @param int $limitPerSource
     * @return array
     */
    public function getRecommendationsForSlugs(array $slugs, int $maxSources = 6, int $limitPerSource = 4): array
    {
        if (empty($slugs)) {
            return [];
        }

        $sourceMangas = Manga::whereIn('slug', $slugs)
            ->with(['genres', 'authors', 'artists', 'tags'])
            ->take($maxSources)
            ->get();

        if ($sourceMangas->isEmpty()) {
            return [];
        }

        $allMangaIds = $sourceMangas->pluck('id')->toArray();
        $results = [];

        foreach ($sourceMangas as $sourceManga) {
            $recommendations = $this->getRecommendationsForManga($sourceManga, $allMangaIds, $limitPerSource);

            if (!empty($recommendations)) {
                $results[] = [
                    'source_manga' => [
                        'id' => $sourceManga->id,
                        'title' => $sourceManga->title,
                        'slug' => $sourceManga->slug,
                        'cover_image' => $sourceManga->cover_image ? Storage::url($sourceManga->cover_image) : null,
                        'type' => $sourceManga->type?->getLabel() ?? 'Manga',
                        'url' => route('manga.show', $sourceManga->slug),
                    ],
                    'recommendations' => $recommendations,
                ];
            }
        }

        return $results;
    }
}
