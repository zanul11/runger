<?php

namespace App\Filament\Resources\GtrTimingPoints\Schemas;

use App\Models\GtrTimingPoint;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class GtrTimingPointForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // Event selalu GTR & kode digenerate otomatis dari tipe —
                // tidak ditampilkan/diinput. Lokasi (lat/long) juga tidak diperlukan.
                Select::make('type')
                    ->label('Tipe')
                    ->options(GtrTimingPoint::TYPES)
                    ->required()
                    ->native(false)
                    ->helperText('Kode dibuat otomatis: START / FINISH / CP1.. / WS1..'),
                TextInput::make('name')
                    ->label('Nama')
                    ->required()
                    ->placeholder('mis. Checkpoint 1 · Punggungan'),
                TextInput::make('sort_order')
                    ->label('Urutan')
                    ->numeric()
                    ->default(0),

                // Kategori yang melewati pos ini (boleh lebih dari satu).
                // Hanya peserta kategori inilah yang muncul di scanner pos ini.
                Select::make('categories')
                    ->label('Kategori')
                    ->relationship('categories', 'name')
                    ->multiple()
                    ->preload()
                    ->helperText('Pilih kategori yang melewati pos ini. Kosong = berlaku semua kategori.'),

                // Kode otomatis ditampilkan read-only saat edit (info saja).
                TextInput::make('code')
                    ->label('Kode (otomatis)')
                    ->disabled()
                    ->dehydrated(false)
                    ->visibleOn('edit'),
            ]);
    }
}
