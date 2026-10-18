<?php

namespace App\Filament\Resources\Mangas\Pages;

use App\Filament\Resources\Mangas\Actions\ImportMetadataAction;
use App\Filament\Resources\Mangas\MangaResource;
use App\Jobs\ScrapeMultiSourceMangaJob;
use App\Jobs\ScrapeNewChaptersJob;
use App\Models\ScraperManga;
use App\Models\ScraperSource;
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
            $priority = 1;

            foreach ($data['scraper_sources'] as $item) {
                $sourceUrl = trim($item['source_url'] ?? '');
                if (empty($sourceUrl)) {
                    continue;
                }

                $sourceId = $item['scraper_source_id'] ?? null;

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
                                'name'              => $host,
                                'sel_chapter_list'  => '.wp-manga-chapter',
                                'sel_chapter_title' => '.wp-manga-chapter a',
                                'sel_chapter_url'   => '.wp-manga-chapter a',
                                'sel_chapter_date'  => '.chapter-release-date',
                                'sel_chapter_pages' => '.page-break img',
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
                    $sm = ScraperManga::updateOrCreate(
                        [
                            'manga_id'          => $this->record->id,
                            'scraper_source_id' => $sourceId,
                        ],
                        [
                            'source_url' => $sourceUrl,
                            'priority'   => $priority++,
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
