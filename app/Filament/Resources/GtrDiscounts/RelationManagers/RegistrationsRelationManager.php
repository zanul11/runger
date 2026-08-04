<?php

namespace App\Filament\Resources\GtrDiscounts\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Daftar pemakai voucher ini (tracking: siapa saja yang memakai + statusnya).
 */
class RegistrationsRelationManager extends RelationManager
{
    protected static string $relationship = 'registrations';

    protected static ?string $title = 'Pemakai Voucher';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('full_name')
            ->defaultSort('registered_at', 'desc')
            ->columns([
                TextColumn::make('nomor_registrasi')->label('No. Reg')->searchable(),
                TextColumn::make('full_name')->label('Nama')->searchable(),
                TextColumn::make('runner.email')->label('Akun/Email')->searchable(),
                TextColumn::make('category.name')->label('Kategori'),
                TextColumn::make('discount_amount')->label('Potongan')->money('IDR'),
                TextColumn::make('payment_status')->label('Bayar')->badge()
                    ->color(fn ($state) => match ($state) {
                        'paid' => 'success', 'cancelled' => 'danger', default => 'warning',
                    }),
                TextColumn::make('registered_at')->label('Waktu')->dateTime('d M Y H:i')->sortable(),
            ])
            ->paginated([10, 25, 50]);
    }
}
