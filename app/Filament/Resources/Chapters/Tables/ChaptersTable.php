<?php

namespace App\Filament\Resources\Chapters\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ChaptersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('manga_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('number')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('title')
                    ->searchable(),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('uploaded_by')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('scheduled_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('published_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('views_count')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                \Filament\Tables\Actions\Action::make('publish')
                    ->label('Publier')
                    ->icon('heroicon-m-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Chapter $record): bool => $record->status !== \App\Enums\ChapterStatus::PUBLIE)
                    ->action(function (Chapter $record) {
                        $record->update([
                            'status' => \App\Enums\ChapterStatus::PUBLIE,
                            'published_at' => now(),
                        ]);
                        // Action Logging here if needed
                    }),
                \Filament\Tables\Actions\Action::make('control')
                    ->label('Contrôler')
                    ->icon('heroicon-m-magnifying-glass')
                    ->color('warning')
                    ->visible(fn (Chapter $record): bool => $record->status === \App\Enums\ChapterStatus::BROUILLON)
                    ->action(fn (Chapter $record) => $record->update(['status' => \App\Enums\ChapterStatus::CONTROLE])),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
