<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Volunteers\VolunteerResource;
use App\Models\Volunteer;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class LatestVolunteers extends TableWidget
{
    protected static ?int $sort = 0;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Volunteer Terbaru')
            ->query(VolunteerResource::getEloquentQuery()->latest())
            ->paginated([5])
            ->defaultPaginationPageOption(5)
            ->recordUrl(fn (Volunteer $record): string => VolunteerResource::getUrl('view', ['record' => $record]))
            ->columns([
                TextColumn::make('name')
                    ->label('Nama')
                    ->weight(FontWeight::Bold)
                    ->searchable(),
                TextColumn::make('phone')
                    ->label('No. HP'),
                TextColumn::make('interests')
                    ->label('Minat')
                    ->badge()
                    ->formatStateUsing(fn ($state) => Volunteer::INTERESTS[$state] ?? $state),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'accepted' => 'Diterima',
                        'rejected' => 'Ditolak',
                        default => 'Pending',
                    })
                    ->color(fn ($state) => match ($state) {
                        'accepted' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('created_at')
                    ->label('Mendaftar')
                    ->since(),
            ]);
    }
}
