<?php

namespace App\Filament\Resources\ReleaseSchedules\Pages;

use App\Filament\Resources\ReleaseSchedules\ReleaseScheduleResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditReleaseSchedule extends EditRecord
{
    protected static string $resource = ReleaseScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
