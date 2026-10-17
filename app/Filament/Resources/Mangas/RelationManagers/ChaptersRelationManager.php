<?php

namespace App\Filament\Resources\Mangas\RelationManagers;

use App\Filament\Resources\Chapters\Schemas\ChapterForm;
use App\Jobs\ProcessChapterZip;
use App\Models\Chapter;
use App\Models\Manga;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use BackedEnum;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class ChaptersRelationManager extends RelationManager
{
    protected static string $relationship = 'chapters';

    protected static ?string $title = 'Chapitres';

    protected static ?string $modelLabel = 'chapitre';

    protected static ?string $pluralModelLabel = 'chapitres';

    protected static string | BackedEnum | null $icon = 'heroicon-o-book-open';

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        return (string) $ownerRecord->chapters()->count();
    }

    public static function getBadgeColor(Model $ownerRecord, string $pageClass): ?string
    {
        return 'primary';
    }

    public function form(Schema $schema): Schema
    {
        return ChapterForm::configure($schema);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('number', 'desc')
            ->columns([
                TextColumn::make('number')
                    ->label('N°')
                    ->sortable()
                    ->badge()
                    ->color('primary'),
                TextColumn::make('title')
                    ->label('Titre')
                    ->searchable()
                    ->placeholder('—')
                    ->limit(35),
                TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->formatStateUsing(function ($state, Chapter $record) {
                        if ($record->status === \App\Enums\ChapterStatus::PROGRAMME && $record->scheduled_at) {
                            return 'Programmé (' . \App\Support\UserTimezone::format($record->scheduled_at, 'd/m H:i') . ')';
                        }
                        return $state instanceof \App\Enums\ChapterStatus ? $state->getLabel() : (string) $state;
                    }),
                TextColumn::make('pages_count')
                    ->label('Pages')
                    ->counts('pages')
                    ->sortable()
                    ->badge()
                    ->color('gray'),
                TextColumn::make('views_count')
                    ->label('Vues')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('published_at')
                    ->label('Publié le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->placeholder('Non publié'),
                TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        'brouillon' => 'Brouillon',
                        'controle' => 'Contrôle',
                        'programme' => 'Programmé',
                        'publie' => 'Publié',
                    ]),
            ])
            ->emptyStateHeading('Aucun chapitre')
            ->emptyStateDescription('Commencez par ajouter le premier chapitre de cette œuvre.')
            ->headerActions([
                Action::make('bulk_upload')
                    ->label('Upload en masse')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('success')
                    ->modalHeading(fn () => 'Upload en masse pour « ' . $this->getOwnerRecord()->title . ' »')
                    ->modalDescription('Sélectionnez plusieurs fichiers ZIP. Chaque fichier ZIP sera traité comme un chapitre séparé, numéroté séquentiellement ou selon le nom de fichier.')
                    ->modalSubmitActionLabel('Lancer le traitement')
                    ->form([
                        TextInput::make('start_number')
                            ->label('Numéro du premier chapitre')
                            ->numeric()
                            ->default(fn () => (int) (($this->getOwnerRecord()->chapters()->max('number') ?? 0) + 1))
                            ->required()
                            ->helperText('Détection automatique selon le nom de fichier si possible, sinon numérotation séquentielle à partir de ce numéro.'),
                        Select::make('status')
                            ->label('Statut des chapitres')
                            ->options([
                                'brouillon' => 'Brouillon',
                                'publie' => 'Publié directement',
                                'programme' => 'Programmer la publication',
                            ])
                            ->default('brouillon')
                            ->live()
                            ->required(),
                        \Filament\Forms\Components\DateTimePicker::make('scheduled_start')
                            ->label('Date & heure du premier chapitre')
                            ->default(fn () => \App\Support\UserTimezone::defaultSchedule())
                            ->required(fn ($get) => $get('status') === 'programme')
                            ->visible(fn ($get) => $get('status') === 'programme'),
                        Select::make('interval_hours')
                            ->label('Intervalle entre chaque chapitre')
                            ->options([
                                '0' => 'Tous en même temps',
                                '12' => 'Toutes les 12 heures',
                                '24' => '1 chapitre par jour (24h)',
                                '48' => '1 chapitre tous les 2 jours (48h)',
                                '72' => '1 chapitre tous les 3 jours (72h)',
                                '168' => '1 chapitre par semaine (7 jours)',
                            ])
                            ->default('24')
                            ->required(fn ($get) => $get('status') === 'programme')
                            ->visible(fn ($get) => $get('status') === 'programme'),
                        Select::make('max_page_width')
                            ->label('Redimensionnement des pages importées')
                            ->options([
                                '1400' => 'HD 1400px (Recommandé - Idéal web & mobile)',
                                '1200' => '1200px (Standard)',
                                '1080' => '1080px (Léger mobile)',
                                '1600' => '1600px (Haute résolution)',
                                'original' => 'Taille originale (aucun redimensionnement)',
                            ])
                            ->default('1400')
                            ->helperText('Redimensionne automatiquement les images trop volumineuses du ZIP.'),
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
                            ->helperText('Chaque ZIP = 1 chapitre. Publication et tri automatique par ordre séquentiel.'),
                    ])
                    ->action(function (array $data) {
                        /** @var Manga $manga */
                        $manga = $this->getOwnerRecord();
                        $zipFiles = $data['zip_files'];
                        $originalNames = $data['zip_original_names'] ?? [];
                        $startNumber = (float) $data['start_number'];
                        $status = $data['status'];
                        $shouldPublish = ($status === 'publie');
                        $isScheduled = ($status === 'programme');
                        $scheduledStart = $isScheduled && !empty($data['scheduled_start'])
                            ? \Carbon\Carbon::parse($data['scheduled_start'])
                            : null;
                        $intervalHours = (int) ($data['interval_hours'] ?? 0);

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

                            // Inspection de l'archive ZIP interne
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

                            // Repli sur le nom de fichier d'origine
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

                        $allHaveDetected = count($inspectedFiles) > 0 && collect($inspectedFiles)->every(fn($item) => $item['detected_number'] !== null);
                        $uniqueDetected = $allHaveDetected && collect($inspectedFiles)->pluck('detected_number')->unique()->count() === count($inspectedFiles);

                        $createdCount = 0;

                        foreach ($inspectedFiles as $index => $item) {
                            if ($uniqueDetected && $startNumber == 1) {
                                $chapterNumber = $item['detected_number'];
                            } else {
                                $chapterNumber = $startNumber + $index;
                            }

                            $slug = 'chapitre-' . str_replace('.', '-', (string) $chapterNumber);

                            $chapterStatus = \App\Enums\ChapterStatus::BROUILLON;
                            $scheduledAt = null;

                            if ($shouldPublish) {
                                $chapterStatus = \App\Enums\ChapterStatus::CONTROLE;
                            } elseif ($isScheduled && $scheduledStart) {
                                $chapterStatus = \App\Enums\ChapterStatus::PROGRAMME;
                                $scheduledAt = $scheduledStart->copy()->addHours($index * $intervalHours);
                            }

                            $chapter = Chapter::create([
                                'manga_id' => $manga->id,
                                'number' => $chapterNumber,
                                'slug' => $slug,
                                'status' => $chapterStatus,
                                'scheduled_at' => $scheduledAt,
                                'published_at' => null,
                            ]);

                            $maxPageWidth = $data['max_page_width'] ?? 1400;
                            ProcessChapterZip::dispatch($chapter, $item['file'], publishWhenReady: $shouldPublish, maxPageWidth: $maxPageWidth);
                            $createdCount++;
                        }

                        Log::info('Bulk upload lancé depuis le panel Manga', [
                            'manga' => $manga->title,
                            'chapters_created' => $createdCount,
                            'ordered_files' => collect($inspectedFiles)->pluck('name'),
                        ]);

                        $message = $isScheduled
                            ? "{$createdCount} chapitre(s) créé(s) pour « {$manga->title} » et programmés à partir du " . \App\Support\UserTimezone::format($scheduledStart, 'd/m/Y H:i') . "."
                            : "{$createdCount} chapitre(s) créé(s) pour « {$manga->title} ». Traitement et publication en cours dans l'ordre strict.";

                        Notification::make()
                            ->title($isScheduled ? 'Upload et programmation réussis' : 'Upload en masse lancé')
                            ->body($message)
                            ->success()
                            ->send();
                    }),

                CreateAction::make()
                    ->label('Nouveau chapitre')
                    ->icon('heroicon-o-plus')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['slug'] = 'chapitre-' . str_replace('.', '-', (string) $data['number']);
                        return $data;
                    })
                    ->after(function (Chapter $record, array $data) {
                        if (!empty($data['zip_file'])) {
                            $zip = $data['zip_file'];
                            $zipPath = is_array($zip) ? array_values($zip)[0] : $zip;
                            $statusVal = $data['status'] ?? null;
                            $shouldPublish = ($statusVal === \App\Enums\ChapterStatus::PUBLIE->value || $statusVal === \App\Enums\ChapterStatus::PUBLIE);
                            $maxPageWidth = $data['max_page_width'] ?? 1400;

                            if ($shouldPublish) {
                                $record->update(['status' => \App\Enums\ChapterStatus::CONTROLE]);
                            }

                            @ini_set('max_execution_time', '0');
                            @set_time_limit(0);
                            ProcessChapterZip::dispatch($record, $zipPath, publishWhenReady: $shouldPublish, maxPageWidth: $maxPageWidth);
                        }
                    }),
            ])
            ->recordActions([
                Action::make('publish')
                    ->label('Publier')
                    ->icon('heroicon-m-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Chapter $record): bool => $record->status !== \App\Enums\ChapterStatus::PUBLIE)
                    ->action(function (Chapter $record) {
                        $record->update([
                            'status' => \App\Enums\ChapterStatus::PUBLIE,
                            'published_at' => now(),
                        ]);

                        \App\Models\AuditLog::create([
                            'user_id' => auth()->id(),
                            'action' => 'published_chapter',
                            'model_type' => Chapter::class,
                            'model_id' => $record->id,
                            'new_values' => [
                                'manga' => $record->manga?->title,
                                'chapter_number' => $record->number,
                                'chapter_title' => $record->title,
                            ],
                            'ip_address' => request()->ip(),
                        ]);
                    }),
                Action::make('schedule')
                    ->label('Programmer')
                    ->icon('heroicon-m-calendar')
                    ->color('info')
                    ->visible(fn (Chapter $record): bool => $record->status !== \App\Enums\ChapterStatus::PUBLIE)
                    ->form([
                        \Filament\Forms\Components\DateTimePicker::make('scheduled_at')
                            ->label('Date et heure de publication')
                            ->default(fn (Chapter $record) => $record->scheduled_at ?? \App\Support\UserTimezone::defaultSchedule())
                            ->required(),
                    ])
                    ->action(function (Chapter $record, array $data) {
                        $record->update([
                            'status' => \App\Enums\ChapterStatus::PROGRAMME,
                            'scheduled_at' => $data['scheduled_at'],
                        ]);

                        Notification::make()
                            ->title('Publication programmée')
                            ->body("Le chapitre #{$record->number} est programmé pour le " . \App\Support\UserTimezone::format($data['scheduled_at'], 'd/m/Y à H:i'))
                            ->info()
                            ->send();
                    }),
                Action::make('control')
                    ->label('Contrôler')
                    ->icon('heroicon-m-magnifying-glass')
                    ->color('warning')
                    ->visible(fn (Chapter $record): bool => $record->status === \App\Enums\ChapterStatus::BROUILLON)
                    ->action(function (Chapter $record) {
                        $record->update(['status' => \App\Enums\ChapterStatus::CONTROLE]);

                        \App\Models\AuditLog::create([
                            'user_id' => auth()->id(),
                            'action' => 'controlled_chapter',
                            'model_type' => Chapter::class,
                            'model_id' => $record->id,
                            'new_values' => [
                                'manga' => $record->manga?->title,
                                'chapter_number' => $record->number,
                            ],
                            'ip_address' => request()->ip(),
                        ]);
                    }),
                EditAction::make()
                    ->after(function (Chapter $record, array $data) {
                        if (!empty($data['zip_file'])) {
                            $zip = $data['zip_file'];
                            $zipPath = is_array($zip) ? array_values($zip)[0] : $zip;
                            $statusVal = $data['status'] ?? null;
                            $shouldPublish = ($statusVal === \App\Enums\ChapterStatus::PUBLIE->value || $statusVal === \App\Enums\ChapterStatus::PUBLIE);
                            $maxPageWidth = $data['max_page_width'] ?? 1400;

                            if ($shouldPublish) {
                                $record->update(['status' => \App\Enums\ChapterStatus::CONTROLE]);
                            }
                            @ini_set('max_execution_time', '0');
                            @set_time_limit(0);
                            ProcessChapterZip::dispatch($record, $zipPath, publishWhenReady: $shouldPublish, maxPageWidth: $maxPageWidth);
                        }
                    }),
                Action::make('resize_pages')
                    ->label('Redimensionner')
                    ->icon('heroicon-m-arrows-pointing-in')
                    ->color('info')
                    ->modalHeading(fn (Chapter $record) => "Redimensionner les images du Chapitre {$record->number}")
                    ->modalDescription("Optimise et redimensionne toutes les pages existantes de ce chapitre pour un affichage plus fluide.")
                    ->form([
                        Select::make('target_width')
                            ->label('Largeur maximale (pixels)')
                            ->options([
                                '1400' => '1400px (Recommandé HD - Idéal PC & Mobile)',
                                '1200' => '1200px (Standard - Très rapide)',
                                '1080' => '1080px (Léger - Économie de bande passante)',
                                '900'  => '900px (Mobile ultra fluide)',
                                '1600' => '1600px (Très haute résolution)',
                            ])
                            ->default('1400')
                            ->required(),
                        Select::make('quality')
                            ->label('Qualité d\'encodage WebP')
                            ->options([
                                '85' => '85% (Haute qualité)',
                                '80' => '80% (Équilibré - Recommandé)',
                                '75' => '75% (Compacité maximale)',
                                '90' => '90% (Qualité maximale)',
                            ])
                            ->default('80')
                            ->required(),
                    ])
                    ->action(function (Chapter $record, array $data) {
                        $targetWidth = (int) $data['target_width'];
                        $quality = (int) $data['quality'];
                        $pages = $record->pages()->orderBy('page_number')->get();

                        if ($pages->isEmpty()) {
                            Notification::make()->title('Aucune page trouvée pour ce chapitre')->warning()->send();
                            return;
                        }

                        $manager = new \Intervention\Image\ImageManager(new \Intervention\Image\Drivers\Gd\Driver());
                        $resizedCount = 0;

                        foreach ($pages as $page) {
                            $path = Storage::disk('public')->path($page->image_path);
                            if (!file_exists($path)) {
                                continue;
                            }

                            try {
                                $image = $manager->decode($path);
                                if ($image->width() > $targetWidth) {
                                    $image->scaleDown(width: $targetWidth);
                                }
                                $encoded = $image->encode(new \Intervention\Image\Encoders\WebpEncoder(quality: $quality));

                                $newRelativePath = 'chapters/' . $record->id . '/' . $page->page_number . '.webp';
                                Storage::disk('public')->put($newRelativePath, (string) $encoded);

                                if ($newRelativePath !== $page->image_path) {
                                    Storage::disk('public')->delete($page->image_path);
                                    $page->update(['image_path' => $newRelativePath]);
                                }
                                $resizedCount++;
                            } catch (\Throwable $e) {
                                Log::error("Erreur redimensionnement page {$page->id}: " . $e->getMessage());
                            }
                        }

                        Notification::make()
                            ->title("{$resizedCount} image(s) redimensionnée(s)")
                            ->body("Toutes les pages du chapitre {$record->number} ont été optimisées en max {$targetWidth}px (WebP {$quality}%).")
                            ->success()
                            ->send();
                    }),
                Action::make('notify_discord')
                    ->label('Annoncer Discord')
                    ->icon('heroicon-m-megaphone')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->modalHeading('Annoncer sur Discord')
                    ->modalDescription('Envoyer immédiatement la fiche d\'annonce de ce chapitre sur Discord.')
                    ->modalSubmitActionLabel('Envoyer maintenant')
                    ->action(function (Chapter $record) {
                        $sent = app(\App\Services\DiscordWebhookService::class)->sendChapterNotification($record, force: true);
                        if ($sent) {
                            Notification::make()
                                ->title('Annonce Discord envoyée')
                                ->body("Le chapitre #{$record->number} a été annoncé sur Discord.")
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Échec de l\'envoi Discord')
                                ->danger()
                                ->send();
                        }
                    }),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('bulk_resize')
                        ->label('Redimensionner la sélection')
                        ->icon('heroicon-o-arrows-pointing-in')
                        ->color('info')
                        ->form([
                            Select::make('target_width')
                                ->label('Largeur maximale (pixels)')
                                ->options([
                                    '1400' => '1400px (Recommandé HD)',
                                    '1200' => '1200px (Standard)',
                                    '1080' => '1080px (Léger mobile)',
                                    '1600' => '1600px (Haute résolution)',
                                ])
                                ->default('1400')
                                ->required(),
                            Select::make('quality')
                                ->label('Qualité d\'encodage WebP')
                                ->options([
                                    '85' => '85%',
                                    '80' => '80%',
                                    '75' => '75%',
                                ])
                                ->default('80')
                                ->required(),
                        ])
                        ->action(function (Collection $records, array $data) {
                            $targetWidth = (int) $data['target_width'];
                            $quality = (int) $data['quality'];
                            $manager = new \Intervention\Image\ImageManager(new \Intervention\Image\Drivers\Gd\Driver());
                            $totalResized = 0;

                            foreach ($records as $record) {
                                foreach ($record->pages()->orderBy('page_number')->get() as $page) {
                                    $path = Storage::disk('public')->path($page->image_path);
                                    if (!file_exists($path)) continue;
                                    try {
                                        $image = $manager->decode($path);
                                        if ($image->width() > $targetWidth) {
                                            $image->scaleDown(width: $targetWidth);
                                        }
                                        $encoded = $image->encode(new \Intervention\Image\Encoders\WebpEncoder(quality: $quality));
                                        $newRelativePath = 'chapters/' . $record->id . '/' . $page->page_number . '.webp';
                                        Storage::disk('public')->put($newRelativePath, (string) $encoded);
                                        if ($newRelativePath !== $page->image_path) {
                                            Storage::disk('public')->delete($page->image_path);
                                            $page->update(['image_path' => $newRelativePath]);
                                        }
                                        $totalResized++;
                                    } catch (\Throwable $e) {}
                                }
                            }

                            Notification::make()
                                ->title("{$records->count()} chapitre(s) redimensionnés")
                                ->body("{$totalResized} pages optimisées en max {$targetWidth}px.")
                                ->success()
                                ->send();
                        }),
                    BulkAction::make('bulk_publish')
                        ->label('Publier la sélection')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion()
                        ->action(function (Collection $records) {
                            /** @var Manga $manga */
                            $manga = $this->getOwnerRecord();

                            Chapter::$skipDiscordNotification = true;
                            try {
                                $records->each(function (Chapter $record) {
                                    $record->update([
                                        'status' => \App\Enums\ChapterStatus::PUBLIE,
                                        'published_at' => now(),
                                    ]);
                                });
                            } finally {
                                Chapter::$skipDiscordNotification = false;
                            }

                            app(\App\Services\DiscordWebhookService::class)->sendBatchChaptersNotification($manga, $records);

                            \App\Models\AuditLog::create([
                                'user_id' => auth()->id(),
                                'action' => 'bulk_published',
                                'model_type' => Chapter::class,
                                'model_id' => null,
                                'new_values' => [
                                    'count' => $records->count(),
                                    'chapters' => $records->map(fn ($r) => $r->manga?->title . ' #' . $r->number)->toArray(),
                                ],
                                'ip_address' => request()->ip(),
                            ]);
                        }),
                    BulkAction::make('bulk_notify_discord')
                        ->label('Annoncer la sélection sur Discord')
                        ->icon('heroicon-m-megaphone')
                        ->color('primary')
                        ->requiresConfirmation()
                        ->action(function (Collection $records) {
                            /** @var Manga $manga */
                            $manga = $this->getOwnerRecord();
                            $sent = app(\App\Services\DiscordWebhookService::class)->sendBatchChaptersNotification($manga, $records, force: true);
                            if ($sent) {
                                Notification::make()
                                    ->title('Annonce de lot envoyée')
                                    ->body("{$records->count()} chapitre(s) annoncés sur Discord.")
                                    ->success()
                                    ->send();
                            } else {
                                Notification::make()
                                    ->title('Échec de l\'annonce Discord')
                                    ->danger()
                                    ->send();
                            }
                        }),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
