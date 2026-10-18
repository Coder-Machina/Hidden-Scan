<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\ScrapeNewChaptersJob;
use App\Models\ScraperManga;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ScraperController extends Controller
{
    /**
     * Déclenche une synchronisation immédiate des nouveaux chapitres en arrière-plan.
     */
    public function syncNow(Request $request, ScraperManga $scraperManga): RedirectResponse
    {
        ScrapeNewChaptersJob::dispatch($scraperManga)->onQueue('scraper');

        return back()->with('success', 'Vérification et import des nouveaux chapitres lancés en arrière-plan.');
    }

    /**
     * Bascule le statut de scraping entre actif et en pause.
     */
    public function toggleStatus(Request $request, ScraperManga $scraperManga): RedirectResponse
    {
        $newStatus = $scraperManga->status === 'active' ? 'paused' : 'active';
        $scraperManga->update(['status' => $newStatus]);

        return back()->with('success', 'Statut du scraping mis à jour : ' . ucfirst($newStatus));
    }
}
