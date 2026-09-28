<?php

namespace App\Filament\Resources\Tags\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class TagForm
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
