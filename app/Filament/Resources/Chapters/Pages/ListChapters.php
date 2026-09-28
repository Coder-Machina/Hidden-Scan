<?php

namespace App\Filament\Resources\Chapters\Pages;

use App\Filament\Resources\Chapters\ChapterResource;
use App\Jobs\ProcessChapterZip;
use App\Models\Chapter;
use App\Models\Manga;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class ListChapters extends ListRecords
{
    protected static string $resource = ChapterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('bulk_upload')
                ->label('Upload en masse')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('success')
                ->modalHeading('Charger plusieurs chapitres')
                ->modalDescription('Sélectionnez un manga et uploadez plusieurs fichiers ZIP. Chaque fichier ZIP sera traité comme un chapitre séparé, numéroté séquentiellement à partir du numéro indiqué.')
                ->modalSubmitActionLabel('Lancer le traitement')
                ->form([
                    Select::make('manga_id')
                        ->label('Manga')
                        ->options(Manga::orderBy('title')->pluck('title', 'id'))
                        ->searchable()
                        ->required(),
                    TextInput::make('start_number')
                        ->label('Numéro du premier chapitre')
                        ->numeric()
                        ->default(1)
                        ->required()
                        ->helperText('Les chapitres suivants seront numérotés séquentiellement.'),
                    Select::make('status')->label('Statut')
                        ->label('Statut des chapitres')
                        ->options([
                            'brouillon' => 'Brouillon',
                            'publie' => 'Publié directement',
                        ])
                        ->default('brouillon')
                        ->required(),
                    FileUpload::make('zip_files')
                        ->label('Fichiers ZIP des chapitres')
                        ->disk('local')
                        ->directory('uploads/bulk-zips')
                        ->acceptedFileTypes(['application/zip', 'application/x-zip-compressed'])
                        ->maxSize(512000)
                        ->multiple()
                        ->preserveFilenames()
                        ->storeFileNamesIn('zip_original_names')
                        ->required()
                        ->helperText('Chaque ZIP = 1 chapitre. Détection et tri automatique par numéro de chapitre, peu importe la vitesse de chargement de chaque fichier.'),
                ])
                ->action(function (array $data) {
                    $manga = Manga::findOrFail($data['manga_id']);
                    $zipFiles = $data['zip_files'];
                    $originalNames = $data['zip_original_names'] ?? [];
                    $startNumber = (float) $data['start_number'];
                    $status = $data['status'];
                    $shouldPublish = ($status === 'publie');

                    if (!is_array($zipFiles)) {
                        $zipFiles = [$zipFiles];
                    }

                    @ini_set('max_execution_time', '0');
                    @set_time_limit(0);

                    // 1. Analyse et détection du numéro de chapitre pour chaque fichier ZIP
                    $inspectedFiles = [];
                    foreach ($zipFiles as $zipFile) {
                        $fullPath = null;
                        if (Storage::disk('local')->exists($zipFile)) {
                            $fullPath = Storage::disk('local')->path($zipFile);
                        } elseif (Storage::disk('public')->exists($zipFile)) {
                            $fullPath = Storage::disk('public')->path($zipFile);
                        } elseif (file_exists($zipFile)) {
                            $fullPath = $zipFile;
                        } elseif (file_exists(storage_path('app/private/' . $zipFile))) {
                            $fullPath = storage_path('app/private/' . $zipFile);
                        } elseif (file_exists(storage_path('app/' . $zipFile))) {
                            $fullPath = storage_path('app/' . $zipFile);
                        }

                        $origName = $originalNames[$zipFile] ?? basename($zipFile);
                        $detectedNumber = null;

                        // A. Inspection de l'archive ZIP interne
                        if ($fullPath && file_exists($fullPath)) {
                            $zip = new ZipArchive();
                            if ($zip->open($fullPath) === true) {
                                $limit = min(25, $zip->numFiles);
                                for ($i = 0; $i < $limit; $i++) {
                                    $entryName = $zip->getNameIndex($i);
                                    if (preg_match('/(?:chapitre|chapter|ch|ep|épisode|episode)[\s._-]*(\d+(?:\.\d+)?)/i', $entryName, $m)) {
                                        $detectedNumber = (float) $m[1];
                                        break;
                                    }
                                }
                                if ($detectedNumber === null) {
                                    for ($i = 0; $i < $limit; $i++) {
                                        $entryName = $zip->getNameIndex($i);
                                        if (preg_match('/(?:^|[\D])(\d+(?:\.\d+)?)(?:\.zip|\/|$)/i', $entryName, $m)) {
                                            $detectedNumber = (float) $m[1];
                                            break;
                                        }
                                    }
                                }
                                $zip->close();
                            }
                        }

                        // B. Repli sur le nom de fichier d'origine
                        if ($detectedNumber === null) {
                            if (preg_match('/(?:chapitre|chapter|ch|ep|épisode|episode)[\s._-]*(\d+(?:\.\d+)?)/i', $origName, $m)) {
                                $detectedNumber = (float) $m[1];
                            } elseif (preg_match('/(?:^|[\D])(\d+(?:\.\d+)?)(?:\.zip|\/|$)/i', $origName, $m)) {
                                $detectedNumber = (float) $m[1];
                            }
                        }

                        $inspectedFiles[] = [
                            'file' => $zipFile,
                            'full_path' => $fullPath,
                            'name' => $origName,
                            'detected_number' => $detectedNumber,
                        ];
                    }

                    // 2. Tri intelligent : garantit l'ordre chronologique des chapitres
                    usort($inspectedFiles, function ($a, $b) {
                        if ($a['detected_number'] !== null && $b['detected_number'] !== null) {
                            return $a['detected_number'] <=> $b['detected_number'];
                        }
                        if ($a['detected_number'] !== null) return -1;
                        if ($b['detected_number'] !== null) return 1;
                        return strnatcasecmp($a['name'], $b['name']);
                    });

                    // Vérifie si tous les fichiers ont un numéro détecté valide et unique
                    $allHaveDetected = count($inspectedFiles) > 0 && collect($inspectedFiles)->every(fn($item) => $item['detected_number'] !== null);
                    $uniqueDetected = $allHaveDetected && collect($inspectedFiles)->pluck('detected_number')->unique()->count() === count($inspectedFiles);

                    $createdCount = 0;

                    foreach ($inspectedFiles as $index => $item) {
                        // Utilise le numéro détecté si disponible et cohérent, ou calcul séquentiel depuis $startNumber
                        if ($uniqueDetected && $startNumber == 1) {
                            $chapterNumber = $item['detected_number'];
                        } else {
                            $chapterNumber = $startNumber + $index;
                        }

                        $slug = 'chapitre-' . str_replace('.', '-', (string) $chapterNumber);

                        $chapter = Chapter::create([
                            'manga_id' => $manga->id,
                            'number' => $chapterNumber,
                            'slug' => $slug,
                            // Si publication demandée, on passe en CONTROLE pendant le traitement
                            // pour empêcher l'affichage public prématuré et les notifications inversées.
                            'status' => $shouldPublish
                                ? \App\Enums\ChapterStatus::CONTROLE
                                : \App\Enums\ChapterStatus::BROUILLON,
                            'published_at' => null,
                        ]);

                        ProcessChapterZip::dispatch($chapter, $item['file'], publishWhenReady: $shouldPublish);
                        $createdCount++;
                    }

                    Log::info('Bulk upload lancé avec tri intelligent', [
                        'manga' => $manga->title,
                        'chapters_created' => $createdCount,
                        'ordered_files' => collect($inspectedFiles)->pluck('name'),
                    ]);

                    Notification::make()
                        ->title('Upload en masse lancé')
                        ->body("{$createdCount} chapitre(s) créé(s) pour « {$manga->title} » dans l'ordre séquentiel. La publication et les notifications s'effectueront dans l'ordre strict des chapitres dès le traitement terminé.")
                        ->success()
                        ->send();
                }),

            CreateAction::make()
                ->label('Nouveau chapitre'),
        ];
    }
}
