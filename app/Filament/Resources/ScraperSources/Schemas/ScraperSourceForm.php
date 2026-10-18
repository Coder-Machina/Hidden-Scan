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
                    ->description('Renseignez les données principales du site à scraper.')
                    ->icon('heroicon-o-globe-alt')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Nom de la source')
                            ->placeholder('Ex: Poseidon Scan, Asura Scan, Flame Comics...')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('base_url')
                            ->label('URL racine du site')
                            ->placeholder('https://poseidon-scan.net')
                            ->url()
                            ->required()
                            ->maxLength(255),
                        Toggle::make('active')
                            ->label('Source active')
                            ->helperText('Si désactivée, cette source ne sera pas interrogée lors des synchronisations automatiques.')
                            ->default(true),
                    ]),

                Section::make('Sélecteurs CSS — Liste des Chapitres')
                    ->description('Sélecteurs CSS pour extraire la liste des chapitres sur la page de présentation d\'un manga.')
                    ->icon('heroicon-o-code-bracket')
                    ->columns(2)
                    ->schema([
                        TextInput::make('sel_chapter_list')
                            ->label('Conteneur de chaque chapitre')
                            ->placeholder('.wp-manga-chapter')
                            ->default('.wp-manga-chapter')
                            ->required()
                            ->helperText('Exemple WordPress Madara : .wp-manga-chapter | MangaStream : li.chapter-item'),
                        TextInput::make('sel_chapter_title')
                            ->label('Titre du chapitre')
                            ->placeholder('.wp-manga-chapter a')
                            ->default('.wp-manga-chapter a')
                            ->required()
                            ->helperText('Élément contenant le texte du chapitre (ex: .wp-manga-chapter a)'),
                        TextInput::make('sel_chapter_url')
                            ->label('Lien vers le chapitre')
                            ->placeholder('.wp-manga-chapter a')
                            ->default('.wp-manga-chapter a')
                            ->required()
                            ->helperText('Balise lien <a href="..."> vers la page de lecture'),
                        TextInput::make('sel_chapter_date')
                            ->label('Date de publication (Optionnel)')
                            ->placeholder('.chapter-release-date')
                            ->default('.chapter-release-date')
                            ->helperText('Exemple : .chapter-release-date ou .chapterdate'),
                    ]),

                Section::make('Sélecteur CSS — Pages du Lecteur')
                    ->description('Sélecteur CSS pour extraire toutes les images de pages dans le lecteur.')
                    ->icon('heroicon-o-photo')
                    ->schema([
                        TextInput::make('sel_chapter_pages')
                            ->label('Images des pages du chapitre')
                            ->placeholder('.page-break img')
                            ->default('.page-break img')
                            ->required()
                            ->helperText('Exemple : .page-break img (supporte automatiquement data-src, data-lazy-src et src)'),
                    ]),

                Section::make('En-têtes HTTP (Headers & Anti-Bot)')
                    ->description('Configurez les en-têtes HTTP (User-Agent, Referer...) pour contourner d\'éventuels blocages.')
                    ->icon('heroicon-o-shield-check')
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
