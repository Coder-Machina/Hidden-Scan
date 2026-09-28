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
                            ->label('Genres'),
                        Select::make('tags')
                            ->relationship('tags', 'name')
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->label('Tags'),
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
                            ->disk('public')
                            ->directory('mangas/covers')
                            ->imageResizeMode('cover')
                            ->imageCropAspectRatio('2:3')
                            ->imageResizeTargetWidth('400')
                            ->imageResizeTargetHeight('600'),
                        FileUpload::make('banner_image')
                            ->label('Bannière')
                            ->image()
                            ->disk('public')
                            ->directory('mangas/banners')
                            ->imageResizeMode('cover')
                            ->imageResizeTargetWidth('1200')
                            ->imageResizeTargetHeight('400'),
                    ]),

                Section::make('Options')
                    ->description('Paramètres avancés.')
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
