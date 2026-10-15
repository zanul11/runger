<?php

namespace App\Filament\Resources\GtrCategories;

use App\Filament\Resources\GtrCategories\Pages\CreateGtrCategory;
use App\Filament\Resources\GtrCategories\Pages\EditGtrCategory;
use App\Filament\Resources\GtrCategories\Pages\ListGtrCategories;
use App\Models\GtrCategory;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class GtrCategoryResource extends Resource
{
    protected static ?string $model = GtrCategory::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFlag;

    protected static ?string $navigationLabel = 'Kategori GTR';

    protected static ?string $modelLabel = 'Kategori GTR';

    protected static ?string $pluralModelLabel = 'Kategori GTR';

    protected static string|\UnitEnum|null $navigationGroup = 'GTR';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identitas Kategori')
                    ->columns(2)
                    ->components([
                        TextInput::make('name')
                            ->label('Nama Kategori')
                            ->required(),
                        TextInput::make('slug')
                            ->label('Slug (URL)')
                            ->helperText('Dipakai di URL, mis. 7k / 15k. Tanpa spasi.')
                            ->required()
                            ->unique(ignoreRecord: true),
                        TextInput::make('bib_prefix')
                            ->label('Prefix Nomor BIB')
                            ->placeholder('7')
                            ->maxLength(8)
                            ->helperText('BIB = prefix + urutan 3 digit per kategori. Mis. "7" → 7001, 7002 … (>999 jadi 4 digit).'),
                        TextInput::make('distance')
                            ->label('Jarak (tampil)')
                            ->placeholder('7 KM')
                            ->required(),
                        TextInput::make('tag')
                            ->label('Tag')
                            ->placeholder('7K Trail'),
                        ColorPicker::make('color')
                            ->label('Warna Aksen')
                            ->default('#E53935'),
                        Textarea::make('description')
                            ->label('Deskripsi Singkat')
                            ->columnSpanFull(),
                    ]),

                Section::make('Media')
                    ->columns(2)
                    ->components([
                        FileUpload::make('header_image')
                            ->label('Gambar Header')
                            ->image()
                            ->maxSize(5120)
                            ->disk('public')
                            ->directory('gtr/categories')
                            ->imageEditor()
                            ->helperText('Gambar otomatis dikompres saat disimpan.')
                            ->saveUploadedFileUsing(fn (TemporaryUploadedFile $file) => \App\Support\ImageCompressor::store($file, 'gtr/categories', 1600)),
                        FileUpload::make('gpx_file')
                            ->label('File GPX (Course Map)')
                            ->disk('public')
                            ->directory('gtr/gpx')
                            ->helperText('Upload .gpx — otomatis tampil di Course Map.'),
                        Toggle::make('gpx_downloadable')
                            ->label('Izinkan peserta download GPX')
                            ->helperText('Matikan bila file GPX belum boleh dibagikan. Rute tetap tampil di Course Map.')
                            ->default(true),
                    ]),

                Section::make('Pendaftaran & Detail')
                    ->columns(2)
                    ->components([
                        TextInput::make('price_early_bird')
                            ->label('Harga Early Bird')
                            ->numeric()
                            ->prefix('IDR'),
                        TextInput::make('price_normal')
                            ->label('Harga Normal')
                            ->numeric()
                            ->prefix('IDR'),
                        DatePicker::make('early_bird_until')
                            ->label('Batas Early Bird'),
                        TextInput::make('start_time')
                            ->label('Start (teks display)')
                            ->placeholder('06:00 WITA'),
                        TextInput::make('elevation_gain')
                            ->label('Elevation Gain')
                            ->placeholder('+344 M')
                            ->hintAction(
                                Action::make('elevationFromGpx')
                                    ->label('Isi dari GPX')
                                    ->icon(Heroicon::OutlinedArrowPath)
                                    ->visible(fn (?GtrCategory $record) => filled($record?->gpx_file))
                                    ->action(function (Set $set, ?GtrCategory $record) {
                                        if ($gain = $record?->gpxElevationGain()) {
                                            $set('elevation_gain', $gain);
                                        }
                                    })
                            ),
                        TextInput::make('cut_off_time')
                            ->label('Cut-Off Time (teks display)')
                            ->placeholder('2 Jam'),
                        \Filament\Forms\Components\DateTimePicker::make('gun_start')
                            ->label('Gun Start (waktu nyata)')
                            ->seconds(false)
                            ->helperText('Dipakai result engine untuk menghitung gun time & start fallback.'),
                        \Filament\Forms\Components\DateTimePicker::make('cut_off_at')
                            ->label('Cut-Off (waktu nyata)')
                            ->seconds(false)
                            ->helperText('Finish setelah waktu ini = DNF (over-COT).'),
                        Textarea::make('award_prize')
                            ->label('Award & Prize')
                            ->columnSpanFull(),
                    ]),

                Section::make('Rute: Pos & Info Jalur')
                    ->description('Klik rute di peta untuk mengambil posisi KM, lalu tambahkan WS / CP / Info Jalur. Peringatan data muncul di bawah peta.')
                    ->columns(1)
                    ->components([
                        View::make('filament.gtr.course-picker')
                            ->viewData(fn (?GtrCategory $record) => ['record' => $record])
                            ->columnSpanFull(),
                        Repeater::make('water_stations')
                            ->label('Titik di Rute (Water Station & Check Point)')
                            ->helperText('Tiap WS / CP beserta posisi KM-nya akan muncul di Course Map, profil elevasi, dan tabel segmen. Kode WS1/CP1 dibuat otomatis sesuai urutan KM.')
                            ->schema([
                                Select::make('type')
                                    ->label('Tipe')
                                    ->options(GtrCategory::POINT_TYPES)
                                    ->default('ws')
                                    ->selectablePlaceholder(false)
                                    ->required(),
                                TextInput::make('km')
                                    ->label('KM')
                                    ->numeric()
                                    ->step('0.01')
                                    ->required(),
                                TextInput::make('name')
                                    ->label('Nama Pos')
                                    ->placeholder('Air Terjun Meledos'),
                                TextInput::make('cutoff')
                                    ->label('Cut-off (jam WITA)')
                                    ->placeholder('08:30')
                                    ->regex('/^\d{1,2}[:.]\d{2}$/')
                                    ->validationMessages(['regex' => 'Format jam HH:MM, contoh 08:30.'])
                                    ->helperText('Kosongkan bila tidak ada.'),
                                TextInput::make('location')
                                    ->label('Lokasi')
                                    ->placeholder('Pertigaan Dusun Bantir, depan masjid')
                                    ->columnSpanFull(),
                                TagsInput::make('facilities')
                                    ->label('Isi / Fasilitas')
                                    ->placeholder('Ketik lalu Enter')
                                    ->suggestions(['Air mineral', 'Isotonik', 'Pisang', 'Semangka', 'Jeruk', 'Gula merah', 'Garam', 'Medis', 'Toilet', 'Refill bottle'])
                                    ->columnSpanFull(),
                                Textarea::make('notes')
                                    ->label('Catatan untuk Peserta')
                                    ->placeholder('Wajib scan BIB. Bawa gelas sendiri (cupless).')
                                    ->rows(2)
                                    ->columnSpanFull(),
                            ])
                            ->columns(4)
                            ->collapsible()
                            ->rules([fn (?GtrCategory $record) => self::courseKmRule($record, true)])
                            ->itemLabel(fn (array $state): ?string => isset($state['km']) && $state['km'] !== ''
                                ? (GtrCategory::POINT_TYPES[$state['type'] ?? 'ws'] ?? 'Titik') . ' · KM ' . $state['km'] . (filled($state['name'] ?? null) ? ' — ' . $state['name'] : '')
                                : null)
                            ->addActionLabel('Tambah Titik (WS / CP)')
                            ->columnSpanFull(),
                        Repeater::make('course_pois')
                            ->label('Info & Bahaya Jalur')
                            ->helperText('Mis. jalur licin, sungai, turunan curam, marshal, medis, spot foto. Bahaya juga muncul sebagai peringatan di split per KM.')
                            ->schema([
                                Select::make('type')
                                    ->label('Tipe')
                                    ->options(GtrCategory::poiTypeOptions())
                                    ->default('technical')
                                    ->selectablePlaceholder(false)
                                    ->required(),
                                TextInput::make('km')
                                    ->label('KM')
                                    ->numeric()
                                    ->step('0.01')
                                    ->required(),
                                TextInput::make('km_end')
                                    ->label('s/d KM (opsional)')
                                    ->numeric()
                                    ->step('0.01')
                                    ->helperText('Isi bila berupa ruas.'),
                                TextInput::make('title')
                                    ->label('Judul')
                                    ->placeholder('Turunan tanah licin'),
                                Textarea::make('note')
                                    ->label('Keterangan')
                                    ->placeholder('Gunakan sepatu trail, ada tali bantu di sisi kanan.')
                                    ->rows(2)
                                    ->columnSpanFull(),
                            ])
                            ->columns(4)
                            ->collapsible()
                            ->collapsed()
                            ->rules([fn (?GtrCategory $record) => self::courseKmRule($record, false)])
                            ->itemLabel(fn (array $state): ?string => isset($state['km']) && $state['km'] !== ''
                                ? (GtrCategory::POI_TYPES[$state['type'] ?? '']['icon'] ?? '') . ' KM ' . $state['km'] . (filled($state['km_end'] ?? null) ? '–' . $state['km_end'] : '') . (filled($state['title'] ?? null) ? ' — ' . $state['title'] : '')
                                : null)
                            ->addActionLabel('Tambah Info Jalur')
                            ->columnSpanFull(),
                    ]),

                Section::make('Mandatory Gear & Rundown')
                    ->columns(2)
                    ->components([
                        TagsInput::make('mandatory_gear')
                            ->label('Mandatory Gear')
                            ->placeholder('Ketik lalu Enter')
                            ->columnSpanFull(),
                        Repeater::make('rundown')
                            ->label('Event Rundown')
                            ->schema([
                                TextInput::make('date')->label('Tanggal')->placeholder('28 Nov 2026'),
                                TextInput::make('time')->label('Waktu')->placeholder('06.00'),
                                TextInput::make('activity')->label('Kegiatan')->columnSpanFull(),
                                TextInput::make('location')->label('Lokasi')->placeholder('Venue / Bukit Keteri')->columnSpanFull(),
                            ])
                            ->columns(2)
                            ->columnSpanFull(),
                    ]),

                Section::make('Pengaturan')
                    ->columns(2)
                    ->components([
                        TextInput::make('sort_order')
                            ->label('Urutan')
                            ->numeric()
                            ->default(0),
                        Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(true),
                    ]),
            ]);
    }

    /**
     * Validasi repeater pos / info jalur: KM tidak melebihi panjang rute;
     * untuk pos, jam cut-off harus makin lambat mengikuti urutan KM.
     */
    protected static function courseKmRule(?GtrCategory $record, bool $checkCutoff): \Closure
    {
        return function (string $attribute, $value, \Closure $fail) use ($record, $checkCutoff) {
            $items = collect(is_array($value) ? $value : [])->filter(fn ($p) => is_array($p) && is_numeric($p['km'] ?? null));
            $ep = $record?->elevation_profile ?? [];
            $max = count($ep) ? (float) end($ep)[0] : null;

            if ($max !== null && ($over = $items->first(fn ($p) => (float) $p['km'] > $max + 0.05))) {
                $fail('KM ' . $over['km'] . ' melebihi panjang rute (' . number_format($max, 1) . ' km).');
            }
            if ($bad = $items->first(fn ($p) => is_numeric($p['km_end'] ?? null) && (float) $p['km_end'] < (float) $p['km'])) {
                $fail('"s/d KM" di KM ' . $bad['km'] . ' harus lebih besar dari KM awal.');
            }

            if ($checkCutoff) {
                $last = null;
                foreach ($items->sortBy(fn ($p) => (float) $p['km']) as $p) {
                    if (! preg_match('/^\s*(\d{1,2})[:.](\d{2})\s*$/', (string) ($p['cutoff'] ?? ''), $m)) {
                        continue;
                    }
                    $min = (int) $m[1] * 60 + (int) $m[2];
                    if ($last !== null && $min <= $last[0]) {
                        $fail('Cut-off di KM ' . $p['km'] . ' harus lebih lambat dari pos sebelumnya (KM ' . $last[1] . ').');

                        return;
                    }
                    $last = [$min, $p['km']];
                }
            }
        };
    }

        public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('header_image')
                    ->label('Header')
                    ->disk('public'),
                TextColumn::make('name')
                    ->label('Nama')
                    ->description(fn (GtrCategory $r) => $r->distance)
                    ->searchable(),
                TextColumn::make('slug')
                    ->badge(),
                TextColumn::make('price_early_bird')
                    ->label('Early Bird')
                    ->money('IDR'),
                TextColumn::make('price_normal')
                    ->label('Normal')
                    ->money('IDR'),
                IconColumn::make('is_active')
                    ->boolean(),
                TextColumn::make('sort_order')
                    ->label('Urutan')
                    ->sortable(),
            ])
            ->defaultSort('sort_order')
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

    public static function getRelations(): array
    {
        return [
            \App\Filament\Resources\GtrCategories\RelationManagers\TimingPointsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGtrCategories::route('/'),
            'create' => CreateGtrCategory::route('/create'),
            'edit' => EditGtrCategory::route('/{record}/edit'),
        ];
    }
}
