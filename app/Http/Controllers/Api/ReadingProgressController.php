<?php

namespace App\Http\Controllers\Api;

use App\Enums\ChapterStatus;
use App\Http\Controllers\Controller;
use App\Models\Chapter;
use App\Models\Manga;
use App\Models\ReadingProgress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ReadingProgressController extends Controller
{
    /**
     * Toggle the read status of a chapter.
     * POST /api/progress/toggle
     */
    public function toggle(Request $request): JsonResponse
    {
        $request->validate([
            'chapter_id' => 'required|integer|exists:chapters,id',
        ]);

        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Veuillez vous connecter pour enregistrer votre progression.',
            ], 401);
        }

        $chapter = Chapter::with('manga')
            ->where('status', ChapterStatus::PUBLIE)
            ->findOrFail($request->chapter_id);

        $progress = ReadingProgress::firstOrNew([
            'user_id' => $user->id,
            'chapter_id' => $chapter->id,
        ]);

        $progress->manga_id = $chapter->manga_id;
        $progress->is_read = !$progress->exists || !$progress->is_read;
        $progress->save();

        $stats = $this->getMangaStats($user->id, $chapter->manga);

        return response()->json([
            'success' => true,
            'is_read' => $progress->is_read,
            'chapter_id' => $chapter->id,
            'manga_id' => $chapter->manga_id,
            'read_count' => $stats['read_count'],
            'total_chapters' => $stats['total_chapters'],
            'percent' => $stats['percent'],
            'read_chapter_ids' => $stats['read_chapter_ids'],
            'next_chapter' => $stats['next_chapter'],
        ]);
    }

    /**
     * Mark all chapters from chapter 1 up to the specified chapter as read.
     * POST /api/progress/mark-up-to
     */
    public function markUpTo(Request $request): JsonResponse
    {
        $request->validate([
            'chapter_id' => 'required|integer|exists:chapters,id',
        ]);

        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Veuillez vous connecter pour enregistrer votre progression.',
            ], 401);
        }

        $targetChapter = Chapter::with('manga')
            ->where('status', ChapterStatus::PUBLIE)
            ->findOrFail($request->chapter_id);

        $chaptersToMark = Chapter::where('manga_id', $targetChapter->manga_id)
            ->where('status', ChapterStatus::PUBLIE)
            ->where('number', '<=', $targetChapter->number)
            ->get();

        if ($chaptersToMark->isNotEmpty()) {
            $now = now();
            $records = $chaptersToMark->map(fn (Chapter $c) => [
                'user_id' => $user->id,
                'manga_id' => $targetChapter->manga_id,
                'chapter_id' => $c->id,
                'is_read' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ])->toArray();

            ReadingProgress::upsert($records, ['user_id', 'chapter_id'], ['is_read', 'updated_at']);
        }

        $stats = $this->getMangaStats($user->id, $targetChapter->manga);

        return response()->json([
            'success' => true,
            'marked_count' => $chaptersToMark->count(),
            'manga_id' => $targetChapter->manga_id,
            'read_count' => $stats['read_count'],
            'total_chapters' => $stats['total_chapters'],
            'percent' => $stats['percent'],
            'read_chapter_ids' => $stats['read_chapter_ids'],
            'next_chapter' => $stats['next_chapter'],
        ]);
    }

    /**
     * Get read progress for a specific manga.
     * GET /api/progress/{manga_id}
     */
    public function getMangaProgress(string|int $manga_id): JsonResponse
    {
        $manga = is_numeric($manga_id)
            ? Manga::find($manga_id)
            : Manga::where('slug', $manga_id)->first();

        if (!$manga) {
            return response()->json([
                'success' => false,
                'message' => 'Œuvre non trouvée.',
            ], 404);
        }

        $userId = Auth::id();
        $stats = $this->getMangaStats($userId, $manga);

        return response()->json([
            'success' => true,
            'manga_id' => $manga->id,
            'manga_slug' => $manga->slug,
            'read_chapter_ids' => $stats['read_chapter_ids'],
            'read_count' => $stats['read_count'],
            'total_chapters' => $stats['total_chapters'],
            'percent' => $stats['percent'],
            'next_chapter' => $stats['next_chapter'],
        ]);
    }

    /**
     * Get read progress for all mangas for current user.
     * GET /api/progress
     */
    public function getAllProgress(): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'success' => true,
                'progress' => [],
            ]);
        }

        $readEntries = ReadingProgress::where('user_id', $user->id)
            ->where('is_read', true)
            ->get()
            ->groupBy('manga_id');

        $result = [];
        foreach ($readEntries as $mangaId => $entries) {
            $manga = Manga::find($mangaId);
            if (!$manga) continue;

            $readChapterIds = $entries->pluck('chapter_id')->toArray();
            $totalChapters = Chapter::where('manga_id', $mangaId)
                ->where('status', ChapterStatus::PUBLIE)
                ->count();

            $nextChapter = Chapter::where('manga_id', $mangaId)
                ->where('status', ChapterStatus::PUBLIE)
                ->whereNotIn('id', $readChapterIds)
                ->orderBy('number', 'asc')
                ->first();

            $result[$manga->slug] = [
                'manga_id' => $manga->id,
                'manga_slug' => $manga->slug,
                'manga_title' => $manga->title,
                'read_count' => count($readChapterIds),
                'total_chapters' => $totalChapters,
                'percent' => $totalChapters > 0 ? round((count($readChapterIds) / $totalChapters) * 100) : 0,
                'read_chapter_ids' => $readChapterIds,
                'next_chapter' => $nextChapter ? [
                    'id' => $nextChapter->id,
                    'number' => $nextChapter->number,
                    'title' => $nextChapter->title,
                    'slug' => $nextChapter->slug,
                    'url' => route('chapter.show', [$manga->slug, $nextChapter->slug]),
                ] : null,
            ];
        }

        return response()->json([
            'success' => true,
            'progress' => $result,
        ]);
    }

    /**
     * Get unread chapters for all ongoing mangas (Mode Rattrapage), sorted by release date.
     * GET /api/progress/catch-up
     */
    public function getCatchUp(): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'success' => true,
                'catch_up' => [],
                'unread_total' => 0,
            ]);
        }

        $mangaIds = ReadingProgress::where('user_id', $user->id)
            ->where('is_read', true)
            ->pluck('manga_id')
            ->unique();

        $mangas = Manga::whereIn('id', $mangaIds)
            ->with(['chapters' => function ($q) {
                $q->where('status', ChapterStatus::PUBLIE)->orderBy('number', 'asc');
            }])
            ->get();

        $allReadEntries = ReadingProgress::where('user_id', $user->id)
            ->whereIn('manga_id', $mangaIds)
            ->where('is_read', true)
            ->get()
            ->groupBy('manga_id');

        $catchUpChapters = [];
        foreach ($mangas as $manga) {
            $readChapterIds = $allReadEntries->get($manga->id, collect())->pluck('chapter_id')->toArray();
            $totalChapters = $manga->chapters->count();
            $readCount = count($readChapterIds);

            if ($readCount > 0 && $readCount < $totalChapters) {
                $unread = $manga->chapters->whereNotIn('id', $readChapterIds);
                foreach ($unread as $chapter) {
                    $pubDate = $chapter->published_at ?? $chapter->created_at;
                    $catchUpChapters[] = [
                        'chapter_id' => $chapter->id,
                        'chapter_number' => $chapter->number,
                        'chapter_title' => $chapter->title,
                        'chapter_slug' => $chapter->slug,
                        'published_at' => $pubDate?->toISOString(),
                        'published_at_formatted' => $pubDate ? $pubDate->translatedFormat('d M Y') : '',
                        'published_at_human' => $pubDate ? $pubDate->diffForHumans() : '',
                        'timestamp' => $pubDate ? $pubDate->timestamp : 0,
                        'is_recent' => $pubDate ? $pubDate->diffInDays(now()) <= 3 : false,
                        'manga_id' => $manga->id,
                        'manga_title' => $manga->title,
                        'manga_slug' => $manga->slug,
                        'manga_cover' => $manga->cover_image ? Storage::url($manga->cover_image) : null,
                        'manga_type' => $manga->type?->getLabel() ?? 'Manga',
                        'url' => route('chapter.show', [$manga->slug, $chapter->slug]),
                    ];
                }
            }
        }

        $sorted = collect($catchUpChapters)->sortByDesc('timestamp')->values()->all();

        return response()->json([
            'success' => true,
            'catch_up' => $sorted,
            'unread_total' => count($sorted),
        ]);
    }

    /**
     * Helper to calculate reading statistics and next unread chapter.
     */
    protected function getMangaStats(?int $userId, Manga $manga): array
    {
        $totalChapters = Chapter::where('manga_id', $manga->id)
            ->where('status', ChapterStatus::PUBLIE)
            ->count();

        $readChapterIds = [];
        if ($userId) {
            $readChapterIds = ReadingProgress::where('user_id', $userId)
                ->where('manga_id', $manga->id)
                ->where('is_read', true)
                ->pluck('chapter_id')
                ->toArray();
        }

        $readCount = count($readChapterIds);
        $percent = $totalChapters > 0 ? round(($readCount / $totalChapters) * 100) : 0;

        $nextChapter = Chapter::where('manga_id', $manga->id)
            ->where('status', ChapterStatus::PUBLIE)
            ->whereNotIn('id', $readChapterIds)
            ->orderBy('number', 'asc')
            ->first();

        // Fallback to first chapter if everything or nothing read
        if (!$nextChapter && $readCount === 0) {
            $nextChapter = Chapter::where('manga_id', $manga->id)
                ->where('status', ChapterStatus::PUBLIE)
                ->orderBy('number', 'asc')
                ->first();
        }

        return [
            'read_count' => $readCount,
            'total_chapters' => $totalChapters,
            'percent' => $percent,
            'read_chapter_ids' => $readChapterIds,
            'next_chapter' => $nextChapter ? [
                'id' => $nextChapter->id,
                'number' => $nextChapter->number,
                'title' => $nextChapter->title,
                'slug' => $nextChapter->slug,
                'url' => route('chapter.show', [$manga->slug, $nextChapter->slug]),
            ] : null,
        ];
    }

    /**
     * Get recommendations "Si t'as aimé X, lis Y" for user or given slugs.
     * GET /api/recommendations
     */
    public function getRecommendations(Request $request, \App\Services\RecommendationService $service): JsonResponse
    {
        $user = Auth::user();

        if ($user) {
            $recommendations = $service->getRecommendationsForUser($user);
        } else {
            $slugs = array_filter(explode(',', (string) $request->query('slugs', '')));
            $recommendations = $service->getRecommendationsForSlugs($slugs);
        }

        return response()->json([
            'success' => true,
            'recommendations' => $recommendations,
        ]);
    }
}
