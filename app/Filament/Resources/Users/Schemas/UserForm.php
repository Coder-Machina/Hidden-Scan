<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\User;
use Filament\Forms\Components\FileUpload;
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
                FileUpload::make('avatar')
                    ->label('Photo de profil (Avatar)')
                    ->image()
                    ->imageEditor()
                    ->circleCropper()
                    ->imageResizeMode('cover')
                    ->imageCropAspectRatio('1:1')
                    ->imageResizeTargetWidth('300')
                    ->imageResizeTargetHeight('300')
                    ->disk('public')
                    ->directory('avatars')
                    ->helperText('Cliquez sur le crayon pour recadrer, rogner ou redimensionner l\'avatar.'),
                TextInput::make('name')
                    ->label('Nom / Pseudo')
                    ->placeholder('ex: Alex, Zoro, Modo1')
                    ->required()
                    ->maxLength(255),
                Select::make('roles')
                    ->label('Rôles (ADMIN, MODO, UPLOADER)')
                    ->relationship(
                        name: 'roles',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn ($query) => $query->whereIn('name', ['admin', 'Admin', 'modo', 'Modo', 'uploader', 'Uploader'])
                    )
                    ->getOptionLabelFromRecordUsing(fn ($record) => strtoupper($record->name))
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
