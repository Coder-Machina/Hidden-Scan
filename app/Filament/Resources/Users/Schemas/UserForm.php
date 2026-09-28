<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nom / Pseudo')
                    ->required()
                    ->maxLength(255),
                TextInput::make('email')
                    ->label('Adresse email')
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                Select::make('roles')
                    ->label('Rôles (Admin, Modo, Uploader)')
                    ->relationship(
                        name: 'roles',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn ($query) => $query->whereIn('name', ['admin', 'Admin', 'modo', 'Modo', 'uploader', 'Uploader'])
                    )
                    ->multiple()
                    ->preload()
                    ->searchable(),
                TextInput::make('password')
                    ->label('Mot de passe')
                    ->password()
                    ->dehydrated(fn ($state) => filled($state))
                    ->required(fn (string $context): bool => $context === 'create')
                    ->maxLength(255),
            ]);
    }
}
