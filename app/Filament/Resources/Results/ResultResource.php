<?php

namespace App\Filament\Resources\Results;

use App\Filament\Resources\Results\Pages\CreateResult;
use App\Filament\Resources\Results\Pages\EditResult;
use App\Filament\Resources\Results\Pages\ListResults;
use App\Filament\Resources\Results\Pages\ViewResult;
use App\Models\Result;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ResultResource extends Resource
{
    protected static ?string $model = Result::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTrophy;

    protected static ?string $navigationLabel = 'Results';

    protected static string|\UnitEnum|null $navigationGroup = 'Participants';

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
                Select::make('race_category_id')
                    ->relationship('raceCategory', 'name'),
                Select::make('participant_id')
                    ->relationship('participant', 'name')
                    ->required(),
                TextInput::make('chip_time'),
                TextInput::make('gun_time'),
                TextInput::make('position_overall')
                    ->numeric(),
                TextInput::make('position_category')
                    ->numeric(),
                TextInput::make('position_gender')
                    ->numeric(),
                TextInput::make('status')
                    ->required()
                    ->default('finisher'),
                Textarea::make('notes')
                    ->columnSpanFull(),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('event.title')
                    ->label('Event'),
                TextEntry::make('raceCategory.name')
                    ->label('Race category')
                    ->placeholder('-'),
                TextEntry::make('participant.name')
                    ->label('Participant'),
                TextEntry::make('chip_time')
                    ->placeholder('-'),
                TextEntry::make('gun_time')
                    ->placeholder('-'),
                TextEntry::make('position_overall')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('position_category')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('position_gender')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('status'),
                TextEntry::make('notes')
                    ->placeholder('-')
                    ->columnSpanFull(),
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
                TextColumn::make('raceCategory.name')
                    ->searchable(),
                TextColumn::make('participant.name')
                    ->searchable(),
                TextColumn::make('chip_time')
                    ->searchable(),
                TextColumn::make('gun_time')
                    ->searchable(),
                TextColumn::make('position_overall')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('position_category')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('position_gender')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('status')
                    ->searchable(),
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
            'index' => ListResults::route('/'),
            'create' => CreateResult::route('/create'),
            'view' => ViewResult::route('/{record}'),
            'edit' => EditResult::route('/{record}/edit'),
        ];
    }
}
