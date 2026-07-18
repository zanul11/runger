<?php

namespace App\Filament\Resources\GtrSponsors;

use App\Filament\Resources\GtrSponsors\Pages\CreateGtrSponsor;
use App\Filament\Resources\GtrSponsors\Pages\EditGtrSponsor;
use App\Filament\Resources\GtrSponsors\Pages\ListGtrSponsors;
use App\Filament\Resources\GtrSponsors\Schemas\GtrSponsorForm;
use App\Filament\Resources\GtrSponsors\Tables\GtrSponsorsTable;
use App\Models\GtrSponsor;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class GtrSponsorResource extends Resource
{
    protected static ?string $model = GtrSponsor::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static string|\UnitEnum|null $navigationGroup = 'GTR';

    protected static ?string $navigationLabel = 'Sponsor';

    protected static ?int $navigationSort = 6;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return GtrSponsorForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return GtrSponsorsTable::configure($table);
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
            'index' => ListGtrSponsors::route('/'),
            'create' => CreateGtrSponsor::route('/create'),
            'edit' => EditGtrSponsor::route('/{record}/edit'),
        ];
    }
}
