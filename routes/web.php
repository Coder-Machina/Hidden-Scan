<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Public\HomeController;
use App\Http\Controllers\Public\MangaController;
use App\Http\Controllers\Public\ChapterController;
use App\Http\Controllers\Public\LibraryController;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/bibliotheque', [LibraryController::class, 'index'])->name('library');
Route::post('/manga/{slug}/noter', [MangaController::class, 'rate'])->name('manga.rate');

Route::prefix('manga')->name('manga.')->group(function () {
    Route::get('/', [MangaController::class, 'index'])->name('index');
    Route::get('/{slug}', [MangaController::class, 'show'])->name('show');
});

Route::prefix('chapitre')->name('chapter.')->group(function () {
    Route::get('/{manga}/{slug}', [ChapterController::class, 'show'])->name('show');
});