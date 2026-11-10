<?php

namespace App\Filament\Resources\GtrRegistrations\RelationManagers;

use App\Support\GtrScanColumns;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/** Riwayat scan BIB seorang peserta: lokasi, waktu, petugas. */
class ScanLogsRelationManager extends RelationManager
{
    protected static string $relationship = 'scanLogs';

    protected static ?string $title = 'Riwayat Scan';

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        return (string) $ownerRecord->scanLogs()->count() ?: null;
    }

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['timingPoint', 'scanner']))
            ->defaultSort('scanned_at')
            ->emptyStateHeading('Belum ada scan')
            ->columns([
                GtrScanColumns::time(),
                GtrScanColumns::location(),
                GtrScanColumns::officer(),
                GtrScanColumns::source(),
                GtrScanColumns::flagged(),
            ]);
    }
}
