<?php

namespace App\Filament\Resources\GtrCategories\RelationManagers;

use App\Models\GtrTimingPoint;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Pos scan BIB yang dilewati kategori ini. Baca-saja: dibentuk otomatis dari "Titik di Rute"
 * (App\Services\GtrTimingSync) setiap kali kategori disimpan.
 */
class TimingPointsRelationManager extends RelationManager
{
    protected static string $relationship = 'timingPoints';

    protected static ?string $title = 'Pos Scan BIB';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->defaultSort('gtr_category_timing_point.sequence')
            ->paginated(false)
            ->description('Otomatis dari "Titik di Rute" + Start & Finish. Ubah titik di rute lalu simpan kategori; pos ikut menyesuaikan.')
            ->columns([
                TextColumn::make('pivot.sequence')->label('#'),
                TextColumn::make('code')->label('Kode')->badge(),
                TextColumn::make('name')->label('Lokasi'),
                TextColumn::make('pivot.km')->label('KM')->placeholder('—'),
                TextColumn::make('type')
                    ->label('Jenis')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => GtrTimingPoint::TYPES[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        'start' => 'success',
                        'finish' => 'danger',
                        'water_station' => 'info',
                        default => 'warning',
                    }),
                IconColumn::make('pivot.is_mandatory')->label('Wajib')->boolean(),
                TextColumn::make('pivot.cutoff_at')->label('Cut-off')->dateTime('d M H:i')->placeholder('—'),
            ]);
    }
}
