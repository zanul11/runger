<?php

namespace App\Filament\Resources\Runners;

use App\Filament\Resources\Runners\Pages\ListRunners;
use App\Filament\Resources\Runners\Pages\ViewRunner;
use App\Models\Runner;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RunnerResource extends Resource
{
    protected static ?string $model = Runner::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?string $navigationLabel = 'Akun Peserta';

    protected static ?string $modelLabel = 'Akun Peserta';

    protected static ?string $pluralModelLabel = 'Akun Peserta';

    protected static string|\UnitEnum|null $navigationGroup = 'GTR';

    protected static ?int $navigationSort = 3;

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('first_name')->label('Nama Depan'),
            TextEntry::make('last_name')->label('Nama Belakang')->placeholder('-'),
            TextEntry::make('email'),
            TextEntry::make('phone')->label('No. HP')->formatStateUsing(fn ($s, $r) => $s ? $r->phone_country . ' ' . $s : '-'),
            TextEntry::make('nationality')->placeholder('-'),
            TextEntry::make('birthdate')->date()->placeholder('-'),
            TextEntry::make('gender')->placeholder('-'),
            TextEntry::make('address')->placeholder('-')->columnSpanFull(),
            TextEntry::make('registrations_count')->label('Jumlah Pendaftaran')->state(fn ($r) => $r->registrations()->count()),
            TextEntry::make('created_at')->label('Bergabung')->dateTime(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('first_name')->label('Nama')
                    ->formatStateUsing(fn ($s, $r) => trim($s . ' ' . $r->last_name))
                    ->searchable(['first_name', 'last_name']),
                TextColumn::make('email')->searchable(),
                TextColumn::make('phone')->label('No. HP')->toggleable(),
                TextColumn::make('nationality')->toggleable(),
                TextColumn::make('registrations_count')->label('Pendaftaran')->counts('registrations')->badge(),
                TextColumn::make('created_at')->label('Bergabung')->dateTime()->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRunners::route('/'),
            'view' => ViewRunner::route('/{record}'),
        ];
    }
}
