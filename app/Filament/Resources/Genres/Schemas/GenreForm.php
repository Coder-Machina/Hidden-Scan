<?php

namespace App\Filament\Resources\Genres\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class GenreForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')->label('Nom')
                    ->required(),
                TextInput::make('slug')->label('Lien (Slug)')
                    ->required(),
            ]);
    }
}
