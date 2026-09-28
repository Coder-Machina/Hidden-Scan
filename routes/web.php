<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Public\HomeController;
use App\Http\Controllers\Public\MangaController;
use App\Http\Controllers\Public\ChapterController;
use App\Http\Controllers\Public\LibraryController;
use App\Http\Controllers\ProfileController;

// ═══ PUBLIC ROUTES ═══
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/bibliotheque', [LibraryController::class, 'index'])->name('library');
Route::post('/manga/{slug}/noter', [MangaController::class, 'rate'])->name('manga.rate');

Route::get('/surprise-moi', [MangaController::class, 'random'])->name('manga.random');
Route::get('/random', fn() => redirect()->route('manga.random'));

Route::prefix('manga')->name('manga.')->group(function () {
    Route::get('/', [MangaController::class, 'index'])->name('index');
    Route::get('/aleatoire', [MangaController::class, 'random'])->name('aleatoire');
    Route::get('/{slug}', [MangaController::class, 'show'])->name('show');
});

Route::prefix('chapitre')->name('chapter.')->group(function () {
    Route::get('/{manga}/{slug}', [ChapterController::class, 'show'])->name('show');
    Route::post('/{chapter}/vue', [ChapterController::class, 'trackView'])->name('track_view');
    Route::post('/{chapter}/signaler', [\App\Http\Controllers\Public\ChapterReportController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('report');
});

// ═══ LEGAL ROUTES ═══
Route::get('/mentions-legales', function () {
    $content = '<h2>1. Éditeur du site</h2><p>Le site Hidden Scan est édité à titre personnel. Pour toute demande : contact@hiddenscan.test.</p><h2>2. Hébergement</h2><p>Hébergé par Vercel / DigitalOcean.</p>';
    return view('public.legal', ['title' => 'Mentions Légales', 'content' => $content]);
})->name('legal.mentions');

Route::get('/cgu', function () {
    $content = '<h2>1. Acceptation</h2><p>En utilisant ce site, vous acceptez les présentes conditions.</p><h2>2. Utilisation</h2><p>Le site est réservé à un usage personnel et non commercial.</p>';
    return view('public.legal', ['title' => 'Conditions Générales d\'Utilisation', 'content' => $content]);
})->name('legal.cgu');

Route::get('/confidentialite', function () {
    $content = '<h2>1. Données personnelles</h2><p>Hidden Scan ne collecte aucune donnée personnelle vous identifiant directement sans votre consentement. Le stockage se fait localement via votre navigateur (localStorage) pour les favoris et l\'historique.</p><h2>2. Cookies</h2><p>Nous utilisons uniquement des cookies techniques nécessaires au fonctionnement (protection CSRF, préférences de lecture, limitations).</p>';
    return view('public.legal', ['title' => 'Politique de Confidentialité', 'content' => $content]);
})->name('legal.privacy');

Route::get('/dmca', function () {
    $content = '<h2>Digital Millennium Copyright Act</h2><p>Hidden Scan respecte la propriété intellectuelle. Si vous êtes le détenteur des droits d\'une œuvre présente sur ce site et souhaitez son retrait, veuillez nous contacter avec les preuves nécessaires.</p>';
    return view('public.legal', ['title' => 'DMCA', 'content' => $content]);
})->name('legal.dmca');

// ═══ AUTHENTICATED ROUTES (BREEZE) ═══
Route::get('/dashboard', function () {
    return redirect()->route('profile.edit');
})->middleware(['auth'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
