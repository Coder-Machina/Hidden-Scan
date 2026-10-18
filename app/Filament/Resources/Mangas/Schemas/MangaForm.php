<?php

namespace App\Filament\Resources\Mangas\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
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

                Section::make('Sources des Chapitres (Multi-sources & Relais intelligent)')
                    ->description('Configurez une ou plusieurs sources ordonnées par priorité. Si la source principale est en panne ou en retard, les sources suivantes prennent automatiquement le relais !')
                    ->icon('heroicon-o-bolt')
                    ->schema([
                        Repeater::make('scraper_sources')
                            ->label('Sources de chapitres')
                            ->itemLabel(function (array $state): ?string {
                                $sourceName = !empty($state['scraper_source_id'])
                                    ? (\App\Models\ScraperSource::find($state['scraper_source_id'])?->name ?? 'Source')
                                    : 'Nouvelle source';
                                $url = !empty($state['source_url']) ? ' — ' . \Illuminate\Support\Str::limit($state['source_url'], 35) : '';
                                return "{$sourceName}{$url}";
                            })
                            ->schema([
                                Select::make('scraper_source_id')
                                    ->label('Site source')
                                    ->placeholder('Sélectionner ou créer un site...')
                                    ->options(fn () => \App\Models\ScraperSource::where('active', true)->pluck('name', 'id'))
                                    ->searchable()
                                    ->preload()
                                    ->createOptionForm([
                                        TextInput::make('name')
                                            ->label('Nom du site')
                                            ->placeholder('Ex: Scan-FR, Asura Scans, Flame, Reaper...')
                                            ->required(),
                                        TextInput::make('base_url')
                                            ->label('Lien / URL du site')
                                            ->placeholder('https://mon-site-scan.fr')
                                            ->url()
                                            ->required()
                                            ->helperText('Collez l\'adresse principale du site (ex: https://mon-site.fr).'),
                                        Section::make('Réglages techniques (optionnel - réservé aux développeurs)')
                                            ->collapsed()
                                            ->compact()
                                            ->schema([
                                                TextInput::make('sel_chapter_list')
                                                    ->label('Sélecteur conteneur chapitres')
                                                    ->default('.wp-manga-chapter')
                                                    ->required(),
                                                TextInput::make('sel_chapter_title')
                                                    ->label('Sélecteur titre')
                                                    ->default('.wp-manga-chapter a')
                                                    ->required(),
                                                TextInput::make('sel_chapter_url')
                                                    ->label('Sélecteur lien')
                                                    ->default('.wp-manga-chapter a')
                                                    ->required(),
                                                TextInput::make('sel_chapter_pages')
                                                    ->label('Sélecteur images')
                                                    ->default('.page-break img')
                                                    ->required(),
                                            ]),
                                    ])
                                    ->createOptionAction(fn ($action) => $action->modalHeading('Ajouter un nouveau site source')->modalSubmitActionLabel('Enregistrer ce site'))
                                    ->helperText('Choisissez un site existant ou cliquez sur "+" pour en ajouter un nouveau.')
                                    ->columnSpan(4),
                                TextInput::make('source_url')
                                    ->label('URL du manga sur ce site')
                                    ->placeholder('https://poseidon-scan.net/manga/solo-leveling')
                                    ->url()
                                    ->required()
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        if (!empty($state) && empty($get('scraper_source_id'))) {
                                            $host = parse_url($state, PHP_URL_HOST);
                                            if ($host) {
                                                $existing = \App\Models\ScraperSource::where('base_url', 'like', "%{$host}%")->first();
                                                if ($existing) {
                                                    $set('scraper_source_id', $existing->id);
                                                }
                                            }
                                        }
                                    })
                                    ->helperText('Collez l\'URL complète de la page manga sur ce site.')
                                    ->columnSpan(6),
                                Select::make('status')
                                    ->label('Statut')
                                    ->options([
                                        'active' => 'Actif',
                                        'paused' => 'En pause',
                                    ])
                                    ->default('active')
                                    ->required()
                                    ->columnSpan(2),
                            ])
                            ->columns(12)
                            ->reorderable()
                            ->collapsible()
                            ->addActionLabel('+ Ajouter une source (principale ou relais)')
                            ->helperText('Glissez-déposez pour définir l\'ordre de priorité : la 1ère source est principale, la 2ème est le relais de secours n°1, etc.'),

                        Placeholder::make('scraper_multi_status')
                            ->label('Tableau de bord du relais intelligent')
                            ->visible(fn ($record) => $record && $record->scraperMangas()->exists())
                            ->columnSpanFull()
                            ->content(function ($record) {
                                if (!$record) return null;
                                $sources = $record->scraperMangas()->with('scraperSource')->orderBy('priority', 'asc')->get();
                                if ($sources->isEmpty()) return null;

                                $rowsHtml = '';
                                foreach ($sources as $index => $sm) {
                                    $rank = $index === 0
                                        ? '<span class="px-2 py-0.5 text-xs font-bold rounded bg-amber-500/20 text-amber-400">#1 Principale</span>'
                                        : '<span class="px-2 py-0.5 text-xs font-semibold rounded bg-blue-500/20 text-blue-400">#' . ($index + 1) . ' Secours</span>';
                                    $statusBadge = match($sm->status) {
                                        'active' => '<span class="px-2 py-0.5 text-xs font-semibold rounded bg-emerald-500/20 text-emerald-400">Actif</span>',
                                        'paused' => '<span class="px-2 py-0.5 text-xs font-semibold rounded bg-gray-500/20 text-gray-400">En pause</span>',
                                        'error'  => '<span class="px-2 py-0.5 text-xs font-semibold rounded bg-rose-500/20 text-rose-400">Erreur</span>',
                                        default  => ucfirst($sm->status),
                                    };
                                    $sourceName = htmlspecialchars($sm->scraperSource?->name ?? 'Source inconnue');
                                    $lastChap = $sm->last_chapter_scraped ? htmlspecialchars($sm->last_chapter_scraped) : 'Aucun';
                                    $lastCheck = $sm->last_checked_at ? $sm->last_checked_at->diffForHumans() : 'Jamais';
                                    $errorMsg = $sm->last_error ? '<div class="text-xs text-rose-400 mt-1">⚠️ ' . htmlspecialchars($sm->last_error) . '</div>' : '';

                                    $rowsHtml .= "
                                        <tr class='border-b border-gray-800/60'>
                                            <td class='py-2 px-3'>{$rank}</td>
                                            <td class='py-2 px-3 font-medium text-gray-200'>{$sourceName}</td>
                                            <td class='py-2 px-3'>{$statusBadge}</td>
                                            <td class='py-2 px-3 font-mono font-bold text-gray-300'>{$lastChap}</td>
                                            <td class='py-2 px-3 text-gray-400 text-xs'>{$lastCheck}{$errorMsg}</td>
                                        </tr>
                                    ";
                                }

                                $syncRoute = route('admin.scraper.sync', $sources->first());
                                $csrfToken = csrf_token();

                                return new \Illuminate\Support\HtmlString("
                                    <div class='p-4 rounded-xl bg-gray-900/70 border border-gray-700/60 space-y-3 text-sm'>
                                        <div class='flex items-center justify-between'>
                                            <span class='font-semibold text-gray-200 flex items-center gap-1.5'>
                                                <span>⚡</span> État en temps réel des sources & relais
                                            </span>
                                            <form method='POST' action='{$syncRoute}' class='inline'>
                                                <input type='hidden' name='_token' value='{$csrfToken}'>
                                                <button type='submit' class='px-3 py-1.5 text-xs font-medium rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white transition shadow'>
                                                    ↻ Déclencher le relais intelligent
                                                </button>
                                            </form>
                                        </div>
                                        <div class='overflow-x-auto'>
                                            <table class='w-full text-left text-xs'>
                                                <thead>
                                                    <tr class='text-gray-400 border-b border-gray-800 uppercase tracking-wider text-[11px]'>
                                                        <th class='py-2 px-3'>Priorité</th>
                                                        <th class='py-2 px-3'>Source</th>
                                                        <th class='py-2 px-3'>Statut</th>
                                                        <th class='py-2 px-3'>Dernier chap.</th>
                                                        <th class='py-2 px-3'>Dernière vérif.</th>
                                                    </tr>
                                                </thead>
                                                <tbody>{$rowsHtml}</tbody>
                                            </table>
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
