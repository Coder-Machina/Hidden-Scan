<?php

namespace App\Filament\Resources\AuditLogs\Pages;

use App\Filament\Resources\AuditLogs\AuditLogResource;
use Filament\Resources\Pages\ListRecords;

class ListAuditLogs extends ListRecords
{
    protected static string $resource = AuditLogResource::class;

    public function getTitle(): string
    {
        return "Journaux d'audit";
    }

    protected function getHeaderActions(): array
    {
        return [
            // Read-only — no create action
        ];
    }
}
