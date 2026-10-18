<?php

namespace App\Filament\Resources\ScraperSources;

use App\Filament\Resources\ScraperSources\Pages\CreateScraperSource;
use App\Filament\Resources\ScraperSources\Pages\EditScraperSource;
use App\Filament\Resources\ScraperSources\Pages\ListScraperSources;
use App\Filament\Resources\ScraperSources\Schemas\ScraperSourceForm;
use App\Filament\Resources\ScraperSources\Tables\ScraperSourcesTable;
use App\Models\ScraperSource;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ScraperSourceResource extends Resource
{
    protected static ?string $model = ScraperSource::class;

    protected static ?string $modelLabel = 'source de scraping';

    protected static ?string $pluralModelLabel = 'sources de scraping';

    protected static ?string $navigationLabel = 'Sources de Scraping';

    protected static string|\UnitEnum|null $navigationGroup = 'Catalogue';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGlobeAlt;

    protected static ?int $navigationSort = 6;

    protected static ?string $recordTitleAttribute = 'name';

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasAnyRole(['admin', 'Admin', 'uploader', 'Uploader']) ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return ScraperSourceForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ScraperSourcesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListScraperSources::route('/'),
            'create' => CreateScraperSource::route('/create'),
            'edit'   => EditScraperSource::route('/{record}/edit'),
        ];
    }
}
