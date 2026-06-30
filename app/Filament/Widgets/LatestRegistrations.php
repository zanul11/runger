<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\GtrRegistrations\GtrRegistrationResource;
use App\Models\GtrRegistration;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class LatestRegistrations extends TableWidget
{
    protected static ?int $sort = 0;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Pendaftar GTR Terbaru')
            ->query(GtrRegistrationResource::getEloquentQuery()->with('category')->latest('registered_at'))
            ->paginated([5])
            ->defaultPaginationPageOption(5)
            ->recordUrl(fn (GtrRegistration $record): string => GtrRegistrationResource::getUrl('view', ['record' => $record]))
            ->columns([
                TextColumn::make('full_name')
                    ->label('Nama')
                    ->weight(FontWeight::Bold)
                    ->searchable(),
                TextColumn::make('category.distance')
                    ->label('Kategori')
                    ->formatStateUsing(fn ($state, $record) => trim(($state ?? '') . ' · ' . ($record->category->name ?? ''))),
                TextColumn::make('payment_status')
                    ->label('Bayar')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'paid' => 'Lunas',
                        'cancelled' => 'Batal',
                        default => 'Pending',
                    })
                    ->color(fn ($state) => match ($state) {
                        'paid' => 'success',
                        'cancelled' => 'danger',
                        default => 'warning',
                    }),
                TextColumn::make('registered_at')
                    ->label('Daftar')
                    ->since(),
            ]);
    }
}
