<?php

namespace App\Filament\Resources\ReleaseSchedules\Pages;

use App\Filament\Resources\ReleaseSchedules\ReleaseScheduleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListReleaseSchedules extends ListRecords
{
    protected static string $resource = ReleaseScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Ajouter une sortie'),
        ];
    }
}
