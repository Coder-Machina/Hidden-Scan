<?php

namespace App\Filament\Resources\Mangas\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class MangasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                ImageColumn::make('cover_image')
                    ->label('Cover')
                    ->disk('public')
                    ->circular()
                    ->size(50),
                TextColumn::make('title')->label('Titre')
                    ->label('Titre')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->limit(40),
                TextColumn::make('type')->label('Type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (\App\Enums\MangaType $state): string => match ($state->value) {
                        'manga' => 'info',
                        'manhwa' => 'success',
                        'manhua' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('status')->label('Statut')
                    ->label('Statut')
                    ->badge(),
                TextColumn::make('authors.name')->label('Auteur(s)')
                    ->label('Auteur(s)')
                    ->badge()
                    ->placeholder('—')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('chapters_count')
                    ->label('Chapitres')
                    ->counts('chapters')
                    ->sortable()
                    ->badge()
                    ->color('primary')
                    ->tooltip('Cliquer pour gérer les chapitres de cette œuvre')
                    ->url(fn (\App\Models\Manga $record): string => \App\Filament\Resources\Mangas\MangaResource::getUrl('edit', [
                        'record' => $record,
                        'activeRelationManager' => 0,
                    ])),
                TextColumn::make('views_count')->label('Vues')
                    ->label('Vues')
                    ->numeric()
                    ->sortable()
                    ->color('gray'),
                IconColumn::make('is_featured')
                    ->label('Vedette')
                    ->boolean()
                    ->trueIcon('heroicon-o-star')
                    ->falseIcon('heroicon-o-x-mark')
                    ->trueColor('warning')
                    ->falseColor('gray'),
                TextColumn::make('created_at')->label('Date de création')
                    ->label('Créé le')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Type')
                    ->options([
                        'manga' => 'Manga',
                        'manhwa' => 'Manhwa',
                        'manhua' => 'Manhua',
                    ]),
                SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        'en_cours' => 'En cours',
                        'termine' => 'Terminé',
                        'pause' => 'En pause',
                        'abandonne' => 'Abandonné',
                    ]),
            ])
            ->recordActions([
                \Filament\Actions\Action::make('chapters')
                    ->label(fn (\App\Models\Manga $record) => "Chapitres (" . $record->chapters()->count() . ")")
                    ->icon('heroicon-o-book-open')
                    ->color('success')
                    ->tooltip('Gérer les chapitres de cette œuvre')
                    ->url(fn (\App\Models\Manga $record): string => \App\Filament\Resources\Mangas\MangaResource::getUrl('edit', [
                        'record' => $record,
                        'activeRelationManager' => 0,
                    ])),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
