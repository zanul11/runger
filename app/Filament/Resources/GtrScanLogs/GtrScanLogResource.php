<?php

namespace App\Filament\Resources\GtrScanLogs;

use App\Filament\Resources\GtrScanLogs\Pages\ListGtrScanLogs;
use App\Models\GtrCategory;
use App\Models\GtrScanLog;
use App\Models\GtrTimingPoint;
use App\Models\User;
use App\Services\GtrTimingAdmin;
use App\Support\GtrScanColumns;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Hasil scan BIB: siapa (peserta), di mana (pos), kapan, dan oleh siapa (petugas).
 * Log append-only: tidak bisa dibuat/dihapus; admin hanya bisa mengabaikan, memulihkan,
 * atau memperbaiki BIB salah ketik (lihat GtrTimingAdmin).
 */
class GtrScanLogResource extends Resource
{
    protected static ?string $model = GtrScanLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQrCode;

    protected static string|\UnitEnum|null $navigationGroup = 'Race Timing';

    protected static ?string $navigationLabel = 'Hasil Scan';

    protected static ?string $modelLabel = 'Hasil Scan';

    protected static ?string $pluralModelLabel = 'Hasil Scan';

    protected static ?int $navigationSort = 3;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['registration.category', 'timingPoint', 'scanner']))
            ->defaultSort('scanned_at', 'desc')
            ->poll('15s')
            ->columns([
                GtrScanColumns::time(),
                TextColumn::make('registration.bib_number')
                    ->label('BIB')
                    ->weight('bold')
                    ->placeholder('—')
                    ->description(fn (GtrScanLog $r) => $r->bib_input !== null && trim($r->bib_input) !== (string) $r->registration?->bib_number ? 'input: '.$r->bib_input : null)
                    ->searchable(query: fn (Builder $query, string $search) => $query->where(fn ($q) => $q
                        ->where('bib_input', 'like', "%{$search}%")
                        ->orWhereHas('registration', fn ($r) => $r->where('bib_number', 'like', "%{$search}%")->orWhere('full_name', 'like', "%{$search}%")))),
                TextColumn::make('registration.full_name')
                    ->label('Peserta')
                    ->placeholder('— tidak terdaftar')
                    ->url(fn (GtrScanLog $r) => $r->registration ? route('filament.admin.resources.gtr-registrations.view', $r->registration) : null),
                TextColumn::make('registration.category.name')
                    ->label('Kategori')
                    ->badge()
                    ->color('gray'),
                GtrScanColumns::location(),
                GtrScanColumns::officer(),
                GtrScanColumns::source(),
                GtrScanColumns::flagged(),
                TextColumn::make('ignore_reason')
                    ->label('Status')
                    ->badge()
                    ->state(fn (GtrScanLog $r) => $r->is_ignored ? ($r->ignore_reason ?? GtrScanLog::REASON_ADMIN) : 'valid')
                    ->formatStateUsing(fn (string $state) => $state === 'valid' ? 'Dihitung' : (GtrScanLog::REASONS[$state] ?? $state))
                    ->color(fn (string $state) => match ($state) {
                        'valid' => 'success',
                        GtrScanLog::REASON_DUPLICATE, GtrScanLog::REASON_CANCELLED => 'gray',
                        default => 'danger',
                    }),
                TextColumn::make('server_received_at')
                    ->label('Diterima server')
                    ->dateTime('H:i:s')
                    ->description(fn (GtrScanLog $r) => $r->clock_offset_ms ? 'offset '.$r->clock_offset_ms.' ms' : null)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                Action::make('ignore')
                    ->label('Abaikan')
                    ->icon(Heroicon::OutlinedEyeSlash)
                    ->color('danger')
                    ->visible(fn (GtrScanLog $r) => ! $r->is_ignored)
                    ->requiresConfirmation()
                    ->modalDescription('Scan tidak dihapus, hanya tidak dihitung. Hasil kategori dihitung ulang.')
                    ->action(function (GtrScanLog $record) {
                        app(GtrTimingAdmin::class)->ignoreScan($record, auth()->user());
                        Notification::make()->title('Scan diabaikan')->success()->send();
                    }),
                Action::make('restore')
                    ->label('Pulihkan')
                    ->icon(Heroicon::OutlinedArrowUturnLeft)
                    ->color('success')
                    ->visible(fn (GtrScanLog $r) => $r->is_ignored && $r->gtr_registration_id && $r->ignore_reason !== GtrScanLog::REASON_DUPLICATE)
                    ->requiresConfirmation()
                    ->action(function (GtrScanLog $record) {
                        app(GtrTimingAdmin::class)->restoreScan($record);
                        $record->refresh();
                        Notification::make()
                            ->title($record->is_ignored ? 'Dipulihkan, tapi tetap duplikat (< 60 dtk dari scan lain)' : 'Scan dihitung kembali')
                            ->success()->send();
                    }),
                Action::make('relink')
                    ->label('Perbaiki BIB')
                    ->icon(Heroicon::OutlinedPencil)
                    ->color('warning')
                    ->visible(fn (GtrScanLog $r) => in_array($r->ignore_reason, [GtrScanLog::REASON_UNKNOWN_BIB, GtrScanLog::REASON_INVALID_BIB, GtrScanLog::REASON_WRONG_CATEGORY], true))
                    ->modalDescription(fn (GtrScanLog $r) => 'BIB yang diketik marshal: '.($r->bib_input ?? '—').'. Isi BIB yang benar; scan akan dihitung untuk peserta itu.')
                    ->schema([
                        TextInput::make('bib')->label('BIB yang benar')->required()->maxLength(12),
                    ])
                    ->action(function (GtrScanLog $record, array $data, Action $action) {
                        try {
                            $reg = app(GtrTimingAdmin::class)->relinkScan($record, $data['bib']);
                        } catch (\InvalidArgumentException $e) {
                            Notification::make()->title($e->getMessage())->danger()->send();
                            $action->halt();

                            return;
                        }
                        Notification::make()->title("Scan dihubungkan ke {$reg->bib_number} · {$reg->full_name}")->success()->send();
                    }),
            ])
            ->filters([
                SelectFilter::make('gtr_timing_point_id')
                    ->label('Lokasi')
                    ->options(fn () => GtrTimingPoint::orderBy('sort_order')->get()->mapWithKeys(fn ($tp) => [$tp->id => "{$tp->code} · {$tp->name}"])->all()),
                SelectFilter::make('kategori')
                    ->label('Kategori')
                    ->options(fn () => GtrCategory::orderBy('sort_order')->pluck('name', 'id')->all())
                    ->query(fn (Builder $query, array $data) => $data['value'] ? $query->whereHas('registration', fn ($r) => $r->where('gtr_category_id', $data['value'])) : $query),
                SelectFilter::make('scanned_by')
                    ->label('Petugas')
                    ->options(fn () => User::whereIn('id', GtrScanLog::whereNotNull('scanned_by')->distinct()->pluck('scanned_by'))->orderBy('name')->pluck('name', 'id')->all()),
                SelectFilter::make('source')
                    ->label('Sumber')
                    ->options(GtrScanColumns::SOURCES),
                TernaryFilter::make('is_flagged')
                    ->label('Ditandai'),
                TernaryFilter::make('is_ignored')
                    ->label('Dihitung')
                    ->trueLabel('Hanya yang diabaikan')
                    ->falseLabel('Hanya yang dihitung')
                    ->queries(
                        true: fn (Builder $query) => $query->where('is_ignored', true),
                        false: fn (Builder $query) => $query->where('is_ignored', false),
                        blank: fn (Builder $query) => $query,
                    ),
                SelectFilter::make('ignore_reason')
                    ->label('Alasan diabaikan')
                    ->options(GtrScanLog::REASONS),
                SelectFilter::make('device_id')
                    ->label('Perangkat')
                    ->options(fn () => GtrScanLog::whereNotNull('device_id')->distinct()->pluck('device_id', 'device_id')->map(fn ($d) => substr($d, 0, 8))->all()),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGtrScanLogs::route('/'),
        ];
    }
}
