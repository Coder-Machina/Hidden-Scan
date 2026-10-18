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
                    ->label('Pages du chapitre (ZIP, CBZ, TAR)')
                    ->disk('local')
                    ->directory('uploads/zips')
                    ->acceptedFileTypes([
                        'application/zip',
                        'application/x-zip',
                        'application/x-zip-compressed',
                        'application/vnd.comicbook+zip',
                        'application/x-cbz',
                        'application/x-tar',
                        'application/tar',
                        'application/x-gtar',
                        'multipart/x-tar',
                        'application/octet-stream',
                        'application/x-compressed',
                    ])
                    ->helperText('Accepte les archives .zip, .cbz ou .tar contenant les pages d\'images du chapitre.')
                    ->maxSize(1048576),
                Select::make('max_page_width')
                    ->label('Redimensionnement des pages importées')
                    ->options([
                        '1400' => 'HD 1400px de large (Recommandé - Optimal pour la lecture)',
                        '1200' => '1200px de large (Standard)',
                        '1080' => '1080px de large (Léger mobile)',
                        '1600' => '1600px de large (Haute résolution)',
                        'original' => 'Taille originale (ne pas redimensionner)',
                    ])
                    ->default('1400')
                    ->helperText('Redimensionne automatiquement les images trop volumineuses de l\'archive.'),
            ]);
    }
}