<?php

namespace App\Filament\Resources\GtrSettings;

use App\Filament\Resources\GtrSettings\Pages\EditGtrSetting;
use App\Filament\Resources\GtrSettings\Pages\ListGtrSettings;
use App\Models\GtrSetting;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class GtrSettingResource extends Resource
{
    protected static ?string $model = GtrSetting::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;

    protected static ?string $navigationLabel = 'Header GTR';

    protected static ?string $modelLabel = 'Header GTR';

    protected static string|\UnitEnum|null $navigationGroup = 'GTR';

    protected static ?int $navigationSort = 0;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Header Halaman GTR')
                ->description('Foto & teks yang tampil di bagian atas (hero) halaman Gerung Trail Run.')
                ->columns(2)
                ->components([
                    FileUpload::make('header_image')
                        ->label('Foto Header')
                        ->image()
                        ->maxSize(5120)
                        ->disk('public')
                        ->directory('gtr/header')
                        ->imageEditor()
                        ->helperText('Gambar otomatis dikompres saat disimpan.')
                        ->saveUploadedFileUsing(fn (TemporaryUploadedFile $file) => \App\Support\ImageCompressor::store($file, 'gtr/header', 1920))
                        ->columnSpanFull(),
                    TextInput::make('eyebrow')
                        ->label('Teks Kecil (Eyebrow)')
                        ->placeholder('1st Edition · Bukit Keteri Trail')
                        ->columnSpanFull(),
                    TextInput::make('title')
                        ->label('Judul')
                        ->placeholder('Gerung Trail Run 2026')
                        ->columnSpanFull(),
                    TextInput::make('date_text')
                        ->label('Tanggal Event')
                        ->placeholder('Minggu, 29 November 2026'),
                    TextInput::make('categories_text')
                        ->label('Kategori')
                        ->placeholder('Kategori 7K & 15K'),
                    TextInput::make('location_text')
                        ->label('Lokasi')
                        ->placeholder('Bukit Keteri, Gerung — Lombok Barat')
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('header_image')->label('Foto')->disk('public'),
                TextColumn::make('title')->label('Judul'),
                TextColumn::make('date_text')->label('Tanggal'),
                TextColumn::make('updated_at')->label('Diperbarui')->dateTime(),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGtrSettings::route('/'),
            'edit' => EditGtrSetting::route('/{record}/edit'),
        ];
    }
}
