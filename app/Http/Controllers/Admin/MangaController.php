<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\ScrapeFullMangaJob;
use App\Jobs\ScrapeNewChaptersJob;
use App\Models\Author;
use App\Models\Genre;
use App\Models\Manga;
use App\Models\ScraperManga;
use App\Models\ScraperSource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MangaController extends Controller
{
    /**
     * Affiche le formulaire de création d'œuvre.
     */
    public function create(): View
    {
        $auteurs = Author::orderBy('name')->get();
        $genres = Genre::orderBy('name')->get();
        $scraperSources = ScraperSource::where('active', true)->get();

        return view('admin.manga.create', compact('auteurs', 'genres', 'scraperSources'));
    }

    /**
     * Enregistre une nouvelle œuvre et lie la source de scraping si fournie.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title'              => 'required|string|max:255',
            'slug'               => 'nullable|string|max:255',
            'synopsis'           => 'nullable|string',
            'type'               => 'nullable|string',
            'status'             => 'nullable|string',
            'scraper_source_id'  => 'nullable|exists:scraper_sources,id',
            'scraper_source_url' => 'nullable|url',
        ]);

        $slug = $validated['slug'] ?? \Illuminate\Support\Str::slug($validated['title']);

        $manga = Manga::create([
            'title'       => $validated['title'],
            'slug'        => $slug,
            'synopsis'    => $validated['synopsis'] ?? '',
            'type'        => $validated['type'] ?? 'manga',
            'status'      => $validated['status'] ?? 'en_cours',
            'views_count' => 0,
        ]);

        // ── AJOUT : lier source scraping si renseignée ──────
        if ($request->filled('scraper_source_id')
            && $request->filled('scraper_source_url')) {

            $scraperManga = ScraperManga::create([
                'manga_id'          => $manga->id,
                'scraper_source_id' => $request->scraper_source_id,
                'source_url'        => $request->scraper_source_url,
                'status'            => 'active',
            ]);

            // Lancer l'import initial en arrière-plan
            ScrapeFullMangaJob::dispatch($scraperManga)
                              ->onQueue('scraper');
        }

        return redirect()->route('admin.manga.edit', $manga)->with('success', 'Œuvre créée avec succès.');
    }

    /**
     * Affiche le formulaire d'édition d'une œuvre.
     */
    public function edit(Manga $manga): View
    {
        $auteurs = Author::orderBy('name')->get();
        $genres = Genre::orderBy('name')->get();
        $scraperSources = ScraperSource::where('active', true)->get();

        return view('admin.manga.edit', compact('manga', 'auteurs', 'genres', 'scraperSources'));
    }

    /**
     * Met à jour une œuvre et gère la source de scraping.
     */
    public function update(Request $request, Manga $manga): RedirectResponse
    {
        $validated = $request->validate([
            'title'              => 'sometimes|required|string|max:255',
            'slug'               => 'nullable|string|max:255',
            'synopsis'           => 'nullable|string',
            'type'               => 'nullable|string',
            'status'             => 'nullable|string',
            'scraper_source_id'  => 'nullable',
            'scraper_source_url' => 'nullable|url',
        ]);

        $manga->update(collect($validated)->except(['scraper_source_id', 'scraper_source_url'])->toArray());

        // ── AJOUT : mettre à jour ou créer le lien scraping ─
        if ($request->filled('scraper_source_id')
            && $request->filled('scraper_source_url')) {

            $scraperManga = ScraperManga::updateOrCreate(
                ['manga_id' => $manga->id],
                [
                    'scraper_source_id' => $request->scraper_source_id,
                    'source_url'        => $request->scraper_source_url,
                    'status'            => 'active',
                ]
            );

            // Lancer une vérification immédiate
            ScrapeNewChaptersJob::dispatch($scraperManga)
                                ->onQueue('scraper');

        } elseif ($request->scraper_source_id === '') {
            // Si la source est retirée, supprimer le lien
            $manga->scraperManga?->delete();
        }

        return back()->with('success', 'Œuvre mise à jour avec succès.');
    }
}
