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
                    ->label('Manga / Manhwa / Manhua')
                    ->options(Manga::all()->pluck('title', 'id'))
                    ->searchable()
                    ->required(),
                TextInput::make('number')
                    ->label('Numéro')
                    ->numeric()
                    ->required(),
                TextInput::make('title')
                    ->label('Titre (optionnel)')
                    ->default(null),
                TextInput::make('slug')
                    ->required(),
                Select::make('status')
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
                    ->maxSize(102400)
                    ->afterStateUpdated(function ($state, $get, $set) {
                        // Le Job sera lancé après la création du chapitre
                    }),
            ]);
    }
}