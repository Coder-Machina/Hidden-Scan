<?php

namespace App\Services;

use App\Enums\ChapterStatus;
use App\Models\Chapter;
use App\Models\ChapterPage;
use App\Models\ScraperManga;
use App\Models\ScraperSource;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\ImageManager;
use Symfony\Component\DomCrawler\Crawler;

class ScraperService
{
    /**
     * Crée un client HTTP configuré avec les en-têtes de la source.
     */
    protected function getClient(ScraperSource $source, ?string $referer = null)
    {
        $headers = $source->headers ?? [];
        if (!isset($headers['User-Agent'])) {
            $headers['User-Agent'] = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';
        }
        if ($referer) {
            $headers['Referer'] = $referer;
        } elseif (!isset($headers['Referer']) && $source->base_url) {
            $headers['Referer'] = $source->base_url;
        }

        return Http::timeout(45)->withHeaders($headers);
    }

    /**
     * Récupère la liste des chapitres d'un manga depuis l'URL source.
     *
     * @return array<int, array{number: float, title: string, url: string, date: ?string}>
     */
    public function getChapterList(ScraperManga $scraperManga): array
    {
        $source = $scraperManga->scraperSource ?? $scraperManga->source;
        if (!$source) {
            throw new \RuntimeException("Aucune source de scraping associée.");
        }

        $response = $this->getClient($source)->get($scraperManga->source_url);
        if (!$response->successful()) {
            throw new \RuntimeException("Impossible de charger la page source : HTTP " . $response->status());
        }

        $html = $response->body();
        $crawler = new Crawler($html, $scraperManga->source_url);

        $chapterNodes = $crawler->filter($source->sel_chapter_list);
        $chapters = [];

        $chapterNodes->each(function (Crawler $node) use ($source, $scraperManga, &$chapters) {
            try {
                // Extraction de l'URL et du titre
                $titleNode = $source->sel_chapter_title ? $node->filter($source->sel_chapter_title) : $node;
                $urlNode = $source->sel_chapter_url ? $node->filter($source->sel_chapter_url) : $node;

                $title = $titleNode->count() > 0 ? trim($titleNode->text()) : '';
                $url = $urlNode->count() > 0 ? trim($urlNode->attr('href') ?? '') : '';

                if (empty($url)) {
                    return;
                }

                // Normalisation URL si relative
                if (str_starts_with($url, '/')) {
                    $url = rtrim($source->base_url, '/') . $url;
                }

                // Date optionnelle
                $date = null;
                if ($source->sel_chapter_date) {
                    $dateNode = $node->filter($source->sel_chapter_date);
                    if ($dateNode->count() > 0) {
                        $date = trim($dateNode->text());
                    }
                }

                // Détection du numéro de chapitre
                $number = $this->extractChapterNumber($title, $url);
                if ($number === null) {
                    return;
                }

                $chapters[] = [
                    'number' => $number,
                    'title'  => $title ?: ('Chapitre ' . $number),
                    'url'    => $url,
                    'date'   => $date,
                ];
            } catch (\Throwable $e) {
                Log::warning("Erreur lors de l'analyse d'un chapitre du manga {$scraperManga->manga_id}: " . $e->getMessage());
            }
        });

        // Tri par numéro de chapitre croissant (1, 2, 3...)
        usort($chapters, fn($a, $b) => $a['number'] <=> $b['number']);

        return $chapters;
    }

    /**
     * Extrait le numéro de chapitre depuis le titre ou l'URL.
     */
    public function extractChapterNumber(string $title, string $url): ?float
    {
        $patterns = [
            '/(?:chapitre|chapter|chap|ch|ep|épisode|episode)[\s._-]*([0-9]+(?:\.[0-9]+)?)/i',
            '/[\/-]([0-9]+(?:\.[0-9]+)?)\/?$/',
            '/\b([0-9]+(?:\.[0-9]+)?)\b/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $title, $matches)) {
                return (float) $matches[1];
            }
        }

        if (preg_match('/(?:chapitre|chapter|ch)[\s._-]*([0-9]+(?:\.[0-9]+)?)/i', $url, $matches)) {
            return (float) $matches[1];
        }

