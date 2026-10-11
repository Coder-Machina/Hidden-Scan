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
    // Reading Progress & Catch-Up & Murim Rank
    Route::get('/progress', [ReadingProgressController::class, 'getAllProgress'])->name('api.progress.all');
    Route::get('/progress/murim', [ReadingProgressController::class, 'getMurimStatus'])->name('api.progress.murim');
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

    // Recommendations ("Si t'as aimé X, lis Y")
    Route::get('/recommendations', [ReadingProgressController::class, 'getRecommendations'])->name('api.recommendations');

    // Profile Synchronization
    Route::post('/profile/sync', [\App\Http\Controllers\ProfileController::class, 'sync'])->name('api.profile.sync');
});

// ═══ DISCORD BOT STAFF & RELEASES API ═══
Route::prefix('bot')->group(function () {
    Route::get('/mangas', [\App\Http\Controllers\Api\DiscordBotController::class, 'listMangas'])->name('api.bot.mangas');
    Route::get('/fiche/{slug}', [\App\Http\Controllers\Api\DiscordBotController::class, 'getFiche'])->name('api.bot.fiche');
    Route::post('/save-fiche', [\App\Http\Controllers\Api\DiscordBotController::class, 'saveFiche'])->name('api.bot.save-fiche');
    Route::post('/link-role', [\App\Http\Controllers\Api\DiscordBotController::class, 'linkRole'])->name('api.bot.link-role');
    Route::post('/step-done', [\App\Http\Controllers\Api\DiscordBotController::class, 'recordStepDone'])->name('api.bot.step-done');
    Route::post('/toggle-rush', [\App\Http\Controllers\Api\DiscordBotController::class, 'toggleRush'])->name('api.bot.toggle-rush');
    Route::get('/staff-stats', [\App\Http\Controllers\Api\DiscordBotController::class, 'getStaffStats'])->name('api.bot.staff-stats');
    Route::get('/agenda-reminders', [\App\Http\Controllers\Api\DiscordBotController::class, 'getAgendaReminders'])->name('api.bot.agenda-reminders');
});
