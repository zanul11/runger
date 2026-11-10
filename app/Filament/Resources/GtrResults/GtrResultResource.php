<?php

namespace App\Filament\Resources\GtrResults;

use App\Casts\DateTimeMs;
use App\Filament\Resources\GtrResults\Pages\ListGtrResults;
use App\Models\GtrCategory;
use App\Models\GtrResult;
use App\Models\GtrResultAdjustment;
use App\Services\GtrTimingAdmin;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Hasil per peserta (ditulis ResultCalculator). Tidak bisa diedit langsung: koreksi manual
 * lewat aksi "Koreksi" → tercatat di gtr_result_adjustments & diterapkan ulang tiap hitung ulang.
 */
class GtrResultResource extends Resource
{
    protected static ?string $model = GtrResult::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTrophy;

    protected static string|\UnitEnum|null $navigationGroup = 'Race Timing';

    protected static ?string $navigationLabel = 'Hasil (Results)';

    protected static ?string $modelLabel = 'Hasil';

    protected static ?string $pluralModelLabel = 'Hasil';

    protected static ?int $navigationSort = 4;

    public const STATUSES = ['finisher' => 'Finisher', 'dnf' => 'DNF', 'dq' => 'DQ', 'dns' => 'DNS'];

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

    public static function getNavigationBadge(): ?string
    {
        $n = GtrResult::where('needs_review', true)->count();

        return $n ? (string) $n : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Perlu review';
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['registration.category', 'registration.runner'])
            ->withCount(['adjustments as active_adjustments' => fn ($q) => $q->where('is_active', true)]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort(fn (Builder $query) => $query->orderBy('gtr_category_id')->orderByRaw('rank_overall IS NULL')->orderBy('rank_overall'))
            ->poll('30s')
            ->columns([
                TextColumn::make('registration.category.name')->label('Kat')->badge()->color('gray')->placeholder('-'),
                TextColumn::make('rank_overall')->label('#')->sortable()->placeholder('-')->weight('bold'),
                TextColumn::make('rank_gender')->label('# Gender')->placeholder('-'),
                TextColumn::make('registration.bib_number')->label('BIB')->searchable()->placeholder('-'),
                TextColumn::make('registration.full_name')->label('Nama')->searchable()->placeholder('-')
                    ->description(fn (GtrResult $r) => $r->registration?->genderCode() === 'F' ? 'Perempuan' : 'Laki-laki'),
                TextColumn::make('gun_time_ms')->label('Gun Time (resmi)')->sortable()
                    ->formatStateUsing(fn (?int $state) => GtrResult::formatMs($state))->placeholder('-')
                    ->fontFamily('mono'),
                TextColumn::make('net_time_ms')->label('Net Time')
                    ->formatStateUsing(fn (?int $state) => GtrResult::formatMs($state))->placeholder('-')
                    ->fontFamily('mono')
                    ->toggleable(isToggledHiddenByDefault: true), // tidak ada scan start → umumnya kosong
                TextColumn::make('finish_time')->label('Jam Finish')->dateTime('H:i:s')->placeholder('-'),
                TextColumn::make('status')->label('Status')->badge()
                    ->formatStateUsing(fn (string $state) => self::STATUSES[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        'finisher' => 'success', 'dnf' => 'warning', 'dq' => 'danger', default => 'gray',
                    }),
                IconColumn::make('needs_review')->label('Review')->boolean()
                    ->trueIcon(Heroicon::OutlinedExclamationTriangle)->falseIcon('')
                    ->trueColor('danger')
                    ->tooltip(fn (GtrResult $r) => $r->review_note),
                TextColumn::make('review_note')->label('Catatan')->placeholder('-')->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('active_adjustments')->label('Koreksi')->badge()->color('warning')
                    ->state(fn (GtrResult $record) => $record->active_adjustments ? $record->active_adjustments.'×' : null)
                    ->placeholder('-'),
                TextColumn::make('computed_at')->label('Dihitung')->dateTime('d M H:i:s')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('gtr_category_id')->label('Kategori')
                    ->options(fn () => GtrCategory::orderBy('sort_order')->pluck('name', 'id')->all()),
                SelectFilter::make('status')->options(self::STATUSES),
                TernaryFilter::make('needs_review')->label('Perlu review'),
                SelectFilter::make('gender')->label('Gender')
                    ->options(['M' => 'Laki-laki', 'F' => 'Perempuan'])
                    ->query(fn (Builder $query, array $data) => match ($data['value'] ?? null) {
                        'M' => $query->whereHas('registration', fn ($r) => $r->where('gender', 'like', 'L%')),
                        'F' => $query->whereHas('registration', fn ($r) => $r->where('gender', 'like', 'P%')),
                        default => $query,
                    }),
            ])
            ->recordActions([
                Action::make('adjust')
                    ->label('Koreksi')
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->color('warning')
                    ->modalHeading(fn (GtrResult $r) => 'Koreksi hasil '.$r->registration?->bib_number.' · '.$r->registration?->full_name)
                    ->modalDescription('Koreksi tercatat (siapa, apa, alasan) dan tetap berlaku walau scan baru masuk. Nonaktifkan di menu "Koreksi Hasil".')
                    ->schema([
                        Select::make('field')->label('Yang dikoreksi')->required()->live()
                            ->options([
                                GtrResultAdjustment::FIELD_STATUS => 'Status (Finisher / DNF / DQ / DNS)',
                                GtrResultAdjustment::FIELD_FINISH_TIME => 'Jam finish',
                                GtrResultAdjustment::FIELD_START_TIME => 'Jam start individu (net time, jarang dipakai)',
                            ]),
                        Select::make('status')->label('Status baru')->options(self::STATUSES)
                            ->visible(fn (Get $get) => $get('field') === GtrResultAdjustment::FIELD_STATUS)
                            ->required(fn (Get $get) => $get('field') === GtrResultAdjustment::FIELD_STATUS),
                        TextInput::make('time')->label('Jam baru (YYYY-MM-DD HH:MM:SS.mmm WITA, kosong = hapus)')
                            ->placeholder('2026-11-29 07:45:12.345')
                            ->regex('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}(\.\d{1,3})?$/')
                            ->visible(fn (Get $get) => in_array($get('field'), [GtrResultAdjustment::FIELD_FINISH_TIME, GtrResultAdjustment::FIELD_START_TIME], true))
                            ->default(fn (GtrResult $record) => $record->finish_time?->format(DateTimeMs::FORMAT)),
                        Textarea::make('reason')->label('Alasan')->required()->rows(2)
                            ->placeholder('mis. terlihat di video CP1 pukul 06:42'),
                    ])
                    ->action(function (GtrResult $record, array $data, Action $action) {
                        $value = $data['field'] === GtrResultAdjustment::FIELD_STATUS ? $data['status'] : ($data['time'] ?? null);
                        try {
                            app(GtrTimingAdmin::class)->adjustResult($record->registration, $data['field'], $value, $data['reason'], auth()->user());
                        } catch (\InvalidArgumentException $e) {
                            Notification::make()->title($e->getMessage())->danger()->send();
                            $action->halt();

                            return;
                        }
                        Notification::make()->title('Koreksi disimpan & hasil dihitung ulang')->success()->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGtrResults::route('/'),
        ];
    }
}
