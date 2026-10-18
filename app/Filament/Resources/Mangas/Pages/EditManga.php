<?php

namespace App\Filament\Resources\Mangas\Pages;

use App\Filament\Resources\Mangas\Actions\ImportMetadataAction;
use App\Filament\Resources\Mangas\MangaResource;
use App\Jobs\ScrapeMultiSourceMangaJob;
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
                ->label('↻ Vérifier sources (Relais intelligent)')
                ->color('info')
                ->icon('heroicon-o-arrow-path')
                ->visible(fn () => $this->record->scraperMangas()->exists())
                ->action(function () {
                    ScrapeMultiSourceMangaJob::dispatch($this->record)->onQueue('scraper');
                    Notification::make()
                        ->title('Relais intelligent déclenché')
                        ->body('La vérification de toutes les sources configurées par ordre de priorité s\'exécute en arrière-plan.')
                        ->success()
                        ->send();
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
        $sources = $this->record->scraperMangas()->orderBy('priority', 'asc')->get();

        if ($sources->isNotEmpty()) {
            $data['scraper_sources'] = $sources->map(fn($sm) => [
                'scraper_source_id' => $sm->scraper_source_id,
                'source_url'        => $sm->source_url,
                'status'            => $sm->status,
            ])->toArray();

            // Rétrocompatibilité avec les champs uniques
            $first = $sources->first();
            $data['scraper_source_id'] = $first->scraper_source_id;
            $data['scraper_source_url'] = $first->source_url;
        }

        return $data;
    }

    protected function afterSave(): void
    {
        $data = $this->form->getRawState();

        // 1. Gestion via le Repeater Multi-Sources
        if (isset($data['scraper_sources']) && is_array($data['scraper_sources'])) {
            $keptIds = [];

            foreach ($data['scraper_sources'] as $index => $item) {
                if (!empty($item['scraper_source_id']) && !empty($item['source_url'])) {
                    $sm = ScraperManga::updateOrCreate(
                        [
                            'manga_id'          => $this->record->id,
                            'scraper_source_id' => $item['scraper_source_id'],
                        ],
                        [
                            'source_url' => $item['source_url'],
                            'priority'   => $index + 1,
                            'status'     => $item['status'] ?? 'active',
                        ]
                    );
                    $keptIds[] = $sm->id;
                }
            }

            // Supprime les sources qui ont été retirées du repeater
            $this->record->scraperMangas()->whereNotIn('id', $keptIds)->delete();

            if (!empty($keptIds)) {
                ScrapeMultiSourceMangaJob::dispatch($this->record)->onQueue('scraper');
            }
        } elseif (!empty($data['scraper_source_id']) && !empty($data['scraper_source_url'])) {
            // Rétrocompatibilité avec les formulaires simples
            $scraperManga = ScraperManga::updateOrCreate(
                ['manga_id' => $this->record->id],
                [
                    'scraper_source_id' => $data['scraper_source_id'],
                    'source_url'        => $data['scraper_source_url'],
                    'priority'          => 1,
                    'status'            => 'active',
                ]
            );

            ScrapeNewChaptersJob::dispatch($scraperManga)->onQueue('scraper');
        } elseif (array_key_exists('scraper_source_id', $data) && empty($data['scraper_source_id'])) {
            $this->record->scraperMangas()->delete();
        }
    }
}
