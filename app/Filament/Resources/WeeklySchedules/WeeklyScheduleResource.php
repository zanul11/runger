<?php

namespace App\Filament\Resources\WeeklySchedules;

use App\Filament\Resources\WeeklySchedules\Pages\CreateWeeklySchedule;
use App\Filament\Resources\WeeklySchedules\Pages\EditWeeklySchedule;
use App\Filament\Resources\WeeklySchedules\Pages\ListWeeklySchedules;
use App\Filament\Resources\WeeklySchedules\Pages\ViewWeeklySchedule;
use App\Models\WeeklySchedule;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class WeeklyScheduleResource extends Resource
{
    protected static ?string $model = WeeklySchedule::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static ?string $navigationLabel = 'Jadwal Mingguan';

    protected static string|\UnitEnum|null $navigationGroup = 'Content';

    protected static ?int $navigationSort = 4;

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->required(),
                TextInput::make('day_of_week')
                    ->required(),
                TimePicker::make('time'),
                TextInput::make('location'),
                Textarea::make('description')
                    ->columnSpanFull(),
                TextInput::make('tag'),
                TextInput::make('sort_order')
                    ->required()
                    ->numeric()
                    ->default(0),
                Toggle::make('is_active')
                    ->required(),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('title'),
                TextEntry::make('day_of_week'),
                TextEntry::make('time')
                    ->time()
                    ->placeholder('-'),
                TextEntry::make('location')
                    ->placeholder('-'),
                TextEntry::make('description')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('tag')
                    ->placeholder('-'),
                TextEntry::make('sort_order')
                    ->numeric(),
                IconEntry::make('is_active')
                    ->boolean(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->searchable(),
                TextColumn::make('day_of_week')
                    ->searchable(),
                TextColumn::make('time')
                    ->time()
                    ->sortable(),
                TextColumn::make('location')
                    ->searchable(),
                TextColumn::make('tag')
                    ->searchable(),
                TextColumn::make('sort_order')
                    ->numeric()
                    ->sortable(),
                IconColumn::make('is_active')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWeeklySchedules::route('/'),
            'create' => CreateWeeklySchedule::route('/create'),
            'view' => ViewWeeklySchedule::route('/{record}'),
            'edit' => EditWeeklySchedule::route('/{record}/edit'),
        ];
    }
}
