<?php

namespace App\Filament\Resources\RaceCategories;

use App\Filament\Resources\RaceCategories\Pages\CreateRaceCategory;
use App\Filament\Resources\RaceCategories\Pages\EditRaceCategory;
use App\Filament\Resources\RaceCategories\Pages\ListRaceCategories;
use App\Filament\Resources\RaceCategories\Pages\ViewRaceCategory;
use App\Models\RaceCategory;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RaceCategoryResource extends Resource
{
    protected static ?string $model = RaceCategory::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFlag;

    protected static ?string $navigationLabel = 'Race Categories';

    protected static string|\UnitEnum|null $navigationGroup = 'Events & Races';

    protected static ?int $navigationSort = 2;

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('event_id')
                    ->relationship('event', 'title')
                    ->required(),
                TextInput::make('code'),
                TextInput::make('name')
                    ->required(),
                TextInput::make('distance_km')
                    ->required()
                    ->numeric(),
                TextInput::make('difficulty_level')
                    ->required()
                    ->numeric()
                    ->default(0),
                TimePicker::make('start_time'),
                TextInput::make('duration'),
                TextInput::make('elevation_gain'),
                Textarea::make('description')
                    ->columnSpanFull(),
                FileUpload::make('image')->disk('public')
                    ->image(),
                TextInput::make('cut_off'),
                TextInput::make('age_minimum'),
                TextInput::make('quota')
                    ->numeric(),
                TextInput::make('fee')
                    ->numeric(),
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
                TextEntry::make('event.title')
                    ->label('Event'),
                TextEntry::make('code')
                    ->placeholder('-'),
                TextEntry::make('name'),
                TextEntry::make('distance_km')
                    ->numeric(),
                TextEntry::make('difficulty_level')
                    ->numeric(),
                TextEntry::make('start_time')
                    ->time()
                    ->placeholder('-'),
                TextEntry::make('duration')
                    ->placeholder('-'),
                TextEntry::make('elevation_gain')
                    ->placeholder('-'),
                TextEntry::make('description')
                    ->placeholder('-')
                    ->columnSpanFull(),
                ImageEntry::make('image')->disk('public')
                    ->placeholder('-'),
                TextEntry::make('cut_off')
                    ->placeholder('-'),
                TextEntry::make('age_minimum')
                    ->placeholder('-'),
                TextEntry::make('quota')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('fee')
                    ->numeric()
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
                TextColumn::make('event.title')
                    ->searchable(),
                TextColumn::make('code')
                    ->searchable(),
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('distance_km')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('difficulty_level')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('start_time')
                    ->time()
                    ->sortable(),
                TextColumn::make('duration')
                    ->searchable(),
                TextColumn::make('elevation_gain')
                    ->searchable(),
                ImageColumn::make('image')->disk('public'),
                TextColumn::make('cut_off')
                    ->searchable(),
                TextColumn::make('age_minimum')
                    ->searchable(),
                TextColumn::make('quota')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('fee')
                    ->numeric()
                    ->sortable(),
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
            'index' => ListRaceCategories::route('/'),
            'create' => CreateRaceCategory::route('/create'),
            'view' => ViewRaceCategory::route('/{record}'),
            'edit' => EditRaceCategory::route('/{record}/edit'),
        ];
    }
}
