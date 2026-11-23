<?php

namespace App\Filament\Resources\CrewPoints;

use App\Filament\Resources\CrewPoints\Pages\EditCrewPoints;
use App\Filament\Resources\CrewPoints\Pages\ListCrewPoints;
use App\Filament\Resources\GtrCategories\GtrCategoryResource;
use App\Models\GtrCategory;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Sign arah & marshal per kategori GTR (KHUSUS PANITIA): KM, arah & warna sign, tim marshal.
 * Disimpan di kolom crew_signs / crew_marshals kategori; tampil di peta panitia /crew/peta,
 * tidak di Course Map peserta. Klik rute di peta untuk mengambil KM.
 */
class CrewPointResource extends Resource
{
    protected static ?string $model = GtrCategory::class;

    protected static ?string $slug = 'sign-marshal';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static ?string $navigationLabel = 'Sign & Marshal';

    protected static ?string $modelLabel = 'sign & marshal';

    protected static ?string $pluralModelLabel = 'Sign & Marshal';

    protected static string|\UnitEnum|null $navigationGroup = 'Roster Panitia';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Section::make('Peta')
                ->description('Klik rute = tambah titik · klik penanda = edit · geser penanda = pindah KM. Perubahan dari peta langsung tersimpan.')
                ->components([
                    View::make('filament.gtr.crew-picker')
                        ->viewData(fn (?GtrCategory $record) => ['record' => $record]),
                ]),

            Section::make('Tabel Sign Arah')
                ->description('Alternatif edit massal lewat tabel (klik Simpan di bawah). Arah relatif terhadap arah lari peserta.')
                ->collapsible()
                ->collapsed()
                ->components([
                    Repeater::make('crew_signs')
                        ->hiddenLabel()
                        ->table([
                            TableColumn::make('KM')->width('120px'),
                            TableColumn::make('Arah')->width('190px'),
                            TableColumn::make('Warna')->width('150px'),
                            TableColumn::make('Keterangan'),
                        ])
                        ->schema([
                            TextInput::make('km')->numeric()->step('0.01')->minValue(0)->required(),
                            Select::make('direction')
                                ->options(GtrCategory::signDirectionOptions())
                                ->placeholder('Pilih arah'),
                            Select::make('color')
                                ->options(GtrCategory::signColorOptions())
                                ->default('red')
                                ->selectablePlaceholder(false)
                                ->required(),
                            TextInput::make('note')->placeholder('Masuk jalan setapak kiri masjid')->maxLength(200),
                        ])
                        ->reorderable(false)
                        ->defaultItems(0)
                        ->rules([fn (?GtrCategory $record) => GtrCategoryResource::courseKmRule($record, false)])
                        ->addActionLabel('Tambah Sign'),
                ]),

            Section::make('Tabel Marshal')
                ->collapsible()
                ->collapsed()
                ->components([
                    Repeater::make('crew_marshals')
                        ->hiddenLabel()
                        ->table([
                            TableColumn::make('KM')->width('120px'),
                            TableColumn::make('Tim / Asal')->width('190px'),
                            TableColumn::make('Orang')->width('110px'),
                            TableColumn::make('Keterangan'),
                        ])
                        ->schema([
                            TextInput::make('km')->numeric()->step('0.01')->minValue(0)->required(),
                            TextInput::make('team')->datalist(GtrCategory::MARSHAL_TEAMS)->placeholder('Basecamp')->required()->maxLength(60),
                            TextInput::make('count')->numeric()->minValue(1)->maxValue(50)->default(1)->required(),
                            TextInput::make('note')->placeholder('Jaga simpang, arahkan ke kanan')->maxLength(200),
                        ])
                        ->reorderable(false)
                        ->defaultItems(0)
                        ->rules([fn (?GtrCategory $record) => GtrCategoryResource::courseKmRule($record, false)])
                        ->addActionLabel('Tambah Marshal'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->orderBy('sort_order')->orderBy('id'))
            ->columns([
                TextColumn::make('name')->label('Kategori')->weight('bold'),
                TextColumn::make('total_km')->label('Rute')->suffix(' km')
                    ->placeholder('GPX belum ada'),
                TextColumn::make('signs')->label('Sign')
                    ->state(fn (GtrCategory $r) => $r->crewSigns()->count())
                    ->description(fn (GtrCategory $r) => ($n = $r->crewSigns()->whereNull('direction')->count()) ? "{$n} belum ada arah" : null),
                TextColumn::make('marshals')->label('Marshal')
                    ->state(fn (GtrCategory $r) => $r->crewMarshals()->count() . ' titik · ' . $r->crewMarshals()->sum('count') . ' orang'),
            ])
            ->recordActions([EditAction::make()->label('Atur')])
            ->paginated(false);
    }

    public static function canCreate(): bool
    {
        return false; // kategori dibuat di menu GTR → Kategori
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCrewPoints::route('/'),
            'edit' => EditCrewPoints::route('/{record}/edit'),
        ];
    }
}
