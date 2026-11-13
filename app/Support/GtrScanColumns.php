<?php

namespace App\Support;

use App\Models\GtrScanLog;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;

/** Kolom bersama untuk daftar hasil scan (menu Hasil Scan & riwayat per peserta). */
class GtrScanColumns
{
    public const SOURCES = [
        GtrScanLog::SOURCE_QR => 'Scan QR',
        GtrScanLog::SOURCE_MANUAL => 'Input manual',
        GtrScanLog::SOURCE_VIDEO => 'Video',
        GtrScanLog::SOURCE_ADMIN => 'Koreksi admin',
    ];

    public static function time(): TextColumn
    {
        return TextColumn::make('scanned_at')
            ->label('Waktu')
            ->dateTime('d M Y · H:i:s.v')
            ->sortable();
    }

    public static function location(): TextColumn
    {
        return TextColumn::make('timingPoint.name')
            ->label('Lokasi')
            ->description(fn (GtrScanLog $r) => $r->timingPoint?->code)
            ->placeholder('—');
    }

    public static function officer(): TextColumn
    {
        return TextColumn::make('scanner.name')
            ->label('Petugas')
            ->placeholder('—')
            ->description(fn (GtrScanLog $r) => $r->scanner ? null : ($r->device_id ? 'Perangkat ' . $r->device_id : null));
    }

    public static function source(): TextColumn
    {
        return TextColumn::make('source')
            ->label('Sumber')
            ->badge()
            ->formatStateUsing(fn (?string $state) => self::SOURCES[$state] ?? $state)
            ->color(fn (?string $state) => $state === GtrScanLog::SOURCE_QR ? 'success' : 'gray');
    }

    public static function flagged(): IconColumn
    {
        return IconColumn::make('is_flagged')
            ->label('Ditandai')
            ->boolean()
            ->trueIcon('heroicon-o-flag')
            ->falseIcon('')
            ->trueColor('warning')
            ->tooltip(fn (GtrScanLog $r) => $r->is_flagged ? 'Perlu dicek (QR tidak dikenal / waktu janggal)' : null);
    }
}
