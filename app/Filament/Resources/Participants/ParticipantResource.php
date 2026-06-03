<?php

namespace App\Filament\Resources\Participants;

use App\Filament\Resources\Participants\Pages\CreateParticipant;
use App\Filament\Resources\Participants\Pages\EditParticipant;
use App\Filament\Resources\Participants\Pages\ListParticipants;
use App\Filament\Resources\Participants\Pages\ViewParticipant;
use App\Models\Participant;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ParticipantResource extends Resource
{
    protected static ?string $model = Participant::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedListBullet;

    protected static ?string $navigationLabel = 'Entry List';

    protected static string|\UnitEnum|null $navigationGroup = 'Participants';

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
                    ->relationship('event', 'title')
                    ->required(),
                Select::make('race_category_id')
                    ->relationship('raceCategory', 'name'),
                TextInput::make('bib_number'),
                TextInput::make('name')
                    ->required(),
                TextInput::make('gender'),
                TextInput::make('age_group'),
                DatePicker::make('birthdate'),
                TextInput::make('city'),
                TextInput::make('country')
                    ->required()
                    ->default('Indonesia'),
                TextInput::make('phone')
                    ->tel(),
                TextInput::make('email')
                    ->label('Email address')
                    ->email(),
                TextInput::make('emergency_contact'),
                TextInput::make('status')
                    ->required()
                    ->default('registered'),
                DateTimePicker::make('registered_at'),
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
                TextEntry::make('bib_number')
                    ->placeholder('-'),
                TextEntry::make('name'),
                TextEntry::make('gender')
                    ->placeholder('-'),
                TextEntry::make('age_group')
                    ->placeholder('-'),
                TextEntry::make('birthdate')
                    ->date()
                    ->placeholder('-'),
                TextEntry::make('city')
                    ->placeholder('-'),
                TextEntry::make('country'),
                TextEntry::make('phone')
                    ->placeholder('-'),
                TextEntry::make('email')
                    ->label('Email address')
                    ->placeholder('-'),
                TextEntry::make('emergency_contact')
                    ->placeholder('-'),
                TextEntry::make('status'),
                TextEntry::make('registered_at')
                    ->dateTime()
                    ->placeholder('-'),
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
                TextColumn::make('bib_number')
                    ->searchable(),
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('gender')
                    ->searchable(),
                TextColumn::make('age_group')
                    ->searchable(),
                TextColumn::make('birthdate')
                    ->date()
                    ->sortable(),
                TextColumn::make('city')
                    ->searchable(),
                TextColumn::make('country')
                    ->searchable(),
                TextColumn::make('phone')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Email address')
                    ->searchable(),
                TextColumn::make('emergency_contact')
                    ->searchable(),
                TextColumn::make('status')
                    ->searchable(),
                TextColumn::make('registered_at')
                    ->dateTime()
                    ->sortable(),
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
            'index' => ListParticipants::route('/'),
            'create' => CreateParticipant::route('/create'),
            'view' => ViewParticipant::route('/{record}'),
            'edit' => EditParticipant::route('/{record}/edit'),
        ];
    }
}
