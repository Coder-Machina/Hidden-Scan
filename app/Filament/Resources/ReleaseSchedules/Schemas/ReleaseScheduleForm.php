<?php

namespace App\Filament\Resources\ReleaseSchedules\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ReleaseScheduleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('day_of_week')
                    ->label('Jour de sortie')
                    ->options([
                        'lundi' => 'Lundi',
                        'mardi' => 'Mardi',
                        'mercredi' => 'Mercredi',
                        'jeudi' => 'Jeudi',
                        'vendredi' => 'Vendredi',
                        'samedi' => 'Samedi',
                        'dimanche' => 'Dimanche',
                    ])
                    ->required(),

                Select::make('manga_id')
                    ->label('Série / Webtoon')
                    ->relationship('manga', 'title')
                    ->searchable()
                    ->preload()
                    ->required(),

                TextInput::make('release_time')
                    ->label('Heure estimée')
                    ->default('18h00')
                    ->placeholder('ex: 18h00, 20h00')
                    ->required(),

                TextInput::make('status_label')
                    ->label('Badge / Statut')
                    ->default('Régulier')
                    ->placeholder('ex: Officiel HS, Régulier, En pause, Spécial'),

                Toggle::make('is_priority')
                    ->label('Priorité / Drop Majeur')
                    ->default(false),

                Toggle::make('is_active')
                    ->label('Visible sur le planning')
                    ->default(true),

                TextInput::make('sort_order')
                    ->label('Ordre')
                    ->numeric()
                    ->default(0),
            ]);
    }
}
