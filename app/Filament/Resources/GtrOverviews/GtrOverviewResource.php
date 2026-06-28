<?php

namespace App\Filament\Resources\GtrOverviews;

use App\Filament\Resources\GtrOverviews\Pages\EditGtrOverview;
use App\Filament\Resources\GtrOverviews\Pages\ListGtrOverviews;
use App\Models\GtrOverview;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class GtrOverviewResource extends Resource
{
    protected static ?string $model = GtrOverview::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedNewspaper;

    protected static ?string $navigationLabel = 'Event Overview';

    protected static ?string $modelLabel = 'Event Overview';

    protected static string|\UnitEnum|null $navigationGroup = 'GTR';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Teks Event Overview')
                ->description('Teks pada bagian "Event Overview" di halaman GTR.')
                ->columns(2)
                ->components([
                    TextInput::make('eyebrow')
                        ->label('Teks Kecil (Eyebrow)')
                        ->placeholder('Event Overview'),
                    TextInput::make('heading')
                        ->label('Judul')
                        ->placeholder('Gerung Trail Run 2026'),
                    Textarea::make('paragraph_1')
                        ->label('Paragraf 1')
                        ->rows(4)
                        ->columnSpanFull(),
                    Textarea::make('paragraph_2')
                        ->label('Paragraf 2')
                        ->rows(4)
                        ->columnSpanFull(),
                ]),

            Section::make('Foto')
                ->description('1 foto besar + 2 foto kecil. Gambar otomatis dikompres saat disimpan.')
                ->columns(3)
                ->components([
                    FileUpload::make('photo_main')
                        ->label('Foto Utama (besar)')
                        ->image()
                        ->maxSize(5120)
                        ->disk('public')
                        ->directory('gtr/overview')
                        ->imageEditor()
                        ->saveUploadedFileUsing(fn (TemporaryUploadedFile $file) => \App\Support\ImageCompressor::store($file, 'gtr/overview', 1600)),
                    FileUpload::make('photo_2')
                        ->label('Foto 2')
                        ->image()
                        ->maxSize(5120)
                        ->disk('public')
                        ->directory('gtr/overview')
                        ->imageEditor()
                        ->saveUploadedFileUsing(fn (TemporaryUploadedFile $file) => \App\Support\ImageCompressor::store($file, 'gtr/overview', 1200)),
                    FileUpload::make('photo_3')
                        ->label('Foto 3')
                        ->image()
                        ->maxSize(5120)
                        ->disk('public')
                        ->directory('gtr/overview')
                        ->imageEditor()
                        ->saveUploadedFileUsing(fn (TemporaryUploadedFile $file) => \App\Support\ImageCompressor::store($file, 'gtr/overview', 1200)),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('photo_main')->label('Foto')->disk('public'),
                TextColumn::make('heading')->label('Judul'),
                TextColumn::make('updated_at')->label('Diperbarui')->dateTime(),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGtrOverviews::route('/'),
            'edit' => EditGtrOverview::route('/{record}/edit'),
        ];
    }
}
