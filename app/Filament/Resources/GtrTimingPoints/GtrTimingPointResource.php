<?php

namespace App\Filament\Resources\GtrTimingPoints;

use App\Filament\Resources\GtrTimingPoints\Pages\CreateGtrTimingPoint;
use App\Filament\Resources\GtrTimingPoints\Pages\EditGtrTimingPoint;
use App\Filament\Resources\GtrTimingPoints\Pages\ListGtrTimingPoints;
use App\Filament\Resources\GtrTimingPoints\Schemas\GtrTimingPointForm;
use App\Filament\Resources\GtrTimingPoints\Tables\GtrTimingPointsTable;
use App\Models\GtrTimingPoint;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class GtrTimingPointResource extends Resource
{
    protected static ?string $model = GtrTimingPoint::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static string|\UnitEnum|null $navigationGroup = 'Race Timing';

    protected static ?string $navigationLabel = 'Titik Timing';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'code';

    public static function form(Schema $schema): Schema
    {
        return GtrTimingPointForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return GtrTimingPointsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGtrTimingPoints::route('/'),
            'create' => CreateGtrTimingPoint::route('/create'),
            'edit' => EditGtrTimingPoint::route('/{record}/edit'),
        ];
    }
}
