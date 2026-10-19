<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\ScrapeNewChaptersJob;
use App\Models\Manga;
use App\Models\ScraperManga;
use App\Models\ScraperSource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ScraperController extends Controller
{
    /**
     * Ajoute une nouvelle source de scraping pour un manga.
     * Route: POST /admin/scraper/attach
     */
    public function attach(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'manga_id'   => ['required', 'integer', 'exists:mangas,id'],
            'source_url' => ['required', 'url'],
            'name'       => ['nullable', 'string', 'max:150'],
        ]);

        $manga = Manga::findOrFail($validated['manga_id']);
        $sourceUrl = trim($validated['source_url']);

        // Résolution de l'hôte et de la source
        $parsed = parse_url($sourceUrl);
        $host = $parsed['host'] ?? '';
        $scheme = $parsed['scheme'] ?? 'https';
        $baseUrl = "{$scheme}://{$host}";

        $sourceName = trim($validated['name'] ?? '') ?: ($host ?: 'Source');

        $source = ScraperSource::firstOrCreate(
            ['base_url' => $baseUrl],
            [
                'name'              => $sourceName,
                'sel_chapter_list'  => 'a[href*="/chapter/"], .wp-manga-chapter',
                'sel_chapter_title' => 'a',
                'sel_chapter_url'   => 'a',
                'sel_chapter_date'  => '.chapter-release-date',
                'sel_chapter_pages' => 'img.manga-page-img, .page-break img',
                'headers'           => [
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                ],
                'active'            => true,
            ]
        );

        $nextPriority = ($manga->scraperMangas()->max('priority') ?? 0) + 1;

        $scraperManga = ScraperManga::updateOrCreate(
            [
                'manga_id'          => $manga->id,
                'scraper_source_id' => $source->id,
            ],
            [
                'source_url'               => $sourceUrl,
                'priority'                 => $nextPriority,
                'status'                   => 'importing',
                'cancel_requested'         => false,
                'current_scraping_chapter' => '0',
                'last_error'               => null,
            ]
        );

        // Lance immédiatement le job d'importation
        ScrapeNewChaptersJob::dispatch($scraperManga)->onQueue('scraper');

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success'      => true,
                'message'      => 'Source ajoutée et import lancé.',
                'scraperManga' => [
                    'id'            => $scraperManga->id,
                    'source_name'   => $source->name,
                    'source_url'    => $scraperManga->source_url,
                    'status'        => 'importing',
                    'status_text'   => 'Import en cours — Ch. 0 / ...',
                    'is_importing'  => true,
                ],
            ]);
        }

        return back()->with('success', 'Source ajoutée avec succès et import lancé en arrière-plan.');
    }

    /**
     * Retourne le statut en temps réel d'une source (AJAX).
     * Route: GET /admin/scraper/{scraperManga}/status
     */
    public function status(Request $request, ScraperManga $scraperManga): JsonResponse
    {
        $scraperManga->refresh();

        $status = $scraperManga->status;
        $current = $scraperManga->current_scraping_chapter;
        $total = $scraperManga->total_chapters_detected;
        $last = $scraperManga->last_chapter_scraped;
        $lastCheck = $scraperManga->last_checked_at;

        // Calcul du pourcentage de progression
        $progressPercent = 0;
        if ($status === 'active' || $status === 'actif') {
            $progressPercent = 100;
        } elseif (in_array($status, ['importing', 'en cours d\'import'])) {
            if ($total > 0 && $current !== null && is_numeric($current)) {
                $progressPercent = min(100, max(1, (int) round(((float)$current / $total) * 100)));
            } else {
                $progressPercent = 1;
            }
        }

        // Libellé exact selon la spécification de design
        $statusText = match ($status) {
            'importing', 'en cours d\'import' => "Import en cours — Chapitre " . ($current ?? '0') . " / " . ($total ?? '...'),
            'active', 'actif' => "Actif — Dernier import : Ch. " . ($last ?: '—') . ($lastCheck ? " " . $lastCheck->diffForHumans() : ''),
            'annulé', 'cancelled' => "Annulé — Arrêté au Ch. " . ($last ?: '0') . ($total ? " / {$total}" : ''),
            'paused', 'en pause' => "En pause — Ch. " . ($last ?: '—') . " importé",
            'error', 'erreur' => "Erreur — " . ($scraperManga->last_error ?: 'Erreur inconnue'),
            default => ucfirst($status),
        };

        // Calcul du prochain chapitre à reprendre si annulé
        $nextChapterNum = null;
        if ($status === 'annulé' && $last !== null && is_numeric($last)) {
            $nextChapterNum = ((float)$last) + 1;
        }

        return response()->json([
            'id'               => $scraperManga->id,
            'status'           => $status,
            'status_text'      => $statusText,
            'is_importing'     => in_array($status, ['importing', 'en cours d\'import']),
            'current_chapter'  => $current,
            'total_chapters'   => $total,
            'last_chapter'     => $last,
            'next_chapter'     => $nextChapterNum,
            'progress_percent' => $progressPercent,
            'error'            => $scraperManga->last_error,
        ]);
    }

    /**
     * Déclenche une synchronisation immédiate des nouveaux chapitres en arrière-plan.
     * Route: POST /admin/scraper/{scraperManga}/sync
     */
    public function syncNow(Request $request, ScraperManga $scraperManga): JsonResponse|RedirectResponse
    {
        $scraperManga->update([
            'status'           => 'importing',
            'cancel_requested' => false,
            'last_error'       => null,
        ]);

        ScrapeNewChaptersJob::dispatch($scraperManga)->onQueue('scraper');

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Vérification et synchronisation lancées en arrière-plan.',
                'status'  => 'importing',
            ]);
        }

        return back()->with('success', 'Vérification et import des nouveaux chapitres lancés en arrière-plan.');
    }

    /**
     * Annule l'importation en cours pour cette source.
     * Route: POST /admin/scraper/{scraperManga}/cancel
     */
    public function cancel(Request $request, ScraperManga $scraperManga): JsonResponse|RedirectResponse
    {
        // 1. Passe cancel_requested à true
        $scraperManga->update([
            'cancel_requested' => true,
        ]);

        // 2. Supprime immédiatement les jobs en attente dans la queue pour ce scraperManga
        if (Schema::hasTable('jobs')) {
            DB::table('jobs')->where(function ($q) use ($scraperManga) {
                $q->where('payload', 'like', '%ScrapeNewChaptersJob%')
                  ->where('payload', 'like', '%"id":' . $scraperManga->id . '%');
            })->orWhere(function ($q) use ($scraperManga) {
                $q->where('payload', 'like', '%ScrapeMultiSourceMangaJob%')
                  ->where('payload', 'like', '%"id":' . $scraperManga->manga_id . '%');
            })->delete();
        }

        // Si aucun travail n'était actif immédiatement dans le process (ex: job déjà fini ou en attente),
        // on peut passer l'état à annulé tout de suite.
        if ($scraperManga->status === 'importing') {
            $scraperManga->update([
                'status' => 'annulé',
            ]);
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Import annulé avec succès.',
                'status'  => 'annulé',
            ]);
        }

        return back()->with('success', 'Demande d\'annulation transmise. L\'import s\'arrête proprement.');
    }

    /**
     * Bascule le statut de scraping entre actif et en pause, ou reprend depuis l'état annulé.
     * Route: PATCH /admin/scraper/{scraperManga}/toggle
     */
    public function toggleStatus(Request $request, ScraperManga $scraperManga): JsonResponse|RedirectResponse
    {
        if ($scraperManga->status === 'paused' || $scraperManga->status === 'en pause') {
            $newStatus = 'active';
        } elseif ($scraperManga->status === 'annulé') {
            // Reprise : repasse en import et relance la queue
            $scraperManga->update([
                'status'           => 'importing',
                'cancel_requested' => false,
            ]);
            ScrapeNewChaptersJob::dispatch($scraperManga)->onQueue('scraper');

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'status'  => 'importing',
                    'message' => 'Reprise de l\'import lancée.',
                ]);
            }

            return back()->with('success', 'Reprise de l\'importation enclenchée.');
        } else {
            $newStatus = 'paused';
        }

        $scraperManga->update(['status' => $newStatus]);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'status'  => $newStatus,
                'message' => 'Statut mis à jour.',
            ]);
        }

        return back()->with('success', 'Statut du scraping mis à jour : ' . ucfirst($newStatus));
    }

    /**
     * Réordonne la priorité des sources d'un manga (drag and drop).
     * Route: PATCH /admin/scraper/{scraperManga}/reorder
     */
    public function reorder(Request $request, ScraperManga $scraperManga): JsonResponse
    {
        $order = $request->input('order'); // array d'IDs [3, 1, 2]

        if (is_array($order)) {
            foreach ($order as $priority => $id) {
                ScraperManga::where('id', $id)
                    ->where('manga_id', $scraperManga->manga_id)
                    ->update(['priority' => $priority + 1]);
            }
        } elseif ($request->has('priority')) {
            $scraperManga->update(['priority' => (int) $request->input('priority')]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Ordre de priorité mis à jour.',
        ]);
    }

    /**
     * Supprime / détache une source de scraping d'un manga.
     * Route: DELETE /admin/scraper/{scraperManga}
     */
    public function detach(Request $request, ScraperManga $scraperManga): JsonResponse|RedirectResponse
    {
        $scraperManga->delete();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Source supprimée avec succès.',
            ]);
        }

        return back()->with('success', 'Source supprimée.');
    }
}
