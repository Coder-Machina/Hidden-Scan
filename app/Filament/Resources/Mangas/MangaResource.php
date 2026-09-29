<?php

namespace App\Filament\Resources\Mangas;

use App\Filament\Resources\Mangas\Pages\CreateManga;
use App\Filament\Resources\Mangas\Pages\EditManga;
use App\Filament\Resources\Mangas\Pages\ListMangas;
use App\Filament\Resources\Mangas\Schemas\MangaForm;
use App\Filament\Resources\Mangas\Tables\MangasTable;
use App\Models\Manga;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class MangaResource extends Resource
{
    protected static ?string $model = Manga::class;

    protected static ?string $modelLabel = 'œuvre';

    protected static ?string $pluralModelLabel = 'œuvres';

    protected static ?string $navigationLabel = 'Œuvres';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'title';

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasAnyRole(['admin', 'Admin', 'owner', 'Owner', 'uploader', 'Uploader']) ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return MangaForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MangasTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            \App\Filament\Resources\Mangas\RelationManagers\ChaptersRelationManager::class,
            \App\Filament\Resources\Mangas\RelationManagers\ReportsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMangas::route('/'),
            'create' => CreateManga::route('/create'),
            'edit' => EditManga::route('/{record}/edit'),
        ];
    }
}
