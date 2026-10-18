<?php

namespace App\Filament\Resources\Mangas\Pages;

use App\Filament\Resources\Mangas\Actions\ImportMetadataAction;
use App\Filament\Resources\Mangas\MangaResource;
use App\Jobs\ScrapeNewChaptersJob;
use App\Models\ScraperManga;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditManga extends EditRecord
{
    protected static string $resource = MangaResource::class;

    public function hasCombinedRelationManagerTabsWithContent(): bool
    {
        return true;
    }

    public function getContentTabLabel(): ?string
    {
        return 'Informations de l\'œuvre';
    }

    public function getContentTabIcon(): ?string
    {
        return 'heroicon-o-information-circle';
    }

    protected function getHeaderActions(): array
    {
        return [
            ImportMetadataAction::make(),
            Action::make('sync_scraper')
                ->label('↻ Vérifier chapitres externes')
                ->color('info')
                ->icon('heroicon-o-arrow-path')
                ->visible(fn () => (bool) $this->record->scraperManga)
                ->action(function () {
                    if ($this->record->scraperManga) {
                        ScrapeNewChaptersJob::dispatch($this->record->scraperManga)->onQueue('scraper');
                        Notification::make()
                            ->title('Vérification lancée')
                            ->body('La recherche de nouveaux chapitres tourne en arrière-plan.')
                            ->success()
                            ->send();
                    }
                }),
            DeleteAction::make(),
        ];
    }

    public function fillForm(): void
    {
        parent::fillForm();
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        if ($this->record->scraperManga) {
            $data['scraper_source_id'] = $this->record->scraperManga->scraper_source_id;
            $data['scraper_source_url'] = $this->record->scraperManga->source_url;
        }

        return $data;
    }

    protected function afterSave(): void
    {
        $data = $this->form->getRawState();

        if (!empty($data['scraper_source_id']) && !empty($data['scraper_source_url'])) {
            $scraperManga = ScraperManga::updateOrCreate(
                ['manga_id' => $this->record->id],
                [
                    'scraper_source_id' => $data['scraper_source_id'],
                    'source_url'        => $data['scraper_source_url'],
                    'status'            => 'active',
                ]
            );

            // Lancer une vérification immédiate
            ScrapeNewChaptersJob::dispatch($scraperManga)->onQueue('scraper');
        } elseif (array_key_exists('scraper_source_id', $data) && empty($data['scraper_source_id'])) {
            $this->record->scraperManga?->delete();
        }
    }
}
