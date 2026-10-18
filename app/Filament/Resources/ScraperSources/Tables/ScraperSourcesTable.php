<?php

namespace App\Filament\Resources\ScraperSources\Tables;

use App\Models\ScraperManga;
use App\Models\ScraperSource;
use App\Services\ScraperService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ScraperSourcesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nom')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('base_url')
                    ->label('URL racine')
                    ->searchable()
                    ->url(fn (ScraperSource $record) => $record->base_url, shouldOpenInNewTab: true)
                    ->color('primary')
                    ->copyable(),
                TextColumn::make('scraper_mangas_count')
                    ->counts('scraperMangas')
                    ->label('Mangas liés')
                    ->badge()
                    ->color('info')
                    ->sortable(),
                IconColumn::make('active')
                    ->label('Actif')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label('Dernière modification')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->emptyStateHeading('Aucune source configurée')
            ->emptyStateDescription('Ajoutez votre première source externe pour automatiser l\'import de chapitres.')
            ->recordActions([
                Action::make('test_source')
                    ->label('Tester')
                    ->icon('heroicon-o-beaker')
                    ->color('warning')
                    ->modalHeading(fn (ScraperSource $record) => "Tester les sélecteurs de « {$record->name} »")
                    ->modalDescription('Saisissez l\'URL d\'un manga sur ce site pour vérifier en direct si les sélecteurs parviennent à extraire les chapitres.')
                    ->modalSubmitActionLabel('Lancer le test')
                    ->form([
                        TextInput::make('test_url')
                            ->label('URL du manga à tester')
                            ->placeholder('https://poseidon-scan.net/manga/solo-leveling')
                            ->url()
                            ->required(),
                    ])
                    ->action(function (ScraperSource $record, array $data) {
                        try {
                            $dummy = new ScraperManga([
                                'scraper_source_id' => $record->id,
                                'source_url'        => $data['test_url'],
                            ]);
                            $dummy->setRelation('scraperSource', $record);

                            $service = app(ScraperService::class);
                            $chapters = $service->getChapterList($dummy);

                            $count = count($chapters);
                            if ($count === 0) {
                                Notification::make()
                                    ->title('Aucun chapitre détecté')
                                    ->body("La page a répondu mais aucun élément ne correspond au sélecteur '{$record->sel_chapter_list}'.")
                                    ->warning()
                                    ->send();
                                return;
                            }

                            $first = $chapters[0];
                            $last = end($chapters);

                            Notification::make()
                                ->title("Test réussi ! ({$count} chapitres trouvés)")
                                ->body("Du chap. {$first['number']} (« {$first['title']} ») au chap. {$last['number']}.")
                                ->success()
                                ->send();
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->title('Erreur lors du test')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
