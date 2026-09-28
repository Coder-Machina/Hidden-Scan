<?php

namespace App\Filament\Resources\Chapters\Pages;

use App\Filament\Resources\Chapters\ChapterResource;
use App\Jobs\ProcessChapterZip;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Log;

class CreateChapter extends CreateRecord
{
    protected static string $resource = ChapterResource::class;

    public ?string $zipFilePath = null;
    public bool $publishWhenReady = false;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (isset($data['zip_file'])) {
            $zip = $data['zip_file'];
            $this->zipFilePath = is_array($zip) ? array_values($zip)[0] : $zip;
            unset($data['zip_file']);

            $statusVal = $data['status'] ?? null;
            if ($statusVal === \App\Enums\ChapterStatus::PUBLIE->value || $statusVal === \App\Enums\ChapterStatus::PUBLIE) {
                $this->publishWhenReady = true;
                $data['status'] = \App\Enums\ChapterStatus::CONTROLE;
            }
        }

        // Auto-génère le slug depuis le numéro de chapitre
        $data['slug'] = 'chapitre-' . str_replace('.', '-', (string) $data['number']);

        return $data;
    }
    
    protected function afterCreate(): void
    {
        Log::info('afterCreate déclenché', ['zipFilePath' => $this->zipFilePath]);

        if ($this->zipFilePath) {
            @ini_set('max_execution_time', '0');
            @set_time_limit(0);
            ProcessChapterZip::dispatch($this->record, $this->zipFilePath, publishWhenReady: $this->publishWhenReady);
        }
    }
}