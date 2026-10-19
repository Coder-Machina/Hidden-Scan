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
                                    ->createOptionUsing(function (array $data): int {
                                        $source = \App\Models\ScraperSource::create([
                                            'name'              => $data['name'],
                                            'base_url'          => $data['base_url'],
                                            'sel_chapter_list'  => $data['sel_chapter_list'] ?? '.wp-manga-chapter',
                                            'sel_chapter_title' => $data['sel_chapter_title'] ?? '.wp-manga-chapter a',
                                            'sel_chapter_url'   => $data['sel_chapter_url'] ?? '.wp-manga-chapter a',
                                            'sel_chapter_pages' => $data['sel_chapter_pages'] ?? '.page-break img',
                                            'sel_chapter_date'  => '.chapter-release-date',
                                            'headers'           => [
                                                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                                            ],
                                            'active'            => true,
                                        ]);

                                        return $source->id;
                                    })
                                    ->helperText('Choisissez un site existant ou cliquez sur « + » pour en ajouter un nouveau.')
                                    ->columnSpan(['default' => 12, 'md' => 8]),
                                Select::make('status')
                                    ->label('Statut de cette source')
                                    ->options([
                                        'active' => '🟢 Actif (Prêt pour relais)',
                                        'paused' => '⏸️ En pause (Désactivé)',
                                    ])
                                    ->default('active')
                                    ->required()
                                    ->columnSpan(['default' => 12, 'md' => 4]),
                                TextInput::make('source_url')
                                    ->label('URL du manga sur ce site')
                                    ->placeholder('https://poseidon-scans.net/serie/nom-du-manga')
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
                                    ->helperText('Collez l\'adresse complète de la page œuvre (ex: https://poseidon-scans.net/serie/omniscient-readers-viewpoint).')
                                    ->columnSpanFull(),
                            ])
                            ->columns(12)
                            ->reorderable()
                            ->collapsible()
                            ->cloneable()
                            ->addActionLabel('+ Ajouter une source supplémentaire (Relais de secours)')
                            ->helperText('Glissez-déposez les cartes pour définir l\'ordre de priorité : la 1ère source est principale (#1), la 2ème prend le relais si besoin (#2), etc.'),

                        Placeholder::make('scraper_multi_status')
                            ->label('')
                            ->visible(fn ($record) => $record && $record->scraperMangas()->exists())
                            ->columnSpanFull()
                            ->content(function ($record) {
                                if (!$record) return null;
                                $sources = $record->scraperMangas()->with('scraperSource')->orderBy('priority', 'asc')->get();
                                if ($sources->isEmpty()) return null;

                                $totalChapters = $record->chapters()->count();
                                $rowsHtml = '';
                                foreach ($sources as $index => $sm) {
                                    $isPrimary = ($index === 0);
                                    $rankBadge = $isPrimary
                                        ? '<span style="display:inline-flex;align-items:center;gap:4px;background:rgba(245,158,11,0.18);color:#fbbf24;border:1px solid rgba(245,158,11,0.35);padding:3px 10px;border-radius:6px;font-weight:700;font-size:11px;letter-spacing:0.02em;">★ #1 Principale</span>'
                                        : '<span style="display:inline-flex;align-items:center;gap:4px;background:rgba(59,130,246,0.15);color:#60a5fa;border:1px solid rgba(59,130,246,0.3);padding:3px 10px;border-radius:6px;font-weight:600;font-size:11px;">#' . ($index + 1) . ' Relais secours</span>';

                                    $statusBadge = match($sm->status) {
                                        'active' => '<span style="display:inline-flex;align-items:center;gap:5px;background:rgba(16,185,129,0.15);color:#34d399;border:1px solid rgba(16,185,129,0.35);padding:3px 9px;border-radius:6px;font-weight:600;font-size:11px;"><span style="width:6px;height:6px;border-radius:50%;background:#34d399;display:inline-block;"></span> Actif</span>',
                                        'paused' => '<span style="display:inline-flex;align-items:center;gap:5px;background:rgba(148,163,184,0.15);color:#94a3b8;border:1px solid rgba(148,163,184,0.25);padding:3px 9px;border-radius:6px;font-size:11px;">⏸️ En pause</span>',
                                        'error'  => '<span style="display:inline-flex;align-items:center;gap:5px;background:rgba(239,68,68,0.15);color:#f87171;border:1px solid rgba(239,68,68,0.35);padding:3px 9px;border-radius:6px;font-size:11px;">⚠️ En erreur</span>',
                                        default  => ucfirst($sm->status),
                                    };

                                    $sourceName = htmlspecialchars($sm->scraperSource?->name ?? 'Source inconnue');
                                    $domain = parse_url($sm->source_url, PHP_URL_HOST) ?? '';
                                    $lastChap = $sm->last_chapter_scraped ? ('Chapitre ' . htmlspecialchars($sm->last_chapter_scraped)) : '<span style="color:#64748b;font-style:italic;">En attente</span>';
                                    $lastCheck = $sm->last_checked_at ? $sm->last_checked_at->diffForHumans() : '<span style="color:#64748b;">Jamais</span>';
                                    $errorBox = $sm->last_error ? '<div style="color:#f87171;background:rgba(239,68,68,0.08);border-left:2px solid #ef4444;padding:4px 8px;border-radius:4px;font-size:11px;margin-top:6px;word-break:break-word;">⚠️ ' . htmlspecialchars($sm->last_error) . '</div>' : '';

                                    $bgRow = ($index % 2 === 0) ? '#0f172a' : '#131d33';

                                    $rowsHtml .= "
                                        <tr style='background:{$bgRow};border-bottom:1px solid #1e293b;transition:background 0.15s;'>
                                            <td style='padding:12px 16px;vertical-align:middle;white-space:nowrap;'>{$rankBadge}</td>
                                            <td style='padding:12px 16px;vertical-align:middle;'>
                                                <div style='color:#f8fafc;font-weight:600;font-size:13px;'>{$sourceName}</div>
                                                <div style='color:#64748b;font-size:11px;margin-top:2px;font-family:monospace;'>{$domain}</div>
                                            </td>
                                            <td style='padding:12px 16px;vertical-align:middle;white-space:nowrap;'>{$statusBadge}</td>
                                            <td style='padding:12px 16px;vertical-align:middle;white-space:nowrap;'>
                                                <span style='background:#1e293b;color:#38bdf8;border:1px solid #334155;padding:3px 9px;border-radius:6px;font-family:monospace;font-weight:700;font-size:12px;'>{$lastChap}</span>
                                            </td>
                                            <td style='padding:12px 16px;vertical-align:middle;'>
                                                <div style='color:#94a3b8;font-size:12px;'>{$lastCheck}</div>
                                                {$errorBox}
                                            </td>
                                        </tr>
                                    ";
                                }

                                $syncRoute = route('admin.scraper.sync', $sources->first());
                                $csrfToken = csrf_token();

                                return new \Illuminate\Support\HtmlString("
                                    <div style='background:#0b1120;border:1px solid #1e293b;border-radius:12px;padding:20px;margin-top:8px;box-shadow:0 4px 16px rgba(0,0,0,0.3);'>
                                        <!-- Entête du tableau de bord -->
                                        <div style='display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:14px;margin-bottom:16px;padding-bottom:14px;border-bottom:1px solid #1e293b;'>
                                            <div style='display:flex;align-items:center;gap:12px;'>
                                                <div style='background:linear-gradient(135deg,rgba(245,158,11,0.2),rgba(217,119,6,0.3));border:1px solid rgba(245,158,11,0.4);border-radius:10px;width:38px;height:38px;display:inline-flex;align-items:center;justify-content:center;font-size:18px;'>
                                                    ⚡
                                                </div>
                                                <div>
                                                    <div style='color:#f8fafc;font-weight:700;font-size:15px;display:flex;align-items:center;gap:8px;'>
                                                        Tableau de bord du relais multi-sources
                                                        <span style='background:rgba(16,185,129,0.15);color:#34d399;border:1px solid rgba(16,185,129,0.3);border-radius:9999px;font-size:10px;font-weight:700;padding:2px 8px;text-transform:uppercase;'>Actif</span>
                                                        <span style='background:rgba(56,189,248,0.12);color:#38bdf8;border:1px solid rgba(56,189,248,0.25);border-radius:9999px;font-size:11px;font-weight:600;padding:2px 8px;'>{$totalChapters} chapitres en ligne</span>
                                                    </div>
                                                    <div style='color:#94a3b8;font-size:12px;margin-top:2px;'>
                                                        Surveillance en continu : si la source principale est en retard ou en panne, le relais de secours prend la main immédiatement.
                                                    </div>
                                                </div>
                                            </div>

                                            <form method='POST' action='{$syncRoute}' style='margin:0;'>
                                                <input type='hidden' name='_token' value='{$csrfToken}'>
                                                <button type='submit' style='display:inline-flex;align-items:center;gap:8px;background:linear-gradient(135deg,#4f46e5,#4338ca);color:#ffffff;border:1px solid #6366f1;border-radius:8px;padding:9px 18px;font-size:12px;font-weight:600;cursor:pointer;transition:all 0.2s;box-shadow:0 2px 8px rgba(79,70,229,0.4);'>
                                                    <span style='font-size:14px;'>↻</span> Déclencher le relais intelligent
                                                </button>
                                            </form>
                                        </div>

                                        <!-- Table stylisée avec CSS inline protégé du purge -->
                                        <div style='overflow-x:auto;border-radius:8px;border:1px solid #1e293b;'>
                                            <table style='width:100%;border-collapse:collapse;text-align:left;font-size:12px;min-width:620px;'>
                                                <thead>
                                                    <tr style='background:#1e293b;color:#94a3b8;text-transform:uppercase;font-size:11px;font-weight:700;letter-spacing:0.05em;border-bottom:1px solid #334155;'>
                                                        <th style='padding:11px 16px;'>Priorité</th>
                                                        <th style='padding:11px 16px;'>Site Source</th>
                                                        <th style='padding:11px 16px;'>Statut</th>
                                                        <th style='padding:11px 16px;'>Dernier Chapitre</th>
                                                        <th style='padding:11px 16px;'>Dernière Vérif.</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    {$rowsHtml}
                                                </tbody>
                                            </table>
                                        </div>

                                        <!-- Explication claire et rassurante -->
                                        <div style='margin-top:14px;padding:12px 16px;background:rgba(30,41,59,0.5);border:1px solid #1e293b;border-radius:8px;color:#94a3b8;font-size:12px;line-height:1.5;display:flex;align-items:flex-start;gap:10px;'>
                                            <span style='font-size:16px;'>💡</span>
                                            <div>
                                                <strong style='color:#e2e8f0;'>Principe du relais intelligent :</strong>
                                                Le système cherche d'abord les nouveaux chapitres sur votre source #1. Si un chapitre est absent, incomplet ou si le site principal ne répond pas, il bascule automatiquement et de manière transparente sur les sources de relais (#2, #3...) pour que les nouveaux chapitres soient importés sans jamais bloquer votre site.
                                            </div>
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
