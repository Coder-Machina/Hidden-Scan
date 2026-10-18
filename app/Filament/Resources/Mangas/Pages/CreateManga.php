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

        if (!empty($data['scraper_source_id']) && !empty($data['scraper_source_url'])) {
            $scraperManga = ScraperManga::create([
                'manga_id'          => $this->record->id,
                'scraper_source_id' => $data['scraper_source_id'],
                'source_url'        => $data['scraper_source_url'],
                'status'            => 'active',
            ]);

            // Lancer l'import initial en arrière-plan
            ScrapeFullMangaJob::dispatch($scraperManga)->onQueue('scraper');
        }
    }
}
