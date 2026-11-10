<?php

namespace App\Filament\Resources\RacepackStaff;

use App\Filament\Resources\RacepackStaff\Pages\CreateRacepackStaff;
use App\Filament\Resources\RacepackStaff\Pages\EditRacepackStaff;
use App\Filament\Resources\RacepackStaff\Pages\ListRacepackStaff;
use App\Models\User;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Akun petugas pengambilan racepack (role racepack). Login di /scanner/app dan hanya
 * mendapat mode Race Pack — tidak bisa scan timing dan tidak bisa masuk panel admin.
 */
class RacepackStaffResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $slug = 'petugas-racepack';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static string|\UnitEnum|null $navigationGroup = 'Race Timing';

    protected static ?string $navigationLabel = 'Petugas Race Pack';

    protected static ?string $modelLabel = 'Petugas Race Pack';

    protected static ?string $pluralModelLabel = 'Petugas Race Pack';

    protected static ?int $navigationSort = 2;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('role', User::ROLE_RACEPACK);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('Nama')->required()->maxLength(191),
            TextInput::make('username')->label('Username')->required()
                ->alphaDash()
                ->unique(ignoreRecord: true)->maxLength(191)
                ->helperText('Dipakai untuk login di aplikasi scanner (/scanner).'),
            TextInput::make('password')->label('Password')->password()
                ->revealable()
                ->required(fn (string $operation) => $operation === 'create')
                ->dehydrated(fn ($state) => filled($state))
                ->helperText('Kosongkan saat edit bila tak ingin mengganti.'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->withCount([
                'racepackCheckins as checkins_count',
            ]))
            ->columns([
                TextColumn::make('name')->label('Nama')->searchable(),
                TextColumn::make('username')->label('Username')->searchable()->copyable(),
                TextColumn::make('checkins_count')->label('Racepack diserahkan')->badge()->color('success'),
                TextColumn::make('created_at')->label('Dibuat')->dateTime('d M Y')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRacepackStaff::route('/'),
            'create' => CreateRacepackStaff::route('/create'),
            'edit' => EditRacepackStaff::route('/{record}/edit'),
        ];
    }
}
