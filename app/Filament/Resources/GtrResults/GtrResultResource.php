<?php

namespace App\Filament\Resources\GtrResults;

use App\Filament\Resources\GtrResults\Pages\ListGtrResults;
use App\Models\GtrResult;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class GtrResultResource extends Resource
{
    protected static ?string $model = GtrResult::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTrophy;

    protected static string|\UnitEnum|null $navigationGroup = 'Race Timing';

    protected static ?string $navigationLabel = 'Hasil (Results)';

    protected static ?string $modelLabel = 'Hasil';

    protected static ?string $pluralModelLabel = 'Hasil';

    protected static ?int $navigationSort = 4;

    // Read-only: hasil hanya dibuat oleh result engine.
    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['registration.category', 'registration.runner']);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('rank_overall')
            ->columns([
                TextColumn::make('rank_overall')->label('#')->sortable()->placeholder('-'),
                TextColumn::make('registration.bib_number')->label('BIB')->searchable()->placeholder('-'),
                TextColumn::make('registration.full_name')->label('Nama')->searchable()->placeholder('-'),
                TextColumn::make('registration.category.tag')->label('Kategori')->badge()->placeholder('-'),
                TextColumn::make('registration.gender')->label('Gender')->placeholder('-'),
                TextColumn::make('net_time_formatted')->label('Net Time')->placeholder('-'),
                TextColumn::make('gun_time_formatted')->label('Gun Time')->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('rank_category')->label('Rank Kat')->placeholder('-'),
                TextColumn::make('rank_gender')->label('Rank Gender')->placeholder('-'),
                TextColumn::make('status')->label('Status')->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'finisher' => 'success', 'dnf' => 'warning', 'dq' => 'danger', default => 'gray',
                    }),
                TextColumn::make('computed_at')->label('Dihitung')->dateTime('d M H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')->options([
                    'finisher' => 'Finisher', 'dnf' => 'DNF', 'dq' => 'DQ', 'dns' => 'DNS',
                ]),
                SelectFilter::make('category')
                    ->label('Kategori')
                    ->relationship('registration.category', 'name'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGtrResults::route('/'),
        ];
    }
}
