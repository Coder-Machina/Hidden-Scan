<?php

namespace App\Filament\Resources\ScraperSources\Schemas;

use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ScraperSourceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informations du site source')
                    ->description('Renseignez les données principales du site à scraper. La détection des chapitres et des images est 100% automatique.')
                    ->icon('heroicon-o-globe-alt')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Nom de la source')
                            ->placeholder('Ex: Aniverse, Poseidon, Asura...')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('base_url')
                            ->label('URL racine du site')
                            ->placeholder('https://aniverse.fr')
                            ->url()
                            ->required()
                            ->maxLength(255),
                        Toggle::make('active')
                            ->label('Source active')
                            ->helperText('Active la surveillance et l\'import automatique pour cette source.')
                            ->default(true),
                    ]),

                Section::make('Paramètres avancés — Sélecteurs CSS (Optionnel)')
                    ->description('Laissez vide pour la détection automatique intelligente (WordPress Madara, Next.js, MangaStream, HTML standard...). Renseignez uniquement si vous souhaitez forcer un sélecteur particulier.')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->collapsed()
                    ->columns(2)
                    ->schema([
                        TextInput::make('sel_chapter_list')
                            ->label('Conteneur de chaque chapitre')
                            ->placeholder('Détection automatique')
                            ->nullable()
                            ->helperText('Ex: .wp-manga-chapter, li.chapter-item, etc.'),
                        TextInput::make('sel_chapter_title')
                            ->label('Titre du chapitre')
                            ->placeholder('Détection automatique')
                            ->nullable()
                            ->helperText('Ex: .wp-manga-chapter a, a.chapter-title'),
                        TextInput::make('sel_chapter_url')
                            ->label('Lien vers le chapitre')
                            ->placeholder('Détection automatique')
                            ->nullable()
                            ->helperText('Ex: .wp-manga-chapter a, a[href*="chapter"]'),
                        TextInput::make('sel_chapter_date')
                            ->label('Date de publication')
                            ->placeholder('Détection automatique')
                            ->nullable(),
                        TextInput::make('sel_chapter_pages')
                            ->label('Images des pages du chapitre')
                            ->placeholder('Détection automatique')
                            ->nullable()
                            ->columnSpanFull()
                            ->helperText('Ex: .page-break img, #readerarea img (les CDN et images brutes sont aussi auto-détectés)'),
                    ]),

                Section::make('En-têtes HTTP & Anti-Bot (Optionnel)')
                    ->description('Configurez les en-têtes HTTP (User-Agent, Referer...) pour contourner d\'éventuels blocages.')
                    ->icon('heroicon-o-shield-check')
                    ->collapsed()
                    ->schema([
                        KeyValue::make('headers')
                            ->label('En-têtes HTTP')
                            ->keyLabel('En-tête (Header)')
                            ->valueLabel('Valeur')
                            ->default([
                                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                            ])
                            ->helperText('Indiquez par exemple Referer, Cookie ou User-Agent.'),
                    ]),
            ]);
    }
}
