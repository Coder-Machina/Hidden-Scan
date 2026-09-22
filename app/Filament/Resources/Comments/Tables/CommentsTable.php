<?php

namespace App\Filament\Resources\Comments\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class CommentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('pseudo')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('content')
                    ->limit(80)
                    ->searchable(),
                TextColumn::make('commentable_type')
                    ->label('Type')
                    ->formatStateUsing(fn($state) => str_contains($state, 'Chapter') ? 'Chapitre' : 'Manga')
                    ->badge(),
                IconColumn::make('is_hidden')
                    ->label('Masqué')
                    ->boolean(),
                IconColumn::make('is_pinned')
                    ->label('Épinglé')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                TernaryFilter::make('is_hidden')->label('Masqué'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}