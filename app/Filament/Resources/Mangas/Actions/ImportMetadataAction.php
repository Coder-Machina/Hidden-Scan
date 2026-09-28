<?php

namespace App\Filament\Resources\Mangas\Actions;

use App\Models\Manga;
use App\Services\MangaMetadataService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Illuminate\Support\Str;

class ImportMetadataAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'import_metadata';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('🪄 Importer les infos')
            ->icon('heroicon-o-sparkles')
            ->color('warning')
            ->modalHeading('Import automatique des métadonnées')
            ->modalDescription('Recherchez une œuvre sur AniList, MangaUpdates, Kitsu, MangaDex ou MyAnimeList pour pré-remplir les informations, genres et couverture en un clin d\'œil.')
            ->modalSubmitActionLabel('Importer dans la fiche')
            ->form([
                Select::make('source')
                    ->label('Source de données')
                    ->options([
                        'anilist' => 'AniList (Recommandé - Rapide, complet & haute qualité)',
                        'mangaupdates' => 'MangaUpdates / Baka-Updates (La plus vaste base Webtoons & Mangas)',
                        'kitsu' => 'Kitsu (Excellente base Manhwas & Manhuas)',
                        'mangadex' => 'MangaDex (Idéal pour synopsis en Français)',
                        'myanimelist' => 'MyAnimeList (Base officielle MAL)',
                    ])
                    ->default('anilist')
                    ->required()
                    ->live(),
                Select::make('selected_item')
                    ->label('Rechercher une œuvre')
                    ->placeholder('Tapez au moins 2 caractères (ex: Solo Leveling, One Piece...)')
                    ->searchable()
                    ->required()
                    ->getSearchResultsUsing(function (string $search, callable $get): array {
                        if (strlen(trim($search)) < 2) {
                            return [];
                        }

                        $service = app(MangaMetadataService::class);
                        $source = $get('source') ?? 'anilist';
                        $results = $service->search($search, $source);

                        $options = [];
                        foreach ($results as $item) {
                            $year = $item['release_year'] ? " ({$item['release_year']})" : '';
                            $type = strtoupper($item['type']);
                            $alt = $item['alt_title'] && $item['alt_title'] !== $item['title'] ? " — " . Str::limit($item['alt_title'], 25) : '';
                            $key = $item['source'] . '::' . $item['id'];
                            $options[$key] = "{$item['title']}{$year} [{$type}]{$alt}";
                        }

                        return $options;
                    })
                    ->getOptionLabelUsing(function ($value): ?string {
                        if (empty($value) || !str_contains($value, '::')) {
                            return null;
                        }

                        [$source, $id] = explode('::', $value, 2);
                        $service = app(MangaMetadataService::class);
                        $metadata = $service->getDetails($source, $id);

                        if (!$metadata) {
                            return $value;
                        }

                        $year = !empty($metadata['release_year']) ? " ({$metadata['release_year']})" : '';
                        $type = strtoupper($metadata['type'] ?? 'manga');
                        $alt = !empty($metadata['alt_title']) && $metadata['alt_title'] !== $metadata['title']
                            ? " — " . Str::limit($metadata['alt_title'], 25)
                            : '';

                        return "{$metadata['title']}{$year} [{$type}]{$alt}";
                    }),
                Toggle::make('translate_french')
                    ->label('Traduire automatiquement le synopsis en Français 🇫🇷')
                    ->default(true),
                Toggle::make('download_cover')
                    ->label('Télécharger et enregistrer la couverture officielle')
                    ->default(true),
            ])
            ->action(function (array $data, $livewire) {
                $service = app(MangaMetadataService::class);

                [$source, $id] = explode('::', $data['selected_item'], 2);
                $metadata = $service->getDetails($source, $id);

                if (!$metadata) {
                    Notification::make()
                        ->title('Erreur')
                        ->body("Impossible de récupérer les détails pour cette œuvre.")
                        ->danger()
                        ->send();
                    return;
                }

                // Traduction automatique du synopsis si demandé
                if (!empty($data['translate_french']) && !empty($metadata['synopsis'])) {
                    $metadata['synopsis'] = $service->translateToFrench($metadata['synopsis']);
                }

                $coverPath = null;
                if (!empty($data['download_cover']) && !empty($metadata['cover_url'])) {
                    $coverPath = $service->downloadAndStoreImage($metadata['cover_url']);
                }

                $relations = $service->syncRelations($metadata);

                $slug = Str::slug($metadata['title']);

                $fillData = [
                    'title' => $metadata['title'],
                    'slug' => $slug,
                    'synopsis' => $metadata['synopsis'] ?: 'Synopsis en cours de rédaction.',
                    'type' => $metadata['type'],
                    'status' => $metadata['status'],
                    'release_year' => $metadata['release_year'],
                    'genres' => $relations['genre_ids'],
                    'authors' => $relations['author_ids'],
                    'artists' => $relations['artist_ids'],
                ];

                if ($coverPath) {
                    $fillData['cover_image'] = $coverPath;
                }

                if ($livewire instanceof \Filament\Resources\Pages\CreateRecord) {
                    $fillData['views_count'] = 0;
                    $fillData['is_featured'] = false;
                    $livewire->form->fill($fillData);
                } elseif ($livewire instanceof \Filament\Resources\Pages\EditRecord) {
                    /** @var Manga $manga */
                    $manga = $livewire->getRecord();
                    $updateAttributes = collect($fillData)->except(['genres', 'authors', 'artists'])->toArray();
                    $manga->update($updateAttributes);
                    $manga->genres()->sync($relations['genre_ids']);
                    $manga->authors()->sync($relations['author_ids']);
                    $manga->artists()->sync($relations['artist_ids']);
                    $manga->refresh();

                    $livewire->form->fill([
                        ...$manga->attributesToArray(),
                        'genres' => $relations['genre_ids'],
                        'authors' => $relations['author_ids'],
                        'artists' => $relations['artist_ids'],
                    ]);
                }

                Notification::make()
                    ->title('Métadonnées importées avec succès !')
                    ->body("Les informations de « {$metadata['title']} » ont été appliquées depuis " . ucfirst($source) . ".")
                    ->success()
                    ->send();
            });
    }
}
