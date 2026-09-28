<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Favorite;
use App\Models\Manga;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FavoriteController extends Controller
{
    /**
     * Get all favorites for the authenticated user.
     * GET /api/favorites
     */
    public function index(): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'success' => true,
                'favorites' => [],
                'favorite_ids' => [],
                'favorite_slugs' => [],
            ]);
        }

        $favorites = $user->favorites()
            ->with(['manga'])
            ->latest()
            ->get();

        $slugs = [];
        $ids = [];
        $list = [];

        foreach ($favorites as $fav) {
            if (!$fav->manga) continue;
            $slugs[] = $fav->manga->slug;
            $ids[] = $fav->manga->id;
            $list[] = [
                'id' => $fav->manga->id,
                'slug' => $fav->manga->slug,
                'title' => $fav->manga->title,
                'cover' => $fav->manga->cover_image ? \Illuminate\Support\Facades\Storage::url($fav->manga->cover_image) : null,
                'added_at' => $fav->created_at->toISOString(),
            ];
        }

        return response()->json([
            'success' => true,
            'favorites' => $list,
            'favorite_ids' => $ids,
            'favorite_slugs' => $slugs,
        ]);
    }

    /**
     * Toggle favorite status for a manga.
     * POST /api/favorites/toggle
     */
    public function toggle(Request $request): JsonResponse
    {
        $request->validate([
            'manga_id' => 'nullable|integer|exists:mangas,id',
            'slug' => 'nullable|string|exists:mangas,slug',
        ]);

        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Connectez-vous pour ajouter cette série à vos favoris.',
            ], 401);
        }

        $manga = null;
        if ($request->filled('manga_id')) {
            $manga = Manga::find($request->manga_id);
        } elseif ($request->filled('slug')) {
            $manga = Manga::where('slug', $request->slug)->first();
        }

        if (!$manga) {
            return response()->json([
                'success' => false,
                'message' => 'Œuvre non trouvée.',
            ], 404);
        }

        $existing = Favorite::where('user_id', $user->id)
            ->where('manga_id', $manga->id)
            ->first();

        if ($existing) {
            $existing->delete();
            $isFavorite = false;
        } else {
            Favorite::create([
                'user_id' => $user->id,
                'manga_id' => $manga->id,
            ]);
            $isFavorite = true;
        }

        return response()->json([
            'success' => true,
            'is_favorite' => $isFavorite,
            'manga_id' => $manga->id,
            'slug' => $manga->slug,
            'favorites_count' => $manga->favorites()->count(),
        ]);
    }

    /**
     * Sync favorites from client (localStorage) to database upon login.
     * POST /api/favorites/sync
     */
    public function sync(Request $request): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false], 401);
        }

        $slugs = $request->input('slugs', []);
        if (!is_array($slugs) || empty($slugs)) {
            return response()->json(['success' => true, 'synced' => 0]);
        }

        $mangas = Manga::whereIn('slug', $slugs)->pluck('id');
        $now = now();
        $records = $mangas->map(fn ($mangaId) => [
            'user_id' => $user->id,
            'manga_id' => $mangaId,
            'created_at' => $now,
            'updated_at' => $now,
        ])->toArray();

        if (!empty($records)) {
            Favorite::upsert($records, ['user_id', 'manga_id'], ['updated_at']);
        }

        return response()->json([
            'success' => true,
            'synced' => count($records),
        ]);
    }
}
