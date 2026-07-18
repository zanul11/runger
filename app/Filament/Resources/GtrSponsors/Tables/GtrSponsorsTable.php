<?php

namespace App\Filament\Resources\GtrSponsors\Tables;

use App\Models\GtrSponsor;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class GtrSponsorsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                ImageColumn::make('logo')
                    ->label('Logo')
                    ->disk('public')
                    ->height(40)
                    ->placeholder('—'),
                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable(),
                TextColumn::make('role')
                    ->label('Keterangan')
                    ->badge()
                    ->color('success')
                    ->placeholder('—')
                    ->searchable(),
                TextColumn::make('tier')
                    ->label('Tier')
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => GtrSponsor::TIERS[$state] ?? $state)
                    ->color(fn (?string $state): string => match ($state) {
                        'title', 'gold' => 'warning',
                        'silver' => 'gray',
                        default => 'info',
                    }),
                TextColumn::make('link')
                    ->label('Link')
                    ->url(fn ($record) => $record->link, true)
                    ->limit(30)
                    ->placeholder('—'),
                TextColumn::make('sort_order')
                    ->label('#')
                    ->numeric()
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
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
