<?php

namespace App\Filament\Resources\Mangas\Pages;

use App\Filament\Resources\Mangas\Actions\ImportMetadataAction;
use App\Filament\Resources\Mangas\MangaResource;
use App\Jobs\ScrapeFullMangaJob;
use App\Models\ScraperManga;
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
            foreach ($data['scraper_sources'] as $index => $sourceItem) {
                if (!empty($sourceItem['scraper_source_id']) && !empty($sourceItem['source_url'])) {
                    ScraperManga::create([
                        'manga_id'          => $this->record->id,
                        'scraper_source_id' => $sourceItem['scraper_source_id'],
                        'source_url'        => $sourceItem['source_url'],
                        'priority'          => $index + 1,
                        'status'            => $sourceItem['status'] ?? 'active',
                    ]);
                    $createdAny = true;
                }
            }

            if ($createdAny) {
                \App\Jobs\ScrapeMultiSourceMangaJob::dispatch($this->record)->onQueue('scraper');
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
