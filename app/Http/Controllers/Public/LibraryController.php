<?php

namespace App\Http\Controllers\Public;

use App\Enums\ChapterStatus;
use App\Http\Controllers\Controller;
use App\Models\Manga;
use App\Models\ReadingProgress;
use App\Services\RecommendationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class LibraryController extends Controller
{
    public function index(RecommendationService $recommendationService)
    {
        $mangasWithProgress = [];
        $catchUpChapters = [];
        $catchUpByManga = [];
        $unreadTotal = 0;
        $recommendations = [];

        if (Auth::check()) {
            $user = Auth::user();
            $userId = $user->id;
            $recommendations = $recommendationService->getRecommendationsForUser($user);

            // Find all manga IDs where user has at least one read chapter
            $mangaIds = ReadingProgress::where('user_id', $userId)
                ->where('is_read', true)
                ->pluck('manga_id')
                ->unique();

            $mangas = Manga::whereIn('id', $mangaIds)
                ->with(['chapters' => function ($q) {
                    $q->where('status', ChapterStatus::PUBLIE)->orderBy('number', 'asc');
                }])
                ->get();

            // Preload read entries for these mangas
            $allReadEntries = ReadingProgress::where('user_id', $userId)
                ->whereIn('manga_id', $mangaIds)
                ->where('is_read', true)
                ->get()
                ->groupBy('manga_id');

            foreach ($mangas as $manga) {
                $totalChapters = $manga->chapters->count();
                $readEntries = $allReadEntries->get($manga->id, collect());
                $readChapterIds = $readEntries->pluck('chapter_id')->toArray();

                $readCount = count($readChapterIds);
                $percent = $totalChapters > 0 ? round(($readCount / $totalChapters) * 100) : 0;

                $nextChapter = $manga->chapters
                    ->whereNotIn('id', $readChapterIds)
                    ->first();

                // If all read, fallback to first chapter for re-reading
                $resumeChapter = $nextChapter ?? $manga->chapters->first();
                $isCompleted = ($readCount >= $totalChapters && $totalChapters > 0);

                $mangasWithProgress[] = [
                    'manga' => $manga,
                    'read_count' => $readCount,
                    'total_chapters' => $totalChapters,
                    'percent' => $percent,
                    'is_completed' => $isCompleted,
                    'resume_chapter' => $resumeChapter,
                ];

                // Mode "Rattrapage": only ongoing mangas with unread chapters
                if ($readCount > 0 && !$isCompleted) {
                    $unreadChapters = $manga->chapters->whereNotIn('id', $readChapterIds);
                    $mangaUnreadList = [];

                    foreach ($unreadChapters as $chapter) {
                        $pubDate = $chapter->published_at ?? $chapter->created_at;
                        $chapterItem = [
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
                            'manga_type_code' => $manga->type?->value ?? 'manga',
                            'url' => route('chapter.show', [$manga->slug, $chapter->slug]),
                        ];

                        $catchUpChapters[] = $chapterItem;
                        $mangaUnreadList[] = $chapterItem;
                    }

                    if (count($mangaUnreadList) > 0) {
                        $catchUpByManga[] = [
                            'manga_id' => $manga->id,
                            'manga_title' => $manga->title,
                            'manga_slug' => $manga->slug,
                            'manga_cover' => $manga->cover_image ? Storage::url($manga->cover_image) : null,
                            'manga_type' => $manga->type?->getLabel() ?? 'Manga',
                            'unread_count' => count($mangaUnreadList),
                            'unread_chapters' => collect($mangaUnreadList)->sortByDesc('timestamp')->values()->all(),
                            'earliest_unread' => collect($mangaUnreadList)->sortBy('chapter_number')->first(),
                            'latest_timestamp' => collect($mangaUnreadList)->max('timestamp') ?? 0,
                        ];
                    }
                }
            }

            // Sort all catch-up chapters by release date descending (newest release first)
            $catchUpChapters = collect($catchUpChapters)->sortByDesc('timestamp')->values()->all();

            // Sort catch-up by manga by latest unread chapter release date descending
            $catchUpByManga = collect($catchUpByManga)->sortByDesc('latest_timestamp')->values()->all();

            $unreadTotal = count($catchUpChapters);
        }

        return view('public.library', compact('mangasWithProgress', 'catchUpChapters', 'catchUpByManga', 'unreadTotal', 'recommendations'));
    }
}