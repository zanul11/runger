<?php

namespace App\Filament\Resources\GtrTimingPoints\Tables;

use App\Models\GtrTimingPoint;
use Filament\Actions\DeleteAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class GtrTimingPointsTable
{
    public static function configure(Table $table): Table
    {
        $km = fn ($v) => $v === null ? '' : ' · km ' . rtrim(rtrim(number_format((float) $v, 2, ',', ''), '0'), ',');

        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['categories', 'assignments' => fn ($a) => $a->where('is_active', true)->with('user')])->withCount('scanLogs'))
            ->defaultSort('sort_order')
            ->paginated(false)
            ->description('Pos dibentuk otomatis dari "Titik di Rute" tiap kategori. Lokasi yang sama digabung menjadi satu pos, sehingga marshal di lokasi itu mencatat pelari semua kategori.')
            ->columns([
                TextColumn::make('code')
                    ->label('Kode')
                    ->badge(),
                TextColumn::make('name')
                    ->label('Lokasi')
                    ->weight('bold')
                    ->description(fn (GtrTimingPoint $r) => $r->location_key ? null : 'Pos lama (tidak terpakai kategori)'),
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
                TextColumn::make('kategori')
                    ->label('Dilewati kategori')
                    ->state(fn (GtrTimingPoint $r) => $r->categories->map(fn ($c) => $c->name . $km($c->pivot->km)
                        . ($c->pivot->is_mandatory ? '' : ' (opsional)')
                        . ($c->pivot->cutoff_at ? ' · COT ' . \Illuminate\Support\Carbon::parse($c->pivot->cutoff_at)->format('H:i') : ''))->all())
                    ->badge()
                    ->color('gray')
                    ->placeholder('—'),
                TextColumn::make('marshal')
                    ->label('Marshal bertugas')
                    ->state(fn (GtrTimingPoint $r) => $r->assignments->pluck('user.name')->filter()->all())
                    ->listWithLineBreaks()
                    ->placeholder('Belum ada'),
                TextColumn::make('scan_logs_count')
                    ->label('Scan')
                    ->numeric()
                    ->alignEnd(),
            ])
            ->recordActions([
                DeleteAction::make()
                    ->visible(fn (GtrTimingPoint $r) => $r->location_key === null)
                    ->requiresConfirmation()
                    ->modalDescription('Pos lama ini tidak dipakai kategori mana pun. Menghapusnya juga menghapus penugasan & scan yang menempel padanya.'),
            ]);
    }
}
