<?php

namespace App\Filament\Resources\Mangas\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class MangaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informations générales')
                    ->description('Les informations de base de l\'œuvre.')
                    ->icon('heroicon-o-book-open')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('title')->label('Titre')
                                ->label('Titre')
                                ->required()
                                ->maxLength(255)
                                ->live(onBlur: true)
                                ->afterStateUpdated(fn ($state, callable $set) =>
                                    $set('slug', \Illuminate\Support\Str::slug($state))
                                ),
                            TextInput::make('slug')->label('Lien (Slug)')
                                ->required()
                                ->maxLength(255)
                                ->unique(ignoreRecord: true),
                        ]),
                        Textarea::make('synopsis')
                            ->label('Synopsis')
                            ->required()
                            ->rows(4)
                            ->columnSpanFull(),
                    ]),

                Section::make('Classification')
                    ->description('Catégorisez votre œuvre.')
                    ->icon('heroicon-o-tag')
                    ->columns(2)
                    ->schema([
                        Select::make('type')->label('Type')
                            ->label('Type')
                            ->options(['manga' => 'Manga', 'manhwa' => 'Manhwa', 'manhua' => 'Manhua'])
                            ->default('manga')
                            ->required(),
                        Select::make('status')->label('Statut')
                            ->label('Statut')
                            ->options([
                                'en_cours' => 'En cours',
                                'termine' => 'Terminé',
                                'pause' => 'En pause',
                                'abandonne' => 'Abandonné',
                            ])
                            ->default('en_cours')
                            ->required(),
                        Select::make('authors')
                            ->label('Auteur(s)')
                            ->relationship('authors', 'name')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                TextInput::make('name')->label('Nom')->required(),
                            ]),
                        Select::make('artists')
                            ->label('Artiste(s)')
                            ->relationship('artists', 'name')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                TextInput::make('name')->label('Nom')->required(),
                            ]),
                        Select::make('genres')
                            ->relationship('genres', 'name')
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->required()
                            ->label('Genres')
                            ->createOptionForm([
                                TextInput::make('name')->label('Nom du genre')->required(),
                            ]),
                        Select::make('tags')
                            ->relationship('tags', 'name')
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->label('Tags')
                            ->createOptionForm([
                                TextInput::make('name')->label('Nom du tag')->required(),
                            ]),
                        TextInput::make('release_year')
                            ->label('Année de sortie')
                            ->numeric()
                            ->minValue(1900)
                            ->maxValue(date('Y'))
                            ->default(null),
                    ]),

                Section::make('Médias')
                    ->description('Images de couverture et bannière.')
                    ->icon('heroicon-o-photo')
                    ->columns(2)
                    ->schema([
                        FileUpload::make('cover_image')
                            ->label('Couverture')
                            ->image()
                            ->imageEditor()
                            ->imageEditorAspectRatios([
                                null,
                                '2:3',
                                '3:4',
                                '1:1',
                                '16:9',
                            ])
                            ->imageEditorMode(2)
                            ->imageResizeMode('cover')
                            ->imageCropAspectRatio('2:3')
                            ->imageResizeTargetWidth('600')
                            ->imageResizeTargetHeight('900')
                            ->disk('public')
                            ->directory('mangas/covers')
                            ->helperText('Cliquez sur le crayon après sélection pour recadrer ou redimensionner l\'image.'),
                        FileUpload::make('banner_image')
                            ->label('Bannière')
                            ->image()
                            ->imageEditor()
                            ->imageEditorAspectRatios([
                                null,
                                '16:9',
                                '21:9',
                                '3:1',
                                '4:1',
                                '2:3',
                                '1:1',
                            ])
                            ->imageEditorMode(2)
                            ->imageResizeMode('cover')
                            ->imageResizeTargetWidth('1600')
                            ->imageResizeTargetHeight('600')
                            ->disk('public')
                            ->directory('mangas/banners')
                            ->helperText('Cliquez sur le crayon pour ajuster le cadrage ou la taille de la bannière héro.'),
                    ]),

                Section::make('Origine & Annonces Discord')
                    ->description('Distinguez les projets officiels de Hidden Scan des sorties partenaires/autres teams.')
                    ->icon('heroicon-o-megaphone')
                    ->columns(2)
                    ->schema([
                        Toggle::make('is_external')
                            ->label('Sortie Site (Autre team / Partenaire)')
                            ->helperText('Activez si l\'œuvre provient d\'une autre team. Les sorties seront annoncées dans le salon Discord des sorties site.')
                            ->default(false)
                            ->live(),
                        TextInput::make('team_name')
                            ->label('Nom de la team partenaire')
                            ->placeholder('Ex: Asura, Flame, etc.')
                            ->visible(fn ($get) => (bool) $get('is_external'))
                            ->helperText('Nom de la team affiché sur la fiche Discord.'),
                        TextInput::make('discord_role_id')
                            ->label('ID du Rôle Discord (Optionnel)')
                            ->placeholder('Ex: 1558575127636217986')
                            ->helperText('Rôle spécifique à mentionner pour ce projet.'),
                        TextInput::make('discord_webhook_url')
                            ->label('Webhook Discord dédié (Optionnel)')
                            ->placeholder('https://discord.com/api/webhooks/...')
                            ->helperText('Laissez vide pour utiliser le salon automatique (officiel ou sorties site).'),
                    ]),

                Section::make('Source des Chapitres')
                    ->description('Lier une URL externe pour importer les chapitres automatiquement.')
                    ->icon('heroicon-o-bolt')
                    ->columns(2)
                    ->schema([
                        Select::make('scraper_source_id')
                            ->label('Site source')
                            ->placeholder('— Aucune source (import manuel) —')
                            ->options(fn () => \App\Models\ScraperSource::where('active', true)->pluck('name', 'id'))
                            ->helperText('Sélectionne le site depuis lequel importer les chapitres.')
                            ->live()
                            ->searchable(),
                        TextInput::make('scraper_source_url')
                            ->label('URL du manga sur ce site')
                            ->placeholder('https://poseidon-scan.net/manga/solo-leveling')
                            ->url()
                            ->visible(fn ($get) => !empty($get('scraper_source_id')))
                            ->helperText('Colle l\'URL exacte de la page manga sur le site source. Les chapitres seront importés automatiquement en arrière-plan.')
                            ->columnSpanFull(),
                        \Filament\Forms\Components\Placeholder::make('scraper_status')
                            ->label('Statut scraping')
                            ->visible(fn ($record) => $record && $record->scraperManga)
                            ->columnSpanFull()
                            ->content(function ($record) {
                                if (!$record || !$record->scraperManga) return null;
                                $sm = $record->scraperManga;
                                $statusBadge = match($sm->status) {
                                    'active' => '<span class="px-2 py-1 text-xs font-semibold rounded bg-emerald-500/20 text-emerald-400">Actif</span>',
                                    'paused' => '<span class="px-2 py-1 text-xs font-semibold rounded bg-amber-500/20 text-amber-400">En pause</span>',
                                    'error' => '<span class="px-2 py-1 text-xs font-semibold rounded bg-rose-500/20 text-rose-400">Erreur</span>',
                                    default => ucfirst($sm->status),
                                };
                                $lastChap = $sm->last_chapter_scraped ? htmlspecialchars($sm->last_chapter_scraped) : 'Aucun';
                                $lastCheck = $sm->last_checked_at ? $sm->last_checked_at->diffForHumans() : 'Jamais';
                                $errorHtml = $sm->last_error ? '<div class="mt-2 text-xs text-rose-400">⚠️ ' . htmlspecialchars($sm->last_error) . '</div>' : '';
                                $syncRoute = route('admin.scraper.sync', $sm);
                                $csrfToken = csrf_token();

                                return new \Illuminate\Support\HtmlString("
                                    <div class='p-4 rounded-xl bg-gray-900/60 border border-gray-700/60 space-y-3 text-sm'>
                                        <div class='flex items-center justify-between'>
                                            <span class='text-gray-400 font-medium'>Statut :</span>
                                            <div>{$statusBadge}</div>
                                        </div>
                                        <div class='flex items-center justify-between'>
                                            <span class='text-gray-400 font-medium'>Dernier chapitre importé :</span>
                                            <span class='font-mono font-bold text-gray-200'>{$lastChap}</span>
                                        </div>
                                        <div class='flex items-center justify-between'>
                                            <span class='text-gray-400 font-medium'>Dernière vérification :</span>
                                            <span class='text-gray-300'>{$lastCheck}</span>
                                        </div>
                                        {$errorHtml}
                                        <div class='pt-2 border-t border-gray-800 flex justify-end'>
                                            <form method='POST' action='{$syncRoute}' class='inline'>
                                                <input type='hidden' name='_token' value='{$csrfToken}'>
                                                <button type='submit' class='px-3 py-1.5 text-xs font-medium rounded-lg bg-gray-800 hover:bg-gray-700 text-gray-200 border border-gray-600 transition shadow-sm'>
                                                    ↻ Vérifier maintenant
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                ");
                            }),
                    ]),

                Section::make('Options')
                    ->description('Paramètres généraux de l\'œuvre.')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->columns(2)
                    ->schema([
                        Toggle::make('is_featured')
                            ->label('Mise en avant')
                            ->helperText('Afficher dans la section "Recommandés".'),
                        TextInput::make('views_count')
                            ->label('Compteur de vues')
                            ->numeric()
                            ->default(0)
                            ->disabled()
                            ->dehydrateStateUsing(fn ($state) => (int) ($state ?? 0)),
                    ]),
            ]);
    }
}
