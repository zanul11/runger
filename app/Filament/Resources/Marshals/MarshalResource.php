<?php

namespace App\Filament\Resources\Marshals;

use App\Filament\Resources\Marshals\Pages\CreateMarshal;
use App\Filament\Resources\Marshals\Pages\EditMarshal;
use App\Filament\Resources\Marshals\Pages\ListMarshals;
use App\Models\GtrTimingPoint;
use App\Models\User;
use App\Services\MarshalService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MarshalResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static string|\UnitEnum|null $navigationGroup = 'Race Timing';

    protected static ?string $navigationLabel = 'Marshal';

    protected static ?string $modelLabel = 'Marshal';

    protected static ?string $pluralModelLabel = 'Marshal';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'name';

    /** Hanya tampilkan user dengan role marshal. */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('role', User::ROLE_MARSHAL);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('Nama')->required()->maxLength(191),
            TextInput::make('email')->label('Email')->email()->required()
                ->unique(ignoreRecord: true)->maxLength(191),
            TextInput::make('password')->label('Password')->password()
                ->revealable()
                ->required(fn (string $operation) => $operation === 'create')
                ->dehydrated(fn ($state) => filled($state))
                ->helperText('Kosongkan saat edit bila tak ingin mengganti.'),

            // Pos hanya saat membuat marshal baru; pindah pos lewat aksi "Pindahkan pos".
            Select::make('timing_point_id')
                ->label('Tugaskan ke Pos')
                ->options(fn () => GtrTimingPoint::with('event')->get()
                    ->mapWithKeys(fn ($tp) => [$tp->id => "{$tp->code} · {$tp->name} ({$tp->event?->title})"]))
                ->searchable()
                ->required()
                ->visibleOn('create'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nama')->searchable(),
                TextColumn::make('email')->searchable(),
                TextColumn::make('active_pos')
                    ->label('Pos Aktif')
                    ->state(function (User $record): string {
                        $a = $record->activeAssignment();

                        return $a?->timingPoint
                            ? "{$a->timingPoint->code} · {$a->timingPoint->name}"
                            : '— belum ditugaskan';
                    })
                    ->badge()
                    ->color(fn (User $record) => $record->activeAssignment() ? 'success' : 'gray'),
                IconColumn::make('has_active')
                    ->label('Aktif')
                    ->state(fn (User $record) => (bool) $record->activeAssignment())
                    ->boolean(),
            ])
            ->recordActions([
                // Pindahkan pos: nonaktifkan lama, aktifkan baru (via MarshalService).
                Action::make('reassign')
                    ->label('Pindahkan pos')
                    ->icon(Heroicon::OutlinedArrowsRightLeft)
                    ->color('info')
                    ->schema([
                        Select::make('timing_point_id')
                            ->label('Pos baru')
                            ->options(fn () => GtrTimingPoint::with('event')->get()
                                ->mapWithKeys(fn ($tp) => [$tp->id => "{$tp->code} · {$tp->name} ({$tp->event?->title})"]))
                            ->searchable()
                            ->required(),
                    ])
                    ->action(function (User $record, array $data) {
                        $tp = GtrTimingPoint::findOrFail($data['timing_point_id']);
                        app(MarshalService::class)->reassign($record, $tp->event_id, $tp->id);
                        Notification::make()->title('Pos dipindahkan')->body("Ke {$tp->code}")->success()->send();
                    }),

                // Toggle aktif/nonaktif penugasan saat ini.
                Action::make('toggleActive')
                    ->label(fn (User $record) => $record->activeAssignment() ? 'Nonaktifkan' : 'Aktifkan')
                    ->icon(Heroicon::OutlinedPower)
                    ->color(fn (User $record) => $record->activeAssignment() ? 'danger' : 'success')
                    ->requiresConfirmation()
                    ->action(function (User $record) {
                        $active = $record->activeAssignment();
                        if ($active) {
                            $active->update(['is_active' => false]);
                            Notification::make()->title('Penugasan dinonaktifkan')->warning()->send();

                            return;
                        }
                        // Aktifkan kembali assignment terakhir bila ada.
                        $last = $record->timingPointAssignments()->latest('assigned_at')->first();
                        if ($last) {
                            app(MarshalService::class)->assign($last->user, $last->event_id, $last->gtr_timing_point_id);
                            Notification::make()->title('Penugasan diaktifkan')->success()->send();
                        } else {
                            Notification::make()->title('Belum ada pos. Pakai "Pindahkan pos".')->danger()->send();
                        }
                    }),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMarshals::route('/'),
            'create' => CreateMarshal::route('/create'),
            'edit' => EditMarshal::route('/{record}/edit'),
        ];
    }
}
