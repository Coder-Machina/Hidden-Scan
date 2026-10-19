<?php

namespace App\Filament\Resources\Mangas\Pages;

use App\Filament\Resources\Mangas\Actions\ImportMetadataAction;
use App\Filament\Resources\Mangas\MangaResource;
use App\Jobs\ScrapeFullMangaJob;
use App\Jobs\ScrapeMultiSourceMangaJob;
use App\Models\ScraperManga;
use App\Models\ScraperSource;
use Filament\Resources\Pages\CreateRecord;

class CreateManga extends CreateRecord
{
    protected static string $resource = MangaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ImportMetadataAction::make(),
        ];
    }

    protected function afterCreate(): void
    {
        $data = $this->form->getRawState();

        if (!empty($data['scraper_sources']) && is_array($data['scraper_sources'])) {
            $createdAny = false;
            $priority = 1;

            foreach ($data['scraper_sources'] as $sourceItem) {
                $sourceUrl = trim($sourceItem['source_url'] ?? '');
                if (empty($sourceUrl)) {
                    continue;
                }

                $sourceId = $sourceItem['scraper_source_id'] ?? null;

                // Auto-résolution ou création de la source si l'utilisateur a juste collé l'URL
                if (empty($sourceId)) {
                    $parsed = parse_url($sourceUrl);
                    $host = $parsed['host'] ?? '';
                    $scheme = $parsed['scheme'] ?? 'https';
                    if ($host) {
                        $baseUrl = "{$scheme}://{$host}";
                        $source = ScraperSource::firstOrCreate(
                            ['base_url' => $baseUrl],
                            [
                                'name'              => trim($sourceItem['name'] ?? '') ?: ($host ?: 'Source'),
                                'sel_chapter_list'  => null,
                                'sel_chapter_title' => null,
                                'sel_chapter_url'   => null,
                                'sel_chapter_date'  => null,
                                'sel_chapter_pages' => null,
                                'headers'           => [
                                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                                ],
                                'active'            => true,
                            ]
                        );
                        $sourceId = $source->id;
                    }
                }

                if ($sourceId) {
                    ScraperManga::create([
                        'manga_id'          => $this->record->id,
                        'scraper_source_id' => $sourceId,
                        'source_url'        => $sourceUrl,
                        'priority'          => $priority++,
                        'status'            => $sourceItem['status'] ?? 'active',
                    ]);
                    $createdAny = true;
                }
            }

            if ($createdAny) {
                ScrapeMultiSourceMangaJob::dispatch($this->record)->onQueue('scraper');
            }
        } elseif (!empty($data['scraper_source_id']) && !empty($data['scraper_source_url'])) {
            $scraperManga = ScraperManga::create([
                'manga_id'          => $this->record->id,
                'scraper_source_id' => $data['scraper_source_id'],
                'source_url'        => $data['scraper_source_url'],
                'priority'          => 1,
                'status'            => 'active',
            ]);

            ScrapeFullMangaJob::dispatch($scraperManga)->onQueue('scraper');
        }
    }
}
