<?php

namespace App\Filament\Resources\GtrCategories;

use App\Filament\Resources\GtrCategories\Pages\CreateGtrCategory;
use App\Filament\Resources\GtrCategories\Pages\EditGtrCategory;
use App\Filament\Resources\GtrCategories\Pages\ListGtrCategories;
use App\Models\GtrCategory;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class GtrCategoryResource extends Resource
{
    protected static ?string $model = GtrCategory::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFlag;

    protected static ?string $navigationLabel = 'Kategori GTR';

    protected static ?string $modelLabel = 'Kategori GTR';

    protected static ?string $pluralModelLabel = 'Kategori GTR';

    protected static string|\UnitEnum|null $navigationGroup = 'GTR';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identitas Kategori')
                    ->columns(2)
                    ->components([
                        TextInput::make('name')
                            ->label('Nama Kategori')
                            ->required(),
                        TextInput::make('slug')
                            ->label('Slug (URL)')
                            ->helperText('Dipakai di URL, mis. 7k / 15k. Tanpa spasi.')
                            ->required()
                            ->unique(ignoreRecord: true),
                        TextInput::make('distance')
                            ->label('Jarak (tampil)')
                            ->placeholder('7 KM')
                            ->required(),
                        TextInput::make('tag')
                            ->label('Tag')
                            ->placeholder('7K Trail'),
                        ColorPicker::make('color')
                            ->label('Warna Aksen')
                            ->default('#E53935'),
                        Textarea::make('description')
                            ->label('Deskripsi Singkat')
                            ->columnSpanFull(),
                    ]),

                Section::make('Media')
                    ->columns(2)
                    ->components([
                        FileUpload::make('header_image')
                            ->label('Gambar Header')
                            ->image()
                            ->disk('public')
                            ->directory('gtr/categories')
                            ->imageEditor()
                            ->helperText('Gambar otomatis dikompres saat disimpan.')
                            ->saveUploadedFileUsing(fn (TemporaryUploadedFile $file) => \App\Support\ImageCompressor::store($file, 'gtr/categories', 1600)),
                        FileUpload::make('gpx_file')
                            ->label('File GPX (Course Map)')
                            ->disk('public')
                            ->directory('gtr/gpx')
                            ->helperText('Upload .gpx — otomatis tampil di Course Map & bisa diunduh.'),
                    ]),

                Section::make('Pendaftaran & Detail')
                    ->columns(2)
                    ->components([
                        TextInput::make('price_early_bird')
                            ->label('Harga Early Bird')
                            ->numeric()
                            ->prefix('IDR'),
                        TextInput::make('price_normal')
                            ->label('Harga Normal')
                            ->numeric()
                            ->prefix('IDR'),
                        DatePicker::make('early_bird_until')
                            ->label('Batas Early Bird'),
                        TextInput::make('start_time')
                            ->label('Start')
                            ->placeholder('06:00 WITA'),
                        TextInput::make('elevation_gain')
                            ->label('Elevation Gain')
                            ->placeholder('+344 M'),
                        TextInput::make('cut_off_time')
                            ->label('Cut-Off Time')
                            ->placeholder('2 Jam'),
                        Textarea::make('award_prize')
                            ->label('Award & Prize')
                            ->columnSpanFull(),
                        Repeater::make('water_stations')
                            ->label('Water Station')
                            ->helperText('Tambahkan tiap WS beserta posisi KM-nya — akan muncul di Course Map & profil elevasi.')
                            ->schema([
                                TextInput::make('km')
                                    ->label('KM')
                                    ->numeric()
                                    ->step('0.1')
                                    ->required(),
                                TextInput::make('name')
                                    ->label('Keterangan')
                                    ->placeholder('WS 1 — Pos Air'),
                            ])
                            ->columns(2)
                            ->addActionLabel('Tambah Water Station')
                            ->columnSpanFull(),
                    ]),

                Section::make('Mandatory Gear & Rundown')
                    ->columns(2)
                    ->components([
                        TagsInput::make('mandatory_gear')
                            ->label('Mandatory Gear')
                            ->placeholder('Ketik lalu Enter')
                            ->columnSpanFull(),
                        Repeater::make('rundown')
                            ->label('Event Rundown')
                            ->schema([
                                TextInput::make('date')->label('Tanggal')->placeholder('28 Nov 2026'),
                                TextInput::make('time')->label('Waktu')->placeholder('06.00'),
                                TextInput::make('activity')->label('Kegiatan')->columnSpanFull(),
                                TextInput::make('location')->label('Lokasi')->placeholder('Venue / Bukit Keteri')->columnSpanFull(),
                            ])
                            ->columns(2)
                            ->columnSpanFull(),
                    ]),

                Section::make('Pengaturan')
                    ->columns(2)
                    ->components([
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
                ImageColumn::make('header_image')
                    ->label('Header')
                    ->disk('public'),
                TextColumn::make('name')
                    ->label('Nama')
                    ->description(fn (GtrCategory $r) => $r->distance)
                    ->searchable(),
                TextColumn::make('slug')
                    ->badge(),
                TextColumn::make('price_early_bird')
                    ->label('Early Bird')
                    ->money('IDR'),
                TextColumn::make('price_normal')
                    ->label('Normal')
                    ->money('IDR'),
                IconColumn::make('is_active')
                    ->boolean(),
                TextColumn::make('sort_order')
                    ->label('Urutan')
                    ->sortable(),
            ])
            ->defaultSort('sort_order')
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
            'index' => ListGtrCategories::route('/'),
            'create' => CreateGtrCategory::route('/create'),
            'edit' => EditGtrCategory::route('/{record}/edit'),
        ];
    }
}
