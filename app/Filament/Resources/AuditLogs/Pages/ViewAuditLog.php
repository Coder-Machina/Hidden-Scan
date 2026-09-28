<?php

namespace App\Filament\Resources\AuditLogs\Pages;

use App\Filament\Resources\AuditLogs\AuditLogResource;
use Filament\Resources\Pages\ViewRecord;

class ViewAuditLog extends ViewRecord
{
    protected static string $resource = AuditLogResource::class;

    public function getTitle(): string
    {
        $record = $this->getRecord();
        return "Détail du journal d'audit #{$record->id} — {$record->action_label}";
    }

    protected function getHeaderActions(): array
    {
        return [
            // Read-only — no edit/delete
        ];
    }
}
