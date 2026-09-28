<?php

namespace App\Filament\Resources\Chapters\Tables;

use App\Models\Chapter;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class ChaptersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('manga.title')
                    ->label('Œuvre')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->limit(30),
                TextColumn::make('number')->label('Numéro')
                    ->label('N°')
                    ->sortable()
                    ->badge()
                    ->color('primary'),
                TextColumn::make('title')->label('Titre')
                    ->label('Titre')
                    ->searchable()
                    ->placeholder('—')
                    ->limit(30),
                TextColumn::make('status')->label('Statut')
                    ->label('Statut')
                    ->badge(),
                TextColumn::make('pages_count')->label('Pages')
                    ->label('Pages')
                    ->counts('pages')
                    ->sortable()
                    ->badge()
                    ->color('gray'),
                TextColumn::make('views_count')->label('Vues')
                    ->label('Vues')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('published_at')->label('Publié le')
                    ->label('Publié le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->placeholder('Non publié'),
                TextColumn::make('created_at')->label('Date de création')
                    ->label('Créé le')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('manga')
                    ->label('Œuvre')
                    ->relationship('manga', 'title')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        'brouillon' => 'Brouillon',
                        'controle' => 'Contrôle',
                        'programme' => 'Programmé',
                        'publie' => 'Publié',
                    ]),
            ])
            ->recordActions([
                \Filament\Actions\Action::make('publish')
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
                \Filament\Actions\Action::make('control')
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
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    \Filament\Actions\BulkAction::make('bulk_publish')
                        ->label('Publier la sélection')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion()
                        ->action(function (Collection $records) {
                            $records->each(function (Chapter $record) {
                                $record->update([
                                    'status' => \App\Enums\ChapterStatus::PUBLIE,
                                    'published_at' => now(),
                                ]);
                            });

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
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
