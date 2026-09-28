<?php

namespace App\Filament\Resources\Chapters\Pages;

use App\Filament\Resources\Chapters\ChapterResource;
use App\Jobs\ProcessChapterZip;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditChapter extends EditRecord
{
    protected static string $resource = ChapterResource::class;

    public ?string $zipFilePath = null;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (isset($data['zip_file'])) {
            $zip = $data['zip_file'];
            $this->zipFilePath = is_array($zip) ? array_values($zip)[0] : $zip;
            unset($data['zip_file']);
        }

        if (isset($data['number']) && empty($data['slug'])) {
            $data['slug'] = 'chapitre-' . str_replace('.', '-', (string) $data['number']);
        }

        return $data;
    }

    protected function afterSave(): void
    {
        if ($this->zipFilePath) {
            @ini_set('max_execution_time', '0');
            @set_time_limit(0);
            $shouldPublish = ($this->record->status === \App\Enums\ChapterStatus::PUBLIE);
            ProcessChapterZip::dispatch($this->record, $this->zipFilePath, publishWhenReady: $shouldPublish);
        }
    }
}
