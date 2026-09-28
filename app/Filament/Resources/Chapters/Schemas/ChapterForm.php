<?php

namespace App\Filament\Resources\Chapters\Schemas;

use App\Jobs\ProcessChapterZip;
use App\Models\Manga;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ChapterForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('manga_id')
                    ->label('Œuvre')
                    ->options(Manga::all()->pluck('title', 'id'))
                    ->searchable()
                    ->hidden(fn ($livewire) => $livewire instanceof \Filament\Resources\RelationManagers\RelationManager)
                    ->required(fn ($livewire) => ! ($livewire instanceof \Filament\Resources\RelationManagers\RelationManager)),
                TextInput::make('number')
                    ->label('Numéro')
                    ->numeric()
                    ->default(function ($livewire) {
                        if ($livewire instanceof \Filament\Resources\RelationManagers\RelationManager) {
                            $owner = $livewire->getOwnerRecord();
                            if ($owner) {
                                return (int) (($owner->chapters()->max('number') ?? 0) + 1);
                            }
                        }
                        return 1;
                    })
                    ->required(),
                TextInput::make('title')
                    ->label('Titre (optionnel)')
                    ->default(null),
                Select::make('status')->label('Statut')
                    ->label('Statut')
                    ->options([
                        'brouillon'  => 'Brouillon',
                        'controle'   => 'Contrôle',
                        'programme'  => 'Programmé',
                        'publie'     => 'Publié',
                    ])
                    ->default('brouillon')
                    ->required(),
                DateTimePicker::make('scheduled_at')
                    ->label('Programmé le'),
                DateTimePicker::make('published_at')
                    ->label('Publié le'),
                FileUpload::make('zip_file')
                    ->label('Pages du chapitre (ZIP)')
                    ->disk('local')
                    ->directory('uploads/zips')
                    ->acceptedFileTypes(['application/zip', 'application/x-zip-compressed'])
                    ->maxSize(512000)
                    ->afterStateUpdated(function ($state, $get, $set) {
                        // Le Job sera lancé après la création du chapitre
                    }),
            ]);
    }
}