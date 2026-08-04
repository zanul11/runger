<?php

namespace App\Filament\Resources\GtrDiscounts\Schemas;

use App\Models\GtrDiscount;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class GtrDiscountForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nama Diskon')
                    ->placeholder('mis. Early Squad Runger')
                    ->required()
                    ->maxLength(191),

                TextInput::make('code')
                    ->label('Kode Diskon')
                    ->placeholder('mis. RUNGER25')
                    ->required()
                    ->maxLength(50)
                    ->unique(ignoreRecord: true)
                    ->helperText('Huruf/angka tanpa spasi. Otomatis huruf besar.')
                    ->dehydrateStateUsing(fn (?string $state) => mb_strtoupper(trim((string) $state)))
                    ->suffixAction(
                        \Filament\Actions\Action::make('gen')
                            ->icon('heroicon-o-sparkles')
                            ->label('Acak')
                            ->action(fn (callable $set) => $set('code', 'GTR' . strtoupper(Str::random(5)))),
                    ),

                Select::make('type')
                    ->label('Jenis Potongan')
                    ->options(GtrDiscount::TYPES)
                    ->default(GtrDiscount::TYPE_FIXED)
                    ->native(false)
                    ->required()
                    ->live(),

                TextInput::make('value')
                    ->label(fn (callable $get) => $get('type') === GtrDiscount::TYPE_PERCENT ? 'Nilai (%)' : 'Nilai (IDR)')
                    ->numeric()
                    ->required()
                    ->minValue(1)
                    ->maxValue(fn (callable $get) => $get('type') === GtrDiscount::TYPE_PERCENT ? 100 : null)
                    ->prefix(fn (callable $get) => $get('type') === GtrDiscount::TYPE_PERCENT ? null : 'IDR')
                    ->suffix(fn (callable $get) => $get('type') === GtrDiscount::TYPE_PERCENT ? '%' : null),

                TextInput::make('quota')
                    ->label('Jumlah Pemakaian (Kuota)')
                    ->numeric()
                    ->minValue(1)
                    ->helperText('Berapa kali kode ini boleh dipakai. Kosongkan = tak terbatas.'),

                Toggle::make('is_active')
                    ->label('Aktif')
                    ->default(true),
            ]);
    }
}
