<?php

namespace App\Filament\Resources\GtrCategories\RelationManagers;

use Filament\Actions\AttachAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Kelola titik timing mana yang harus dilewati kategori ini (pivot
 * gtr_category_timing_point: sequence, is_mandatory, cutoff_at).
 */
class TimingPointsRelationManager extends RelationManager
{
    protected static string $relationship = 'timingPoints';

    protected static ?string $title = 'Titik Timing Kategori';

    /** Field pivot saat mengedit titik yang sudah terpasang. */
    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('sequence')
                ->label('Urutan')
                ->numeric()
                ->default(0)
                ->required(),
            Toggle::make('is_mandatory')
                ->label('Wajib dilewati')
                ->default(true),
            DateTimePicker::make('cutoff_at')
                ->label('Cut-off')
                ->seconds(false),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('code')
            ->defaultSort('gtr_category_timing_point.sequence')
            ->columns([
                TextColumn::make('pivot.sequence')
                    ->label('#')
                    ->sortable(),
                TextColumn::make('code')
                    ->searchable(),
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'start' => 'success',
                        'finish' => 'danger',
                        'water_station' => 'info',
                        default => 'gray',
                    }),
                IconColumn::make('pivot.is_mandatory')
                    ->label('Wajib')
                    ->boolean(),
                TextColumn::make('pivot.cutoff_at')
                    ->label('Cut-off')
                    ->dateTime('d M H:i')
                    ->placeholder('-'),
            ])
            ->headerActions([
                AttachAction::make()
                    ->label('Tambah Titik')
                    ->schema(fn (AttachAction $action): array => [
                        $action->getRecordSelect(),
                        TextInput::make('sequence')->label('Urutan')->numeric()->default(0)->required(),
                        Toggle::make('is_mandatory')->label('Wajib dilewati')->default(true),
                        DateTimePicker::make('cutoff_at')->label('Cut-off')->seconds(false),
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
                DetachAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DetachBulkAction::make(),
                ]),
            ]);
    }
}
