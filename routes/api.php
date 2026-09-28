<?php

use App\Http\Controllers\Api\FavoriteController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ReadingProgressController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['web'])->group(function () {
    // Reading Progress & Catch-Up
    Route::get('/progress', [ReadingProgressController::class, 'getAllProgress'])->name('api.progress.all');
    Route::get('/progress/catch-up', [ReadingProgressController::class, 'getCatchUp'])->name('api.progress.catch-up');
    Route::get('/progress/{manga_id}', [ReadingProgressController::class, 'getMangaProgress'])->name('api.progress.manga');
    Route::post('/progress/toggle', [ReadingProgressController::class, 'toggle'])->name('api.progress.toggle');
    Route::post('/progress/mark-up-to', [ReadingProgressController::class, 'markUpTo'])->name('api.progress.mark-up-to');

    // Favorites
    Route::get('/favorites', [FavoriteController::class, 'index'])->name('api.favorites.index');
    Route::post('/favorites/toggle', [FavoriteController::class, 'toggle'])->name('api.favorites.toggle');
    Route::post('/favorites/sync', [FavoriteController::class, 'sync'])->name('api.favorites.sync');

    // Notifications (New chapters for favorite series)
    Route::get('/notifications', [NotificationController::class, 'index'])->name('api.notifications.index');
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('api.notifications.mark-all-read');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('api.notifications.read');
});
