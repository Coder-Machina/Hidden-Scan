<?php

namespace App\Filament\Resources\Chapters\Pages;

use App\Filament\Resources\Chapters\ChapterResource;
use App\Jobs\ProcessChapterZip;
use Filament\Resources\Pages\CreateRecord;

class CreateChapter extends CreateRecord
{
    protected static string $resource = ChapterResource::class;

    protected function afterCreate(): void
{
    $zipPath = $this->data['zip_file'] ?? null;

    if ($zipPath) {
        if (is_array($zipPath)) {
            $zipPath = array_values($zipPath)[0];
        }
        ProcessChapterZip::dispatch($this->record, $zipPath);
    }
}

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        unset($data['zip_file']);
        return $data;
    }
}