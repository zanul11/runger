<?php

namespace App\Filament\Resources\GtrResultAdjustments;

use App\Filament\Resources\GtrResultAdjustments\Pages\ListGtrResultAdjustments;
use App\Models\GtrResultAdjustment;
use App\Services\GtrTimingAdmin;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/** Audit koreksi manual hasil: siapa, apa (lama → baru), alasan. Tidak bisa dihapus, hanya dinonaktifkan. */
class GtrResultAdjustmentResource extends Resource
{
    protected static ?string $model = GtrResultAdjustment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static string|\UnitEnum|null $navigationGroup = 'Race Timing';

    protected static ?string $navigationLabel = 'Koreksi Hasil';

    protected static ?string $modelLabel = 'Koreksi Hasil';

    protected static ?string $pluralModelLabel = 'Koreksi Hasil';

    protected static ?int $navigationSort = 5;

    private const FIELDS = ['status' => 'Status', 'finish_time' => 'Jam finish', 'start_time' => 'Jam start'];

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['registration.category', 'user']))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')->label('Waktu')->dateTime('d M H:i:s')->sortable(),
                TextColumn::make('registration.bib_number')->label('BIB')->weight('bold')->searchable(),
                TextColumn::make('registration.full_name')->label('Peserta')->searchable()
                    ->description(fn (GtrResultAdjustment $r) => $r->registration?->category?->name),
                TextColumn::make('field')->label('Field')->badge()->formatStateUsing(fn (string $state) => self::FIELDS[$state] ?? $state),
                TextColumn::make('old_value')->label('Lama')->placeholder('—'),
                TextColumn::make('new_value')->label('Baru')->placeholder('— (dihapus)')->weight('bold'),
                TextColumn::make('reason')->label('Alasan')->wrap(),
                TextColumn::make('user.name')->label('Oleh')->placeholder('—'),
                IconColumn::make('is_active')->label('Aktif')->boolean(),
            ])
            ->filters([
                TernaryFilter::make('is_active')->label('Aktif'),
            ])
            ->recordActions([
                Action::make('deactivate')
                    ->label('Nonaktifkan')
                    ->icon(Heroicon::OutlinedNoSymbol)
                    ->color('danger')
                    ->visible(fn (GtrResultAdjustment $r) => $r->is_active)
                    ->requiresConfirmation()
                    ->modalDescription('Koreksi ini tidak berlaku lagi (catatan tetap tersimpan). Hasil peserta dihitung ulang.')
                    ->action(function (GtrResultAdjustment $record) {
                        app(GtrTimingAdmin::class)->deactivateAdjustment($record);
                        Notification::make()->title('Koreksi dinonaktifkan')->success()->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGtrResultAdjustments::route('/'),
        ];
    }
}
