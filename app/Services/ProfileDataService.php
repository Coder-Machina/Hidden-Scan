<?php

namespace App\Services;

use App\Models\Genre;
use App\Models\Manga;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

class ProfileDataService
{
    /**
     * Prépare l'ensemble des données riches nécessaires à l'affichage complet du profil Hidden Scan
     * (utilisé à la fois par l'espace personnel /profile et le profil public /user/{username}).
     */
    public static function getProfileData(User $user, ?User $viewer = null, ?string $reqTab = null): array
    {
        $isOwner = $viewer && $viewer->id === $user->id;
        $isStaff = $viewer && $viewer->isStaffOrAdmin();

        // 1. Statistiques de lecture réelles et Voie Martiale Murim
        $readCount = 0;
        $mangasLusCount = 0;
        if (method_exists($user, 'readingProgress')) {
            $readCount = (int) $user->readingProgress()->where('is_read', true)->count();
            $mangasLusCount = (int) $user->readingProgress()->where('is_read', true)->distinct()->count('manga_id');
        }

        $favoritesCount = method_exists($user, 'favorites') ? (int) $user->favorites()->count() : 0;
        $commentsCount = method_exists($user, 'comments') ? (int) $user->comments()->count() : 0;
        $likesReceived = method_exists($user, 'comments') ? (int) ($user->comments()->sum('likes_count') ?? 0) : 0;

        $recentComments = method_exists($user, 'comments')
            ? $user->comments()->with(['commentable'])->latest()->take(6)->get()
            : collect();

        $murimStatus = MurimRankService::calculateStatus($readCount, (int) ($user->streak ?? 0));
        $monthlyHeatmap = LeaderboardService::getMonthlyHeatmap($user);
        $leaderboard = LeaderboardService::getTopCultivators(15, $user);

        $genres = Genre::orderBy('name')->get();

        $allMangas = Manga::select(['id', 'title', 'slug', 'cover_image', 'type'])
            ->orderBy('title')
            ->get();

        // Manga lookup mapping pour les titres & couvertures
        $mangasLookup = $allMangas->mapWithKeys(function ($m) {
            return [$m->slug => [
                'id' => $m->id,
                'title' => $m->title,
                'cover' => $m->cover_image ? Storage::url($m->cover_image) : null,
            ]];
        });

        // 2. Hydratation du Top 5
        $topMangasIds = is_array($user->top_mangas) ? $user->top_mangas : [];
        $topMangasMap = !empty($topMangasIds)
            ? Manga::whereIn('id', $topMangasIds)->get()->keyBy('id')
            : collect();

        // 3. Hydratation de la Tier List
        $tierListConfig = is_array($user->tier_list) ? $user->tier_list : [
            'S' => [], 'A' => [], 'B' => [], 'C' => [], 'D' => [], 'F' => []
        ];

        // 4. Données allMangas préparées pour Javascript / Alpine
        $allMangasData = $allMangas->map(fn($m) => [
            'id' => $m->id,
            'title' => $m->title,
            'slug' => $m->slug,
            'cover' => $m->cover_image ? Storage::url($m->cover_image) : null,
            'type' => is_object($m->type) ? ($m->type->value ?? 'manga') : ($m->type ?? 'manga'),
        ])->values()->all();

        // 5. Bête Spirituelle & Cadres d'Avatar
        $spiritualBeasts = MurimRankService::getSpiritualBeasts();
        $userBeastStatus = MurimRankService::getBeastStatus($user->spiritual_beast ?? 'shadow_crow', $readCount);
        $avatarFrames = MurimRankService::getAvatarFrames($readCount);

        // 6. Favoris de l'utilisateur (BDD)
        $userFavorites = method_exists($user, 'favorites')
            ? $user->favorites()->with('manga')->latest()->take(24)->get()
                ->map(function ($fav) {
                    $m = $fav->manga;
                    if (!$m) return null;
                    return [
                        'slug' => $m->slug,
                        'title' => $m->title,
                        'cover' => $m->cover_image ? Storage::url($m->cover_image) : null,
                        'addedAt' => $fav->created_at ? $fav->created_at->toISOString() : null,
                    ];
                })
                ->filter()
                ->values()
                ->all()
            : [];

        // 7. Historique de lecture de l'utilisateur (BDD)
        $userReadingHistory = method_exists($user, 'readingProgress')
            ? $user->readingProgress()
                ->with(['manga', 'chapter'])
                ->where('is_read', true)
                ->latest('updated_at')
                ->take(30)
                ->get()
                ->map(function ($rp) {
                    $m = $rp->manga;
                    $c = $rp->chapter;
                    if (!$m) return null;
                    return [
                        'mangaSlug' => $m->slug,
                        'chapterSlug' => $c ? $c->slug : ('chapitre-' . ($rp->chapter_number ?? 1)),
                        'mangaTitle' => $m->title,
                        'chapterTitle' => $c ? ($c->title ?: ('Chapitre ' . $c->number)) : ('Chapitre ' . ($rp->chapter_number ?? 1)),
                        'cover' => $m->cover_image ? Storage::url($m->cover_image) : null,
                        'readAt' => $rp->updated_at ? $rp->updated_at->toISOString() : ($rp->created_at ? $rp->created_at->toISOString() : null),
                    ];
                })
                ->filter()
                ->values()
                ->all()
            : [];

        // Collections pour rendu SSR Blade (SEO & tests PHPUnit)
        $favorisCollection = method_exists($user, 'favorites')
            ? $user->favorites()->whereHas('manga')->with('manga')->latest()->take(24)->get()
            : collect();

        $recentsCollection = method_exists($user, 'readingProgress')
            ? $user->readingProgress()
                ->where('is_read', true)
                ->whereHas('manga')
                ->with(['manga', 'chapter'])
                ->latest('updated_at')
                ->take(12)
                ->get()
            : collect();

        $validTabs = ['overview', 'murim', 'activity', 'beast', 'tierlist', 'leaderboard', 'library', 'edit', 'settings'];
        $defaultTab = in_array($reqTab, $validTabs) ? $reqTab : 'overview';

        return [
            'user' => $user,
            'isOwner' => (bool) $isOwner,
            'isStaff' => (bool) $isStaff,
            'defaultTab' => $defaultTab,
            'readCount' => $readCount,
            'mangasLusCount' => $mangasLusCount,
            'favoritesCount' => $favoritesCount,
            'commentsCount' => $commentsCount,
            'likesReceived' => $likesReceived,
            'recentComments' => $recentComments,
            'murimStatus' => $murimStatus,
            'monthlyHeatmap' => $monthlyHeatmap,
            'leaderboard' => $leaderboard,
            'genres' => $genres,
            'allMangas' => $allMangas,
            'allMangasData' => $allMangasData,
            'mangasLookup' => $mangasLookup,
            'topMangasIds' => $topMangasIds,
            'topMangasMap' => $topMangasMap,
            'tierListConfig' => $tierListConfig,
            'spiritualBeasts' => $spiritualBeasts,
            'userBeastStatus' => $userBeastStatus,
            'avatarFrames' => $avatarFrames,
            'currentFrame' => $user->avatar_frame ?? 'novice',
            'qiShields' => (int) ($user->qi_shields ?? 0),
            'userFavorites' => $userFavorites,
            'userReadingHistory' => $userReadingHistory,
            'favoris' => $favorisCollection,
            'recents' => $recentsCollection,
            'stats' => [
                'mangas_lus'    => $mangasLusCount,
                'chapitres_lus' => $readCount,
                'favoris'       => $favoritesCount,
                'membre_depuis' => $user->created_at ? $user->created_at->diffForHumans() : 'Récemment',
            ],
        ];
    }
}
