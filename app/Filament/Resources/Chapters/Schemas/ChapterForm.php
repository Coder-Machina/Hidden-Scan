<?php

namespace App\Filament\Resources\Chapters\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ChapterForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('manga_id')
                    ->required()
                    ->numeric(),
                TextInput::make('number')
                    ->required()
                    ->numeric(),
                TextInput::make('title')
                    ->default(null),
                TextInput::make('slug')
                    ->required(),
                Select::make('status')
                    ->options([
            'brouillon' => 'Brouillon',
            'controle' => 'Controle',
            'programme' => 'Programme',
            'publie' => 'Publie',
        ])
                    ->default('brouillon')
                    ->required(),
                TextInput::make('uploaded_by')
                    ->numeric()
                    ->default(null),
                DateTimePicker::make('scheduled_at'),
                DateTimePicker::make('published_at'),
                TextInput::make('views_count')
                    ->required()
                    ->numeric()
                    ->default(0),
            ]);
    }
}
