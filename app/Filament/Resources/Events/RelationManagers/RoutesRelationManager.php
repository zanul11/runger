<?php

namespace App\Filament\Resources\Events\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RoutesRelationManager extends RelationManager
{
    protected static string $relationship = 'routes';

    protected static ?string $title = 'Rute & GPX';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Upload File GPX')
                ->description('Statistik (jarak, elevasi, marker KM, polyline) akan diekstrak otomatis dari file GPX setelah disimpan.')
                ->components([
                    FileUpload::make('gpx_file')
                        ->label('File GPX')
                        ->required()
                        ->disk('public')
                        ->directory('routes/gpx')
                        ->visibility('public')
                        ->acceptedFileTypes([
                            'application/gpx+xml',
                            'application/xml',
                            'text/xml',
                            'application/octet-stream',
                            'text/plain',
                        ])
                        ->rules(['file', 'max:10240', 'extensions:gpx'])
                        ->maxSize(10 * 1024)
                        ->preserveFilenames()
                        ->helperText('Pilih file .gpx hasil rekaman (Strava, COROS, Garmin). Max 10 MB.'),
                    TextInput::make('name')
                        ->label('Nama Rute (optional)')
                        ->maxLength(120)
                        ->helperText('Kosongkan untuk pakai nama dari file GPX.'),
                    Toggle::make('is_active')->default(true),
                ]),

            Section::make('Statistik Hasil Parse')
                ->description('Otomatis terisi setelah file di-upload & disimpan.')
                ->columns(3)
                ->visibleOn('edit')
                ->components([
                    Placeholder::make('total_km')->label('Total Jarak')
                        ->content(fn ($record) => $record?->total_km ? $record->total_km . ' km' : '—'),
                    Placeholder::make('elevation_gain_m')->label('Elevation Gain')
                        ->content(fn ($record) => $record?->elevation_gain_m ? '+' . $record->elevation_gain_m . ' m' : '—'),
                    Placeholder::make('km_marker_count')->label('KM Markers')
                        ->content(fn ($record) => $record?->km_marker_count ?? '—'),
                    Placeholder::make('elevation_min_m')->label('Elevasi Min')
                        ->content(fn ($record) => $record?->elevation_min_m !== null ? $record->elevation_min_m . ' m' : '—'),
                    Placeholder::make('elevation_max_m')->label('Elevasi Max')
                        ->content(fn ($record) => $record?->elevation_max_m !== null ? $record->elevation_max_m . ' m' : '—'),
                    Placeholder::make('tikum')->label('Start (Lat, Lng)')
                        ->content(fn ($record) => ($record?->tikum_lat && $record?->tikum_lng) ? "{$record->tikum_lat}, {$record->tikum_lng}" : '—'),
                ]),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')->searchable()->weight('bold')
                    ->placeholder('(belum di-parse)'),
                TextColumn::make('total_km')->label('Jarak')->suffix(' km')->numeric(decimalPlaces: 2),
                TextColumn::make('elevation_gain_m')->label('Elevasi')->prefix('+')->suffix(' m'),
                TextColumn::make('km_marker_count')->label('Marker'),
                TextColumn::make('gpx_file')->label('GPX')->formatStateUsing(fn ($state) => $state ? '✓' : '—'),
                IconColumn::make('is_active')->boolean(),
            ])
            ->defaultSort('sort_order')
            ->headerActions([
                CreateAction::make()->label('Tambah Rute'),
            ])
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
}
