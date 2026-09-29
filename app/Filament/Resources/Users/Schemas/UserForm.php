<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nom / Pseudo')
                    ->placeholder('ex: Alex, Zoro, Modo1')
                    ->required()
                    ->maxLength(255),
                Select::make('roles')
                    ->label('Rôles (Admin, Modo, Uploader)')
                    ->relationship(
                        name: 'roles',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn ($query) => $query->whereIn('name', ['admin', 'Admin', 'owner', 'Owner', 'modo', 'Modo', 'uploader', 'Uploader'])
                    )
                    ->multiple()
                    ->preload()
                    ->required()
                    ->searchable(),
                TextInput::make('pass_code')
                    ->label('Pass Secret (Clé d\'accès)')
                    ->default(fn () => User::generateUniquePassCode())
                    ->required()
                    ->helperText('Donnez cette clé au membre du staff pour qu\'il se connecte directement sur le site.')
                    ->unique(ignoreRecord: true),
                TextInput::make('email')
                    ->label('Adresse email')
                    ->default(fn () => 'staff_' . strtolower(Str::random(6)) . '@hiddenscan.local')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                TextInput::make('password')
                    ->label('Mot de passe (par défaut : password)')
                    ->password()
                    ->default('password')
                    ->dehydrated(fn ($state) => filled($state))
                    ->nullable()
                    ->maxLength(255),
            ]);
    }
}
