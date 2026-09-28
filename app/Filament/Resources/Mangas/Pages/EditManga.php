<?php

namespace App\Filament\Resources\Mangas\Pages;

use App\Filament\Resources\Mangas\MangaResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditManga extends EditRecord
{
    protected static string $resource = MangaResource::class;

    public function hasCombinedRelationManagerTabsWithContent(): bool
    {
        return true;
    }

    public function getContentTabLabel(): ?string
    {
        return 'Informations de l\'œuvre';
    }

    public function getContentTabIcon(): ?string
    {
        return 'heroicon-o-information-circle';
    }

    protected function getHeaderActions(): array
    {
        return [
            \App\Filament\Resources\Mangas\Actions\ImportMetadataAction::make(),
            DeleteAction::make(),
        ];
    }

    public function fillForm(): void
    {
        parent::fillForm();
    }
}
