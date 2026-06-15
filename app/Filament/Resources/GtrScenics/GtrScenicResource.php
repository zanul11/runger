<?php

namespace App\Filament\Resources\GtrScenics;

use App\Filament\Resources\GtrScenics\Pages\CreateGtrScenic;
use App\Filament\Resources\GtrScenics\Pages\EditGtrScenic;
use App\Filament\Resources\GtrScenics\Pages\ListGtrScenics;
use App\Models\GtrScenic;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class GtrScenicResource extends Resource
{
    protected static ?string $model = GtrScenic::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;

    protected static ?string $navigationLabel = 'Scenic Course';

    protected static ?string $modelLabel = 'Foto Scenic Course';

    protected static ?string $pluralModelLabel = 'Scenic Course';

    protected static string|\UnitEnum|null $navigationGroup = 'GTR';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Foto Scenic Course')
                ->description('Foto-foto yang tampil di bagian "Our Scenic Course" pada halaman GTR.')
                ->columns(2)
                ->components([
                    FileUpload::make('image')
                        ->label('Foto')
                        ->image()
                        ->disk('public')
                        ->directory('gtr/scenic')
                        ->imageEditor()
                        ->required()
                        ->helperText('Gambar otomatis dikompres saat disimpan.')
                        ->saveUploadedFileUsing(fn (TemporaryUploadedFile $file) => \App\Support\ImageCompressor::store($file, 'gtr/scenic', 1500))
                        ->columnSpanFull(),
                    TextInput::make('label')
                        ->label('Keterangan / Label')
                        ->placeholder('Top Keteri')
                        ->columnSpanFull(),
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
                ImageColumn::make('image')->label('Foto')->disk('public'),
                TextColumn::make('label')->label('Keterangan')->searchable(),
                IconColumn::make('is_active')->label('Aktif')->boolean(),
                TextColumn::make('sort_order')->label('Urutan')->sortable(),
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
            'index' => ListGtrScenics::route('/'),
            'create' => CreateGtrScenic::route('/create'),
            'edit' => EditGtrScenic::route('/{record}/edit'),
        ];
    }
}
