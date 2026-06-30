<?php

namespace App\Filament\Resources\GtrTimingPoints\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class GtrTimingPointForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('event_id')
                    ->relationship('event', 'title')
                    ->required(),
                TextInput::make('code')
                    ->required(),
                TextInput::make('name')
                    ->required(),
                Select::make('type')
                    ->options([
            'start' => 'Start',
            'checkpoint' => 'Checkpoint',
            'water_station' => 'Water station',
            'finish' => 'Finish',
        ])
                    ->required(),
                TextInput::make('latitude')
                    ->numeric(),
                TextInput::make('longitude')
                    ->numeric(),
                TextInput::make('sort_order')
                    ->required()
                    ->numeric()
                    ->default(0),
            ]);
    }
}
