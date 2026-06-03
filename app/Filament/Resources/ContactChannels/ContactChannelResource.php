<?php

namespace App\Filament\Resources\ContactChannels;

use App\Filament\Resources\ContactChannels\Pages\CreateContactChannel;
use App\Filament\Resources\ContactChannels\Pages\EditContactChannel;
use App\Filament\Resources\ContactChannels\Pages\ListContactChannels;
use App\Filament\Resources\ContactChannels\Pages\ViewContactChannel;
use App\Models\ContactChannel;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ContactChannelResource extends Resource
{
    protected static ?string $model = ContactChannel::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhone;

    protected static ?string $navigationLabel = 'Contact Channels';

    protected static string|\UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 1;

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('type')
                    ->required(),
                TextInput::make('label')
                    ->required(),
                TextInput::make('value')
                    ->required(),
                TextInput::make('link'),
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
                TextEntry::make('type'),
                TextEntry::make('label'),
                TextEntry::make('value'),
                TextEntry::make('link')
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
                TextColumn::make('type')
                    ->searchable(),
                TextColumn::make('label')
                    ->searchable(),
                TextColumn::make('value')
                    ->searchable(),
                TextColumn::make('link')
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
            'index' => ListContactChannels::route('/'),
            'create' => CreateContactChannel::route('/create'),
            'view' => ViewContactChannel::route('/{record}'),
            'edit' => EditContactChannel::route('/{record}/edit'),
        ];
    }
}
