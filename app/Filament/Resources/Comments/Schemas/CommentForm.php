<?php

namespace App\Filament\Resources\Comments\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class CommentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('commentable_type')
                    ->required(),
                TextInput::make('commentable_id')
                    ->required()
                    ->numeric(),
                TextInput::make('parent_id')
                    ->numeric()
                    ->default(null),
                TextInput::make('pseudo')->label('Pseudo')
                    ->required(),
                Textarea::make('content')->label('Contenu')
                    ->required()
                    ->columnSpanFull(),
                TextInput::make('ip_hash')
                    ->required(),
                Toggle::make('is_hidden')
                    ->required(),
                Toggle::make('is_pinned')
                    ->required(),
            ]);
    }
}
