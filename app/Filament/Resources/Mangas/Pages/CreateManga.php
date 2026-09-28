<?php

namespace App\Filament\Resources\Mangas\Pages;

use App\Filament\Resources\Mangas\Actions\ImportMetadataAction;
use App\Filament\Resources\Mangas\MangaResource;
use Filament\Resources\Pages\CreateRecord;

class CreateManga extends CreateRecord
{
    protected static string $resource = MangaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ImportMetadataAction::make(),
        ];
    }
}
