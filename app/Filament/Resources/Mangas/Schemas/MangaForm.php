<?php

namespace App\Filament\Resources\Mangas\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class MangaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->required(),
                TextInput::make('slug')
                    ->required(),
                Textarea::make('synopsis')
                    ->default(null)
                    ->columnSpanFull(),
                FileUpload::make('cover_image')
                    ->image()
                    ->disk('public'),
                FileUpload::make('banner_image')
                    ->image()
                    ->disk('public'),
                TextInput::make('author_id')
                    ->numeric()
                    ->default(null),
                TextInput::make('artist_id')
                    ->numeric()
                    ->default(null),
                Select::make('type')
                    ->options(['manga' => 'Manga', 'manhwa' => 'Manhwa', 'manhua' => 'Manhua'])
                    ->default('manga')
                    ->required(),
                Select::make('status')
                    ->options([
            'en_cours' => 'En cours',
            'termine' => 'Termine',
            'pause' => 'Pause',
            'abandonne' => 'Abandonne',
        ])
                    ->default('en_cours')
                    ->required(),
                TextInput::make('release_year')
                    ->default(null),
                TextInput::make('views_count')
                    ->required()
                    ->numeric()
                    ->default(0),
                Toggle::make('is_featured')
                    ->required(),
            ]);
    }
}
