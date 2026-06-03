<?php

namespace App\Filament\Resources\Volunteers;

use App\Filament\Resources\Volunteers\Pages\CreateVolunteer;
use App\Filament\Resources\Volunteers\Pages\EditVolunteer;
use App\Filament\Resources\Volunteers\Pages\ListVolunteers;
use App\Filament\Resources\Volunteers\Pages\ViewVolunteer;
use App\Models\Volunteer;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class VolunteerResource extends Resource
{
    protected static ?string $model = Volunteer::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHandRaised;

    protected static ?string $navigationLabel = 'Volunteer GTR';

    protected static ?string $modelLabel = 'Volunteer GTR';

    protected static ?string $pluralModelLabel = 'Volunteer GTR';

    protected static string|\UnitEnum|null $navigationGroup = 'Content';

    protected static ?int $navigationSort = 6;

    protected static array $statuses = [
        'pending' => 'Pending',
        'accepted' => 'Diterima',
        'rejected' => 'Ditolak',
    ];

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nama')
                    ->required(),
                TextInput::make('phone')
                    ->label('No. HP / WhatsApp')
                    ->tel()
                    ->required(),
                Textarea::make('address')
                    ->label('Alamat')
                    ->required()
                    ->columnSpanFull(),
                CheckboxList::make('interests')
                    ->label('Minat Kepanitiaan')
                    ->options(Volunteer::INTERESTS)
                    ->columns(2)
                    ->maxItems(2)
                    ->required()
                    ->columnSpanFull(),
                Textarea::make('reason')
                    ->label('Alasan Ingin Jadi Volunteer')
                    ->required()
                    ->columnSpanFull(),
                Textarea::make('experience')
                    ->label('Pengalaman Jadi Panitia / Volunteer')
                    ->columnSpanFull(),
                Textarea::make('skills')
                    ->label('Keahlian')
                    ->columnSpanFull(),
                Select::make('status')
                    ->options(self::$statuses)
                    ->default('pending')
                    ->required(),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name')
                    ->label('Nama'),
                TextEntry::make('phone')
                    ->label('No. HP / WhatsApp'),
                TextEntry::make('address')
                    ->label('Alamat')
                    ->columnSpanFull(),
                TextEntry::make('interests')
                    ->label('Minat Kepanitiaan')
                    ->badge()
                    ->formatStateUsing(fn ($state) => Volunteer::INTERESTS[$state] ?? $state),
                TextEntry::make('reason')
                    ->label('Alasan')
                    ->columnSpanFull(),
                TextEntry::make('experience')
                    ->label('Pengalaman Jadi Panitia / Volunteer')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('skills')
                    ->label('Keahlian')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => self::$statuses[$state] ?? $state),
                TextEntry::make('created_at')
                    ->label('Mendaftar')
                    ->dateTime(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable(),
                TextColumn::make('phone')
                    ->label('No. HP')
                    ->searchable(),
                TextColumn::make('interests')
                    ->label('Minat')
                    ->badge()
                    ->formatStateUsing(fn ($state) => Volunteer::INTERESTS[$state] ?? $state),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => self::$statuses[$state] ?? $state)
                    ->color(fn ($state) => match ($state) {
                        'accepted' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('created_at')
                    ->label('Mendaftar')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options(self::$statuses),
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
            'index' => ListVolunteers::route('/'),
            'create' => CreateVolunteer::route('/create'),
            'view' => ViewVolunteer::route('/{record}'),
            'edit' => EditVolunteer::route('/{record}/edit'),
        ];
    }
}
