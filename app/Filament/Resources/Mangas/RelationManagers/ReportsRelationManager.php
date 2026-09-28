<?php

namespace App\Filament\Resources\Mangas\RelationManagers;

use App\Models\ChapterReport;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class ReportsRelationManager extends RelationManager
{
    protected static string $relationship = 'reports';

    protected static ?string $title = 'Signalements';

    protected static ?string $modelLabel = 'signalement';

    protected static ?string $pluralModelLabel = 'signalements';

    protected static string | BackedEnum | null $icon = 'heroicon-o-flag';

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        $count = $ownerRecord->reports()->pending()->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getBadgeColor(Model $ownerRecord, string $pageClass): ?string
    {
        return 'danger';
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('chapter.number')
                    ->label('Chapitre')
                    ->badge()
                    ->color('primary')
                    ->sortable(),
                TextColumn::make('page_number')
                    ->label('Page')
                    ->badge()
                    ->color('gray')
                    ->placeholder('Globale'),
                TextColumn::make('type')
                    ->label('Type d\'erreur')
                    ->badge()
                    ->formatStateUsing(fn (ChapterReport $record) => $record->type_label)
                    ->color(fn (ChapterReport $record) => $record->type_color),
                TextColumn::make('message')
                    ->label('Précisions du lecteur')
                    ->limit(45)
                    ->tooltip(fn (ChapterReport $record) => $record->message)
                    ->placeholder('—'),
                TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->formatStateUsing(fn (ChapterReport $record) => $record->status_label)
                    ->color(fn (ChapterReport $record) => $record->status_color),
                TextColumn::make('user.name')
                    ->label('Signalé par')
                    ->placeholder('Visiteur anonyme')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('Date')
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        'en_attente' => 'En attente',
                        'resolu' => 'Résolu',
                        'rejete' => 'Rejeté',
                    ])
                    ->default('en_attente'),
            ])
            ->recordActions([
                Action::make('resolve')
                    ->label('Résoudre')
                    ->icon('heroicon-m-check-circle')
                    ->color('success')
                    ->visible(fn (ChapterReport $record): bool => $record->status === 'en_attente')
                    ->action(function (ChapterReport $record) {
                        $record->update(['status' => 'resolu']);
                        Notification::make()
                            ->title('Signalement résolu')
                            ->success()
                            ->send();
                    }),
                Action::make('reject')
                    ->label('Rejeter')
                    ->icon('heroicon-m-x-circle')
                    ->color('gray')
                    ->visible(fn (ChapterReport $record): bool => $record->status === 'en_attente')
                    ->action(function (ChapterReport $record) {
                        $record->update(['status' => 'rejete']);
                        Notification::make()
                            ->title('Signalement classé sans suite')
                            ->send();
                    }),
                Action::make('view_reader')
                    ->label('Voir le chapitre')
                    ->icon('heroicon-m-arrow-top-right-on-square')
                    ->color('info')
                    ->url(fn (ChapterReport $record): ?string => $record->chapter && $record->manga
                        ? route('chapter.show', [$record->manga->slug, $record->chapter->slug])
                        : null)
                    ->openUrlInNewTab(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('bulk_resolve')
                        ->label('Marquer comme résolus')
                        ->icon('heroicon-o-check')
                        ->color('success')
                        ->action(fn (Collection $records) => $records->each->update(['status' => 'resolu'])),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
