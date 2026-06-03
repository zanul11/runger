<?php

namespace App\Filament\Resources\Routes;

use App\Filament\Resources\Routes\Pages\CreateRoute;
use App\Filament\Resources\Routes\Pages\EditRoute;
use App\Filament\Resources\Routes\Pages\ListRoutes;
use App\Filament\Resources\Routes\Pages\ViewRoute;
use App\Models\Route;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RouteResource extends Resource
{
    protected static ?string $model = Route::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMap;

    protected static ?string $navigationLabel = 'Routes (GPX)';

    protected static string|\UnitEnum|null $navigationGroup = 'Content';

    protected static ?int $navigationSort = 1;

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('event_id')
                    ->relationship('event', 'title'),
                TextInput::make('name')
                    ->required(),
                TextInput::make('slug')
                    ->required(),
                Textarea::make('description')
                    ->columnSpanFull(),
                TextInput::make('gpx_file'),
                TextInput::make('total_km')
                    ->numeric(),
                TextInput::make('elevation_gain_m')
                    ->numeric(),
                TextInput::make('km_marker_count')
                    ->numeric(),
                TextInput::make('tikum_lat')
                    ->numeric(),
                TextInput::make('tikum_lng')
                    ->numeric(),
                TextInput::make('route_points'),
                TextInput::make('km_markers'),
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
                    ->label('Event')
                    ->placeholder('-'),
                TextEntry::make('name'),
                TextEntry::make('slug'),
                TextEntry::make('description')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('gpx_file')
                    ->placeholder('-'),
                TextEntry::make('total_km')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('elevation_gain_m')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('km_marker_count')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('tikum_lat')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('tikum_lng')
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
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('slug')
                    ->searchable(),
                TextColumn::make('gpx_file')
                    ->searchable(),
                TextColumn::make('total_km')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('elevation_gain_m')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('km_marker_count')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('tikum_lat')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('tikum_lng')
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
            'index' => ListRoutes::route('/'),
            'create' => CreateRoute::route('/create'),
            'view' => ViewRoute::route('/{record}'),
            'edit' => EditRoute::route('/{record}/edit'),
        ];
    }
}
