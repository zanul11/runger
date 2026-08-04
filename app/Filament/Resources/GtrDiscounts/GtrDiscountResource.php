<?php

namespace App\Filament\Resources\GtrDiscounts;

use App\Filament\Resources\GtrDiscounts\Pages\CreateGtrDiscount;
use App\Filament\Resources\GtrDiscounts\Pages\EditGtrDiscount;
use App\Filament\Resources\GtrDiscounts\Pages\ListGtrDiscounts;
use App\Filament\Resources\GtrDiscounts\Schemas\GtrDiscountForm;
use App\Filament\Resources\GtrDiscounts\Tables\GtrDiscountsTable;
use App\Models\GtrDiscount;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class GtrDiscountResource extends Resource
{
    protected static ?string $model = GtrDiscount::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTicket;

    protected static string|\UnitEnum|null $navigationGroup = 'GTR';

    protected static ?string $navigationLabel = 'Diskon / Voucher';

    protected static ?string $modelLabel = 'Diskon';

    protected static ?string $pluralModelLabel = 'Diskon';

    protected static ?int $navigationSort = 7;

    protected static ?string $recordTitleAttribute = 'code';

    public static function form(Schema $schema): Schema
    {
        return GtrDiscountForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return GtrDiscountsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            \App\Filament\Resources\GtrDiscounts\RelationManagers\RegistrationsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGtrDiscounts::route('/'),
            'create' => CreateGtrDiscount::route('/create'),
            'edit' => EditGtrDiscount::route('/{record}/edit'),
        ];
    }
}
