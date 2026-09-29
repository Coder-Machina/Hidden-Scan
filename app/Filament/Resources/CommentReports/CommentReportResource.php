<?php

namespace App\Filament\Resources\CommentReports;

use App\Filament\Resources\CommentReports\Pages\ManageCommentReports;
use App\Models\CommentReport;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CommentReportResource extends Resource
{
    protected static ?string $model = CommentReport::class;

    protected static ?string $modelLabel = 'signalement';

    protected static ?string $pluralModelLabel = 'signalements';

    protected static ?string $navigationLabel = 'Signalements';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFlag;
    protected static string|\UnitEnum|null $navigationGroup = null;
    protected static ?int $navigationSort = 40;

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasAnyRole(['admin', 'Admin', 'owner', 'Owner', 'modo', 'Modo', 'moderateur', 'Modérateur']) ?? false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('comment.content')
                    ->label('Commentaire signalé')
                    ->limit(60)
                    ->searchable(),
                \Filament\Tables\Columns\TextColumn::make('comment.pseudo')
                    ->label('Auteur du commentaire')
                    ->placeholder('Anonyme'),
                \Filament\Tables\Columns\TextColumn::make('reason')
                    ->label('Motif du signalement')
                    ->searchable(),
                \Filament\Tables\Columns\IconColumn::make('is_resolved')
                    ->label('Résolu')
                    ->boolean(),
                \Filament\Tables\Columns\TextColumn::make('created_at')
                    ->label('Signalé le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->emptyStateHeading('Aucun signalement')
            ->emptyStateDescription('Tous les signalements de commentaires apparaîtront ici.')
            ->filters([
                \Filament\Tables\Filters\TernaryFilter::make('is_resolved')
                    ->label('Statut de résolution')
                    ->trueLabel('Signalements résolus')
                    ->falseLabel('En attente de traitement')
                    ->placeholder('Tous les signalements'),
            ])
            ->recordActions([
                \Filament\Actions\Action::make('resolve')
                    ->label('Marquer résolu')
                    ->icon('heroicon-m-check')
                    ->color('success')
                    ->visible(fn (CommentReport $record) => !$record->is_resolved)
                    ->action(function (CommentReport $record) {
                        $record->update([
                            'is_resolved' => true,
                            'resolved_by' => auth()->id(),
                        ]);
                        \Filament\Notifications\Notification::make()
                            ->title('Signalement marqué comme résolu')
                            ->success()
                            ->send();
                    }),
                DeleteAction::make()->label('Supprimer'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->label('Supprimer la sélection'),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageCommentReports::route('/'),
        ];
    }
}
