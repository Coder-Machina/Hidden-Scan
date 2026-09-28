<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informations du profil')
                    ->icon('heroicon-o-user')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('name')
                            ->label('Nom / Pseudo'),
                        TextEntry::make('email')
                            ->label('Adresse email')
                            ->copyable(),
                        TextEntry::make('roles.name')
                            ->label('Rôles')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'owner' => 'danger',
                                'admin' => 'warning',
                                default => 'info',
                            })
                            ->placeholder('Membre standard'),
                        TextEntry::make('comments_count')
                            ->label('Commentaires postés')
                            ->state(fn ($record) => $record->comments()->count())
                            ->badge()
                            ->color('primary'),
                        TextEntry::make('email_verified_at')
                            ->label('Email vérifié le')
                            ->dateTime('d/m/Y H:i')
                            ->placeholder('Non vérifié'),
                        TextEntry::make('created_at')
                            ->label('Date d\'inscription')
                            ->dateTime('d/m/Y H:i'),
                    ]),
            ]);
    }
}