        return null;
    }

    /**
     * Scrape les URLs des images d'un chapitre.
     *
     * @return array<int, string>
     */
    public function scrapeChapterPages(ScraperSource $source, string $chapterUrl): array
    {
        $response = $this->getClient($source, $chapterUrl)->get($chapterUrl);
        if (!$response->successful()) {
            throw new \RuntimeException("Impossible de charger la page du chapitre : HTTP " . $response->status());
        }

        $html = $response->body();
        $crawler = new Crawler($html, $chapterUrl);

        $imageNodes = $crawler->filter($source->sel_chapter_pages);
        $imageUrls = [];

        $imageNodes->each(function (Crawler $img) use ($source, &$imageUrls) {
            $src = $img->attr('data-src')
                ?? $img->attr('data-lazy-src')
                ?? $img->attr('data-original')
                ?? $img->attr('src');

            $src = trim($src ?? '');

            if (empty($src) || str_starts_with($src, 'data:image') || str_contains($src, 'spacer.gif')) {
                return;
            }

            if (str_starts_with($src, '//')) {
                $src = 'https:' . $src;
            } elseif (str_starts_with($src, '/')) {
                $src = rtrim($source->base_url, '/') . $src;
            }

            $imageUrls[] = $src;
        });

        return array_values(array_unique($imageUrls));
    }

    /**
     * Télécharge les images d'un chapitre et les enregistre en base.
     * Respecte la règle : sleep(1) entre chaque image.
     */
    public function downloadAndSavePages(Chapter $chapter, array $imageUrls, ScraperSource $source, string $chapterUrl): int
    {
        $manager = new ImageManager(new Driver());
        $destFolder = 'chapters/' . $chapter->id;
        Storage::disk('public')->makeDirectory($destFolder);

        // Supprime les anciennes pages pour éviter les doublons
        $chapter->pages()->delete();

        $savedCount = 0;

        foreach ($imageUrls as $index => $imageUrl) {
            $pageNumber = $index + 1;

            try {
                // Pause obligatoire entre chaque image
                sleep(1);

                $response = $this->getClient($source, $chapterUrl)->get($imageUrl);
                if (!$response->successful()) {
                    Log::warning("Échec téléchargement page {$pageNumber} du chapitre {$chapter->id} ({$imageUrl}) : HTTP " . $response->status());
                    continue;
                }

                $binary = $response->body();
                $saved = false;
                $destPath = null;

                try {
                    $image = $manager->decode($binary);

                    // Redimensionnement si > 1400px de large
                    if ($image->width() > 1400) {
                        $image->scaleDown(width: 1400);
                    }

                    if ($image->width() <= 16380 && $image->height() <= 16380) {
                        $destPath = $destFolder . '/' . $pageNumber . '.webp';
                        $encoded = $image->encode(new WebpEncoder(quality: 82));
                        Storage::disk('public')->put($destPath, (string) $encoded);
                        $saved = true;
                    }
                } catch (\Throwable $e) {
                    Log::warning("Conversion WebP échouée pour la page {$pageNumber} (chapitre {$chapter->id}): " . $e->getMessage());
                }

                // Fallback direct si l'encodage WebP n'a pas pu se faire
                if (!$saved) {
                    $ext = strtolower(pathinfo(parse_url($imageUrl, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION));
                    $ext = in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif']) ? $ext : 'jpg';
                    $destPath = $destFolder . '/' . $pageNumber . '.' . $ext;
                    Storage::disk('public')->put($destPath, $binary);
                }

                ChapterPage::create([
                    'chapter_id'  => $chapter->id,
                    'image_path'  => $destPath,
                    'page_number' => $pageNumber,
                ]);

                $savedCount++;
            } catch (\Throwable $e) {
                Log::error("Erreur lors du traitement de la page {$pageNumber} du chapitre {$chapter->id}: " . $e->getMessage());
            }
        }

        return $savedCount;
    }

    /**
     * Importe un chapitre unique pour un ScraperManga donné.
     */
    public function scrapeSingleChapter(ScraperManga $scraperManga, array $chapterData): ?Chapter
    {
        $source = $scraperManga->scraperSource ?? $scraperManga->source;
        if (!$source) {
            throw new \RuntimeException("Source introuvable.");
        }

        $chapterNumber = (float) $chapterData['number'];
        $slug = 'chapitre-' . str_replace('.', '-', (string) $chapterNumber);

        // Vérifie si le chapitre existe déjà
        $chapter = Chapter::where('manga_id', $scraperManga->manga_id)
            ->where('number', $chapterNumber)
            ->first();

        if (!$chapter) {
            $chapter = Chapter::create([
                'manga_id'     => $scraperManga->manga_id,
                'number'       => $chapterNumber,
                'title'        => $chapterData['title'] ?? ('Chapitre ' . $chapterNumber),
                'slug'         => $slug,
                'status'       => ChapterStatus::PUBLIE,
                'published_at' => now(),
            ]);
        } elseif ($chapter->pages()->exists()) {
            // Déjà téléchargé et présent
            return $chapter;
        }

        // Extraction des pages
        $pageUrls = $this->scrapeChapterPages($source, $chapterData['url']);
        if (empty($pageUrls)) {
            Log::warning("Aucune image trouvée pour le chapitre {$chapterNumber} à {$chapterData['url']}");
            return $chapter;
        }

        // Téléchargement des pages
        $count = $this->downloadAndSavePages($chapter, $pageUrls, $source, $chapterData['url']);

        if ($count > 0) {
            $scraperManga->update([
                'last_chapter_scraped' => (string) $chapterNumber,
                'last_checked_at'      => now(),
                'status'               => 'active',
                'last_error'           => null,
            ]);
        }

        return $chapter;
    }

    /**
     * Synchronise intelligemment les chapitres d'un manga en explorant toutes ses sources configurées,
     * selon leur ordre de priorité (Relais intelligent & Fallback en cas de panne ou de retard).
     *
     * @return array{imported: int, errors: array}
     */
    public function syncMangaMultiSources(\App\Models\Manga $manga): array
    {
        $sources = $manga->scraperMangas()
            ->with('scraperSource')
            ->where('status', '!=', 'paused')
            ->orderBy('priority', 'asc')
            ->get();

        if ($sources->isEmpty()) {
            return ['imported' => 0, 'errors' => []];
        }

        $importedTotal = 0;
        $errors = [];

        // 1. Récupère la liste des chapitres déjà existants avec des pages pour ce manga
        $existingChapters = Chapter::where('manga_id', $manga->id)
            ->whereHas('pages')
            ->pluck('number')
            ->map(fn($n) => (float) $n)
            ->all();

        // 2. Parcourt chaque source par ordre de priorité pour combler les manques
        foreach ($sources as $index => $scraperManga) {
            $sourceModel = $scraperManga->scraperSource ?? $scraperManga->source;
            if (!$sourceModel || !$sourceModel->active) {
                continue;
            }

            $sourceLabel = "Source #" . ($index + 1) . " (« {$sourceModel->name} »)";

            try {
                // Tente de récupérer la liste des chapitres sur cette source
                $chapters = $this->getChapterList($scraperManga);

                // Si succès, met à jour le statut et reset l'erreur éventuelle
                $scraperManga->update([
                    'last_checked_at' => now(),
                    'status'          => 'active',
                    'last_error'      => null,
                ]);

                // Identifie les chapitres disponibles sur cette source mais absents en base
                $missingChapters = array_filter($chapters, function ($ch) use ($existingChapters) {
                    return !in_array((float) $ch['number'], $existingChapters, true);
                });

                if (!empty($missingChapters)) {
                    Log::info("{$sourceLabel} : " . count($missingChapters) . " nouveaux chapitres détectés pour « {$manga->title} ».");

                    foreach ($missingChapters as $chData) {
                        try {
                            $chapter = $this->scrapeSingleChapter($scraperManga, $chData);
                            if ($chapter && $chapter->pages()->exists()) {
                                $existingChapters[] = (float) $chData['number'];
                                $importedTotal++;
                                $scraperManga->update([
                                    'last_chapter_scraped' => (string) $chData['number'],
                                ]);
                                Log::info("{$sourceLabel} : Chapitre {$chData['number']} importé avec succès pour « {$manga->title} ».");
                            }

                            sleep(2); // Pause obligatoire de 2s entre chaque chapitre
                        } catch (\Throwable $e) {
                            Log::warning("{$sourceLabel} : Échec import chapitre {$chData['number']} : " . $e->getMessage());
                        }
                    }
                } else {
                    Log::info("{$sourceLabel} : Aucun nouveau chapitre à ajouter (à jour ou en retard).");
                }

            } catch (\Throwable $e) {
                // Relais intelligent : en cas d'échec sur cette source, on enregistre l'erreur et on passe à la source suivante !
                $scraperManga->update([
                    'last_checked_at' => now(),
                    'status'          => 'error',
                    'last_error'      => $e->getMessage(),
                ]);

                $errors[] = "{$sourceLabel} : " . $e->getMessage();
                Log::warning("Relais de secours activé pour « {$manga->title} » car {$sourceLabel} a échoué : " . $e->getMessage());
            }
        }

        return [
            'imported' => $importedTotal,
            'errors'   => $errors,
        ];
    }
}
