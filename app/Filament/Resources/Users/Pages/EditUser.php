<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('public_profile')
                ->label('Profil public')
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->color('info')
                ->url(fn (): string => route('profile.public', $this->record->username ?: ($this->record->name ?: $this->record->id)))
                ->openUrlInNewTab(),
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
