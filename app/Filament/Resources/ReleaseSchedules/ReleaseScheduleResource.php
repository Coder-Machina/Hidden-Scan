<?php

namespace App\Filament\Resources\ReleaseSchedules;

use App\Filament\Resources\ReleaseSchedules\Pages\CreateReleaseSchedule;
use App\Filament\Resources\ReleaseSchedules\Pages\EditReleaseSchedule;
use App\Filament\Resources\ReleaseSchedules\Pages\ListReleaseSchedules;
use App\Filament\Resources\ReleaseSchedules\Schemas\ReleaseScheduleForm;
use App\Filament\Resources\ReleaseSchedules\Tables\ReleaseSchedulesTable;
use App\Models\ReleaseSchedule;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ReleaseScheduleResource extends Resource
{
    protected static ?string $model = ReleaseSchedule::class;

    protected static ?string $modelLabel = 'sortie au planning';

    protected static ?string $pluralModelLabel = 'planning des sorties';

    protected static ?string $navigationLabel = 'Planning des Sorties';

    protected static string|\UnitEnum|null $navigationGroup = 'Catalogue';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?int $navigationSort = 5;

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasAnyRole(['admin', 'Admin', 'owner', 'Owner', 'uploader', 'Uploader']) ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return ReleaseScheduleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ReleaseSchedulesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListReleaseSchedules::route('/'),
            'create' => CreateReleaseSchedule::route('/create'),
            'edit' => EditReleaseSchedule::route('/{record}/edit'),
        ];
    }
}
