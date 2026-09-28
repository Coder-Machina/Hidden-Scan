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
    protected static string|\UnitEnum|null $navigationGroup = 'Modération';

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
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('comment.content')->label('Commentaire')
                    ->label('Commentaire')
                    ->limit(50)
                    ->searchable(),
                \Filament\Tables\Columns\TextColumn::make('reason')->label('Raison')
                    ->label('Raison')
                    ->searchable(),
                \Filament\Tables\Columns\IconColumn::make('is_resolved')
                    ->label('Résolu')
                    ->boolean(),
                \Filament\Tables\Columns\TextColumn::make('created_at')->label('Date de création')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                \Filament\Tables\Filters\TernaryFilter::make('is_resolved')
                    ->label('État de résolution')
            ])
            ->recordActions([
                \Filament\Actions\Action::make('resolve')
                    ->label('Marquer comme résolu')
                    ->icon('heroicon-m-check')
                    ->color('success')
                    ->visible(fn (CommentReport $record) => !$record->is_resolved)
                    ->action(fn (CommentReport $record) => $record->update([
                        'is_resolved' => true,
                        'resolved_by' => auth()->id()
                    ])),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
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
