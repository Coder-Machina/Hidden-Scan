<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Public\HomeController;
use App\Http\Controllers\Public\MangaController;
use App\Http\Controllers\Public\ChapterController;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::prefix('manga')->name('manga.')->group(function () {
    Route::get('/', [MangaController::class, 'index'])->name('index');
    Route::get('/{slug}', [MangaController::class, 'show'])->name('show');
});

Route::prefix('chapitre')->name('chapter.')->group(function () {
    Route::get('/{manga}/{slug}', [ChapterController::class, 'show'])->name('show');
});