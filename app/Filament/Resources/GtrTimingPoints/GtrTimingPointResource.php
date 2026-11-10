<?php

namespace App\Filament\Resources\GtrTimingPoints;

use App\Filament\Resources\GtrTimingPoints\Pages\ListGtrTimingPoints;
use App\Filament\Resources\GtrTimingPoints\Tables\GtrTimingPointsTable;
use App\Models\GtrTimingPoint;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Support\Icons\Heroicon;

/**
 * Titik timing (pos scan BIB) — dibentuk otomatis dari "Titik di Rute" tiap kategori
 * (lihat App\Services\GtrTimingSync). Di sini hanya dilihat & disinkronkan, tidak dibuat manual.
 */
class GtrTimingPointResource extends Resource
{
    protected static ?string $model = GtrTimingPoint::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static string|\UnitEnum|null $navigationGroup = 'Race Timing';

    protected static ?string $navigationLabel = 'Titik Timing';

    protected static ?string $modelLabel = 'Titik Timing';

    protected static ?string $pluralModelLabel = 'Titik Timing';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return GtrTimingPointsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGtrTimingPoints::route('/'),
        ];
    }
}
