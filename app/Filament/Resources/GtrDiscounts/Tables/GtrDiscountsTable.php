<?php

namespace App\Filament\Resources\GtrDiscounts\Tables;

use App\Models\GtrDiscount;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class GtrDiscountsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('code')
                    ->label('Kode')
                    ->badge()
                    ->color('warning')
                    ->copyable()
                    ->searchable(),
                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable(),
                TextColumn::make('label')
                    ->label('Potongan')
                    ->badge()
                    ->color('success'),
                TextColumn::make('used_count')
                    ->label('Terpakai')
                    ->formatStateUsing(fn ($state, GtrDiscount $record) => $record->quota === null
                        ? $state . ' / ∞'
                        : $state . ' / ' . $record->quota),
                TextColumn::make('remaining')
                    ->label('Sisa')
                    ->state(fn (GtrDiscount $record) => $record->remaining() === null ? '∞' : $record->remaining())
                    ->badge()
                    ->color(fn (GtrDiscount $record) => ($record->remaining() === null || $record->remaining() > 0) ? 'gray' : 'danger'),
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
