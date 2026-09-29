<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Ajouter un membre staff'),
        ];
    }

    public function getTabs(): array
    {
        $staffRoles = ['admin', 'Admin', 'owner', 'Owner', 'modo', 'Modo', 'uploader', 'Uploader'];

        return [
            'staff' => Tab::make('Équipe Staff')
                ->icon('heroicon-o-shield-check')
                ->badge(fn () => User::role($staffRoles)->count())
                ->badgeColor('primary')
                ->modifyQueryUsing(fn (Builder $query) => $query->role($staffRoles)),

            'banned' => Tab::make('Modération & Bannis')
                ->icon('heroicon-o-no-symbol')
                ->badge(fn () => User::where('is_banned', true)->orWhere('is_comment_banned', true)->count())
                ->badgeColor('danger')
                ->modifyQueryUsing(fn (Builder $query) => $query->where(fn ($q) => $q->where('is_banned', true)->orWhere('is_comment_banned', true))),

            'all' => Tab::make('Tous les comptes')
                ->icon('heroicon-o-users'),
        ];
    }
}
