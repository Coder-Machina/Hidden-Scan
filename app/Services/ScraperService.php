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

        // 1. Stratégie Next.js / RSC payload (ex: Poseidon Scans, Next.js manga sites)
        $chapters = $this->extractFromNextJs($html, $scraperManga->source_url);
        if (!empty($chapters)) {
            usort($chapters, fn($a, $b) => $a['number'] <=> $b['number']);
            return $chapters;
        }

        // 2. Stratégie JSON-LD (Schema.org ComicSeries / ComicIssue)
        $chapters = $this->extractFromJsonLd($html, $scraperManga->source_url);
        if (!empty($chapters)) {
            usort($chapters, fn($a, $b) => $a['number'] <=> $b['number']);
            return $chapters;
        }

        // 3. Stratégie DOM Crawler avec sélecteur configuré et fallbacks intelligents
        $crawler = new Crawler($html, $scraperManga->source_url);
        $candidateSelectors = array_unique(array_filter([
            $source->sel_chapter_list,
            'a[href*="/chapter/"]',
            'a[href*="/chapitre/"]',
            '#chapterlist li',
            '.cl-item',
            '.eph-num',
            '.chapter-list-item',
            'ul.sub-chap li',
            'div[data-number]',
            '.wp-manga-chapter',
        ]));

        $chapterNodes = null;
        $matchedSelector = null;
        foreach ($candidateSelectors as $sel) {
            try {
                $nodes = $crawler->filter($sel);
                if ($nodes->count() > 0) {
                    $chapterNodes = $nodes;
                    $matchedSelector = $sel;
                    break;
                }
            } catch (\Throwable $e) {
                // ignorer les sélecteurs invalides
            }
        }

        if (!$chapterNodes || $chapterNodes->count() === 0) {
            return [];
        }

        $chapterNodes->each(function (Crawler $node) use ($source, $scraperManga, &$chapters) {
            try {
                // Extraction de l'URL et du titre
                if ($node->nodeName() === 'a') {
                    $title = trim($node->text());
                    $url = trim($node->attr('href') ?? '');
                } else {
                    $titleNode = $source->sel_chapter_title ? $node->filter($source->sel_chapter_title) : null;
                    if (!$titleNode || $titleNode->count() === 0) {
                        $titleNode = $node->filter('a');
                    }
                    $urlNode = $source->sel_chapter_url ? $node->filter($source->sel_chapter_url) : null;
                    if (!$urlNode || $urlNode->count() === 0) {
                        $urlNode = $node->filter('a');
                    }

                    $title = $titleNode->count() > 0 ? trim($titleNode->text()) : trim($node->text());
                    $url = $urlNode->count() > 0 ? trim($urlNode->attr('href') ?? '') : '';
                }

                if (empty($url)) {
                    return;
                }

                // Normalisation URL si relative
                if (str_starts_with($url, '/')) {
                    $parsedHost = parse_url($scraperManga->source_url, PHP_URL_HOST);
                    $parsedScheme = parse_url($scraperManga->source_url, PHP_URL_SCHEME) ?? 'https';
                    $baseUrl = $parsedHost ? ($parsedScheme . '://' . $parsedHost) : $source->base_url;
                    $url = rtrim($baseUrl, '/') . $url;
                }

                // Date optionnelle
                $date = null;
                if ($source->sel_chapter_date) {
                    try {
                        $dateNode = $node->filter($source->sel_chapter_date);
                        if ($dateNode->count() > 0) {
                            $date = trim($dateNode->text());
                        }
                    } catch (\Throwable $e) {
                        // ignore
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

        // Déduplication par numéro
        $unique = [];
        foreach ($chapters as $ch) {
            $numKey = (string)$ch['number'];
            if (!isset($unique[$numKey])) {
                $unique[$numKey] = $ch;
            }
        }
        $chapters = array_values($unique);

        // Tri par numéro de chapitre croissant (1, 2, 3...)
        usort($chapters, fn($a, $b) => $a['number'] <=> $b['number']);

        return $chapters;
    }

    /**
     * Tente d'extraire la liste complète des chapitres depuis un payload Next.js RSC.
     */
    protected function extractFromNextJs(string $html, string $sourceUrl): array
    {
        if (preg_match('/\\\\?"chapters\\\\?":\s*(\[\s*\{.*?\}\s*\])(?:,\s*\\\\?"|\}\]\]|\}\s*\}|\}\s*\])/s', $html, $matches)
            || preg_match('/\\\\?"chapters\\\\?":\s*(\[.*?\])(?:,\s*\\\\?"|\}\]\])/s', $html, $matches)) {
            $rawJson = stripslashes($matches[1]);
            $cleanJson = preg_replace('/"\$D([^"]+)"/', '"$1"', $rawJson);
            $items = json_decode($cleanJson, true);
            if (is_array($items) && !empty($items)) {
                $baseChapterUrl = rtrim($sourceUrl, '/');
                $chapters = [];
                foreach ($items as $item) {
                    if (!isset($item['number'])) {
                        continue;
                    }
                    $num = (float)$item['number'];
                    $title = !empty($item['title']) ? "Chapitre {$num} - {$item['title']}" : "Chapitre {$num}";
                    $url = $baseChapterUrl . '/chapter/' . $num;
                    $chapters[] = [
                        'number' => $num,
                        'title'  => $title,
                        'url'    => $url,
                        'date'   => $item['createdAt'] ?? null,
                    ];
                }
                return $chapters;
            }
        }

        return [];
    }

    /**
     * Tente d'extraire la liste des chapitres depuis le schema JSON-LD.
     */
    protected function extractFromJsonLd(string $html, string $sourceUrl): array
    {
        if (preg_match_all('/<script[^>]+type="application\/ld\+json"[^>]*>(.*?)<\/script>/s', $html, $scripts)) {
            foreach ($scripts[1] as $content) {
                $data = json_decode($content, true);
                if (is_array($data) && isset($data['hasPart']) && is_array($data['hasPart'])) {
                    $chapters = [];
                    foreach ($data['hasPart'] as $part) {
                        $num = isset($part['issueNumber']) ? (float)$part['issueNumber'] : null;
                        $url = $part['url'] ?? '';
                        $name = $part['name'] ?? '';
                        if ($num === null && !empty($url)) {
                            $num = $this->extractChapterNumber($name, $url);
                        }
                        if ($num !== null && !empty($url)) {
                            $chapters[] = [
                                'number' => $num,
                                'title'  => $name ?: ('Chapitre ' . $num),
                                'url'    => $url,
                                'date'   => null,
                            ];
                        }
                    }
                    if (!empty($chapters)) {
                        return $chapters;
                    }
                }
            }
        }

        return [];
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

        $candidateSelectors = array_unique(array_filter([
            $source->sel_chapter_pages,
            'img.manga-page-img',
            '.manga-page-img',
            'img[src*="/api/chapters/"]',
            '.page-break img',
            'div.reading-content img',
            '#readerarea img',
            '.chapter-image img',
            'div[id*="reader"] img',
            'img[alt*="Chapter Image"]',
            'img[alt*="Page"]',
        ]));

        $imageNodes = null;
        foreach ($candidateSelectors as $sel) {
            try {
                $nodes = $crawler->filter($sel);
                if ($nodes->count() > 0) {
                    $imageNodes = $nodes;
                    break;
                }
            } catch (\Throwable $e) {
                // ignore
            }
        }

        if (!$imageNodes || $imageNodes->count() === 0) {
            return [];
        }

        $imageUrls = [];
        $parsedHost = parse_url($chapterUrl, PHP_URL_HOST);
        $parsedScheme = parse_url($chapterUrl, PHP_URL_SCHEME) ?? 'https';
        $baseUrl = $parsedHost ? ($parsedScheme . '://' . $parsedHost) : $source->base_url;

        $imageNodes->each(function (Crawler $img) use ($baseUrl, &$imageUrls) {
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
                $src = rtrim($baseUrl, '/') . $src;
            }

            $imageUrls[] = $src;
        });

        return array_values(array_unique($imageUrls));
    }

    /**
     * Télécharge les images d'un chapitre et les enregistre en base.
     * Respecte la règle : sleep(1) entre chaque image.
     * S'arrête proprement après avoir fini l'image en cours si annulation demandée.
     */
    public function downloadAndSavePages(Chapter $chapter, array $imageUrls, ScraperSource $source, string $chapterUrl, ?ScraperManga $scraperManga = null): int
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

                // Vérifie si une annulation est demandée après avoir terminé l'image en cours
                if ($scraperManga) {
                    $scraperManga->refresh();
                    if ($scraperManga->cancel_requested) {
                        Log::info("Annulation demandée : arrêt propre après la page {$pageNumber} pour le chapitre {$chapter->id}.");
                        break;
                    }
                }
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

        $expectedCount = count($pageUrls);

        // Téléchargement des pages avec gestion d'annulation propre
        $count = $this->downloadAndSavePages($chapter, $pageUrls, $source, $chapterData['url'], $scraperManga);

        // Si une annulation a été demandée pendant le téléchargement des pages
        $scraperManga->refresh();
        if ($scraperManga->cancel_requested) {
            if ($count < $expectedCount) {
                // Incomplet : suppression propre
                $chapter->pages()->delete();
                Storage::disk('public')->deleteDirectory('chapters/' . $chapter->id);
                $chapter->delete();
                Log::info("Chapitre {$chapterNumber} supprimé car incomplet lors de l'annulation.");
                $chapter = null;
            } else {
                // Complet : conservé
                $scraperManga->update([
                    'last_chapter_scraped' => (string) $chapterNumber,
                ]);
            }

            $scraperManga->update([
                'status'                   => 'annulé',
                'cancel_requested'         => false,
                'current_scraping_chapter' => null,
            ]);

            return $chapter;
        }

        if ($count > 0) {
            $scraperManga->update([
                'last_chapter_scraped' => (string) $chapterNumber,
                'last_checked_at'      => now(),
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
            ->whereNotIn('status', ['paused', 'en pause'])
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
                $scraperManga->refresh();
                if ($scraperManga->cancel_requested) {
                    $scraperManga->update([
                        'status'                   => 'annulé',
                        'cancel_requested'         => false,
                        'current_scraping_chapter' => null,
                    ]);
                    return ['imported' => $importedTotal, 'errors' => $errors, 'cancelled' => true];
                }

                // Tente de récupérer la liste des chapitres sur cette source
                $chapters = $this->getChapterList($scraperManga);
                $totalDetected = count($chapters);

                // Met à jour le total détecté
                $scraperManga->update([
                    'total_chapters_detected' => $totalDetected,
                    'last_checked_at'         => now(),
                    'last_error'              => null,
                ]);

                // Identifie les chapitres disponibles sur cette source mais absents en base
                $missingChapters = array_filter($chapters, function ($ch) use ($existingChapters) {
                    return !in_array((float) $ch['number'], $existingChapters, true);
                });

                if (!empty($missingChapters)) {
                    $scraperManga->update(['status' => 'importing']);
                    Log::info("{$sourceLabel} : " . count($missingChapters) . " nouveaux chapitres détectés pour « {$manga->title} ».");

                    foreach ($missingChapters as $chData) {
                        // Vérifie si une annulation a été demandée au début du chapitre
                        $scraperManga->refresh();
                        if ($scraperManga->cancel_requested) {
                            $scraperManga->update([
                                'status'                   => 'annulé',
                                'cancel_requested'         => false,
                                'current_scraping_chapter' => null,
                            ]);
                            Log::info("{$sourceLabel} : Arrêt propre suite à annulation avant chapitre {$chData['number']}.");
                            return ['imported' => $importedTotal, 'errors' => $errors, 'cancelled' => true];
                        }

                        $scraperManga->update([
                            'status'                   => 'importing',
                            'current_scraping_chapter' => (string) $chData['number'],
                        ]);

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

                            // Vérifie à nouveau après le chapitre
                            $scraperManga->refresh();
                            if ($scraperManga->cancel_requested) {
                                $scraperManga->update([
                                    'status'                   => 'annulé',
                                    'cancel_requested'         => false,
                                    'current_scraping_chapter' => null,
                                ]);
                                return ['imported' => $importedTotal, 'errors' => $errors, 'cancelled' => true];
                            }

                            sleep(2); // Pause obligatoire de 2s entre chaque chapitre
                        } catch (\Throwable $e) {
                            Log::warning("{$sourceLabel} : Échec import chapitre {$chData['number']} : " . $e->getMessage());
                        }
                    }
                } else {
                    Log::info("{$sourceLabel} : Aucun nouveau chapitre à ajouter (à jour ou en retard).");
                }

                // Fin sans annulation : statut actif
                $scraperManga->refresh();
                if (!$scraperManga->cancel_requested && $scraperManga->status === 'importing') {
                    $scraperManga->update([
                        'status'                   => 'active',
                        'current_scraping_chapter' => null,
                        'cancel_requested'         => false,
                        'last_checked_at'          => now(),
                    ]);
                }

            } catch (\Throwable $e) {
                // Relais intelligent : en cas d'échec sur cette source, on enregistre l'erreur et on passe à la source suivante !
                $scraperManga->update([
                    'last_checked_at'          => now(),
                    'status'                   => 'error',
                    'last_error'               => $e->getMessage(),
                    'current_scraping_chapter' => null,
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
