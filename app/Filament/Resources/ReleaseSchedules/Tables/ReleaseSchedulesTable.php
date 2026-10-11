<?php

namespace App\Filament\Resources\ReleaseSchedules\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ReleaseSchedulesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('day_of_week')
            ->columns([
                ImageColumn::make('manga.cover_image')
                    ->label('Cover')
                    ->disk('public')
                    ->circular()
                    ->size(40),

                TextColumn::make('manga.title')
                    ->label('Série')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('day_of_week')
                    ->label('Jour')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->color(fn (string $state): string => match ($state) {
                        'lundi' => 'info',
                        'mardi' => 'primary',
                        'mercredi' => 'warning',
                        'jeudi' => 'success',
                        'vendredi' => 'danger',
                        'samedi' => 'gray',
                        'dimanche' => 'info',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('release_time')
                    ->label('Heure')
                    ->badge()
                    ->color('gray')
                    ->sortable(),

                TextColumn::make('status_label')
                    ->label('Statut')
                    ->badge()
                    ->color(fn (string $state): string => match (strtolower($state)) {
                        'officiel hs' => 'danger',
                        'prioritaire' => 'warning',
                        'spécial', 'special' => 'success',
                        default => 'gray',
                    }),

                IconColumn::make('is_priority')
                    ->label('Prioritaire')
                    ->boolean(),

                IconColumn::make('is_active')
                    ->label('Actif')
                    ->boolean(),

                TextColumn::make('sort_order')
                    ->label('Ordre')
                    ->sortable(),
            ])
            ->emptyStateHeading('Aucune sortie programmée')
            ->emptyStateDescription('Ajoutez les séries et leurs jours de sortie hebdomadaire.')
            ->filters([
                SelectFilter::make('day_of_week')
                    ->label('Filtrer par jour')
                    ->options([
                        'lundi' => 'Lundi',
                        'mardi' => 'Mardi',
                        'mercredi' => 'Mercredi',
                        'jeudi' => 'Jeudi',
                        'vendredi' => 'Vendredi',
                        'samedi' => 'Samedi',
                        'dimanche' => 'Dimanche',
                    ]),
            ])
            ->recordActions([
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
