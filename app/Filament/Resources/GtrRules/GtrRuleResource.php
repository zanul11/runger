<?php

namespace App\Filament\Resources\GtrRules;

use App\Filament\Resources\GtrRules\Pages\CreateGtrRule;
use App\Filament\Resources\GtrRules\Pages\EditGtrRule;
use App\Filament\Resources\GtrRules\Pages\ListGtrRules;
use App\Models\GtrRule;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class GtrRuleResource extends Resource
{
    protected static ?string $model = GtrRule::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $navigationLabel = 'Rules & Regulation';

    protected static ?string $modelLabel = 'Rule';

    protected static ?string $pluralModelLabel = 'Rules & Regulation';

    protected static string|\UnitEnum|null $navigationGroup = 'GTR';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Aturan')
                ->description('Poin-poin yang tampil di halaman "Rules And Regulation" GTR.')
                ->columns(1)
                ->components([
                    TextInput::make('title')
                        ->label('Judul Aturan')
                        ->placeholder('Mandatory Gear')
                        ->required(),
                    Textarea::make('content')
                        ->label('Isi / Penjelasan')
                        ->rows(3)
                        ->required(),
                    TextInput::make('sort_order')
                        ->label('Urutan')
                        ->numeric()
                        ->default(0),
                    Toggle::make('is_active')
                        ->label('Aktif')
                        ->default(true),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sort_order')->label('No.')->sortable(),
                TextColumn::make('title')->label('Judul')->searchable()->weight('bold'),
                TextColumn::make('content')->label('Isi')->limit(60)->wrap(),
                IconColumn::make('is_active')->label('Aktif')->boolean(),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGtrRules::route('/'),
            'create' => CreateGtrRule::route('/create'),
            'edit' => EditGtrRule::route('/{record}/edit'),
        ];
    }
}
