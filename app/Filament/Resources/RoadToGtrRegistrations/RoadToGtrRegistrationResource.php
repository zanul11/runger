<?php

namespace App\Filament\Resources\RoadToGtrRegistrations;

use App\Filament\Resources\RoadToGtrRegistrations\Pages\CreateRoadToGtrRegistration;
use App\Filament\Resources\RoadToGtrRegistrations\Pages\EditRoadToGtrRegistration;
use App\Filament\Resources\RoadToGtrRegistrations\Pages\ListRoadToGtrRegistrations;
use App\Models\RoadToGtrRegistration;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/** Pendaftar Road to GTR Fun Trail (form publik /gerung-trail-run/road-to-gtr). */
class RoadToGtrRegistrationResource extends Resource
{
    protected static ?string $model = RoadToGtrRegistration::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFlag;

    protected static ?string $navigationLabel = 'Road to GTR';

    protected static ?string $modelLabel = 'Pendaftar Road to GTR';

    protected static ?string $pluralModelLabel = 'Pendaftar Road to GTR';

    protected static string|\UnitEnum|null $navigationGroup = 'GTR';

    protected static ?int $navigationSort = 8;

    public static function getNavigationBadge(): ?string
    {
        return (string) RoadToGtrRegistration::count() ?: null;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nama')
                    ->required()
                    ->maxLength(120),
                TextInput::make('phone')
                    ->label('No. HP / WhatsApp')
                    ->tel()
                    ->required()
                    ->maxLength(20)
                    ->unique(ignoreRecord: true),
                TextInput::make('community')
                    ->label('Komunitas')
                    ->maxLength(120),
                Textarea::make('address')
                    ->label('Alamat')
                    ->required()
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('index')
                    ->label('#')
                    ->rowIndex(),
                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('phone')
                    ->label('No. HP')
                    ->formatStateUsing(fn ($state, RoadToGtrRegistration $record) => $record->phone_formatted)
                    ->url(fn (RoadToGtrRegistration $record) => $record->whatsapp_url, shouldOpenInNewTab: true)
                    ->color('success')
                    ->searchable(),
                TextColumn::make('community')
                    ->label('Komunitas')
                    ->placeholder('-')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('address')
                    ->label('Alamat')
                    ->wrap()
                    ->limit(50)
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label('Mendaftar')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->headerActions([
                Action::make('csv')
                    ->label('Download CSV')
                    ->icon(Heroicon::OutlinedArrowDownTray)
                    ->action(fn () => response()->streamDownload(function () {
                        $out = fopen('php://output', 'w');
                        fputcsv($out, ['No', 'Nama', 'No. HP', 'Komunitas', 'Alamat', 'Mendaftar']);
                        RoadToGtrRegistration::orderBy('created_at')->get()->each(function (RoadToGtrRegistration $r, int $i) use ($out) {
                            fputcsv($out, [$i + 1, $r->name, $r->phone, $r->community, $r->address, $r->created_at?->format('Y-m-d H:i')]);
                        });
                        fclose($out);
                    }, 'road-to-gtr-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv; charset=utf-8'])),
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

    public static function getPages(): array
    {
        return [
            'index' => ListRoadToGtrRegistrations::route('/'),
            'create' => CreateRoadToGtrRegistration::route('/create'),
            'edit' => EditRoadToGtrRegistration::route('/{record}/edit'),
        ];
    }
}
