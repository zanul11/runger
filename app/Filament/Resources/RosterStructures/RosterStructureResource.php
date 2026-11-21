<?php

namespace App\Filament\Resources\RosterStructures;

use App\Filament\Resources\RosterStructures\Pages\EditRosterStructure;
use App\Filament\Resources\RosterStructures\Pages\ListRosterStructures;
use App\Models\Event;
use App\Models\RosterAssignment;
use App\Models\RosterConsumptionSetting;
use App\Models\RosterPosition;
use App\Services\RosterService;
use BackedEnum;
use Closure;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * Struktur roster panitia hari H per event: Area → Grup → Pos (min–max petugas), plus setting
 * konsumsi. Satu halaman per event (repeater bertingkat, urutan bisa di-drag). Petugas diisi di
 * GTR Crew → Roster; tampilan baca-saja publik di /crew/roster.
 */
class RosterStructureResource extends Resource
{
    protected static ?string $model = Event::class;

    protected static ?string $slug = 'struktur-roster';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?string $navigationLabel = 'Struktur Roster';

    protected static ?string $modelLabel = 'struktur roster';

    protected static ?string $pluralModelLabel = 'Struktur Roster';

    protected static string|\UnitEnum|null $navigationGroup = 'Roster Panitia';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Section::make('Event')
                ->description('Info event (nama, tanggal, lokasi, kategori) diubah di menu Konten → Events.')
                ->columns(2)
                ->components([
                    TextInput::make('title')->label('Nama event')->disabled()->dehydrated(false),
                    Toggle::make('roster_aktif')
                        ->label('Event aktif di roster')
                        ->helperText('Hanya satu event yang aktif; event lain otomatis dinonaktifkan.')
                        ->inline(false),
                ]),

            Section::make('Struktur kepanitiaan')
                ->description('Area → grup → pos/divisi. Seret ikon ⋮⋮ untuk mengurutkan. Pos nonaktif tidak dihitung & tidak tampil di roster, data petugasnya tetap disimpan.')
                ->components([self::areasRepeater()]),

            Group::make()
                ->relationship('rosterConsumption')
                ->components([
                    Section::make('Konsumsi Race Village')
                        ->description('Dasar hitungan = max(total kebutuhan maksimal semua pos aktif, total terisi) + tamu.')
                        ->columns(3)
                        ->components([
                            TextInput::make('tamu_tambahan')->label('Tamu / VIP / sponsor')->numeric()->minValue(0)->maxValue(5000)->default(10)->required()
                                ->helperText('Bisa juga diubah dari halaman roster.'),
                            TextInput::make('air_per_orang')->label('Air mineral per orang')->numeric()->minValue(0)->maxValue(20)->default(2)->required()->suffix('botol'),
                            Select::make('area_acuan_id')
                                ->label('Area acuan paket sarapan pos')
                                ->helperText('Kosong = area berslug "lapangan".')
                                ->options(fn (?RosterConsumptionSetting $record) => $record?->event?->rosterAreas()->pluck('nama', 'id') ?? [])
                                ->placeholder('Otomatis (lapangan)'),
                            Repeater::make('jadwal')
                                ->label('Jadwal konsumsi')
                                ->table([
                                    TableColumn::make('Waktu')->width('140px'),
                                    TableColumn::make('Kegiatan'),
                                ])
                                ->schema([
                                    TextInput::make('waktu')->placeholder('04.30')->maxLength(20)->required(),
                                    TextInput::make('kegiatan')->placeholder('Sarapan panitia')->maxLength(200)->required(),
                                ])
                                ->reorderable()
                                ->addActionLabel('Tambah baris jadwal')
                                ->defaultItems(0)
                                ->columnSpanFull(),
                            Textarea::make('catatan')->label('Catatan kecil')->rows(2)->maxLength(255)->columnSpanFull(),
                        ]),
                ]),
        ]);
    }

    private static function areasRepeater(): Repeater
    {
        return Repeater::make('rosterAreas')
            ->label('Area')
            ->relationship()
            ->orderColumn('urutan')
            ->schema([
                Group::make()->columns(2)->components([
                    TextInput::make('nama')->label('Nama area')->required()->maxLength(80)->placeholder('Race Village'),
                    TextInput::make('slug')->label('Slug')->maxLength(80)->placeholder('otomatis dari nama')
                        ->helperText('Dipakai area acuan konsumsi ("lapangan").')
                        ->dehydrateStateUsing(fn ($state) => Str::slug((string) $state) ?: null),
                ]),
                Repeater::make('groups')
                    ->label('Grup')
                    ->relationship()
                    ->orderColumn('urutan')
                    ->schema([
                        TextInput::make('nama')->label('Nama grup')->required()->maxLength(80)->placeholder('Komando'),
                        self::positionsRepeater(),
                    ])
                    ->itemLabel(fn (array $state): string => ($state['nama'] ?? '') !== '' ? $state['nama'].' · '.count($state['positions'] ?? []).' pos' : 'Grup baru')
                    ->collapsible()
                    ->addActionLabel('Tambah grup')
                    ->defaultItems(0),
            ])
            ->itemLabel(fn (array $state): string => $state['nama'] ?? 'Area baru')
            ->collapsible()
            ->addActionLabel('Tambah area')
            ->defaultItems(0);
    }

    private static function positionsRepeater(): Repeater
    {
        return Repeater::make('positions')
            ->label('Pos / divisi')
            ->relationship()
            ->orderColumn('urutan')
            ->columns(6)
            ->schema([
                TextInput::make('nama')->label('Nama pos')->required()->maxLength(120)->columnSpan(3),
                Select::make('ikon')->label('Ikon')->options(RosterPosition::iconOptions())->allowHtml()->native(false)
                    ->default('flag')->required()->selectablePlaceholder(false)->columnSpan(2),
                Toggle::make('aktif')->label('Aktif')->default(true)->inline(false)->columnSpan(1),
                TextInput::make('min_petugas')->label('Min')->numeric()->integer()->minValue(0)->maxValue(50)->default(1)->required()
                    ->helperText('0 = pos opsional')->live(onBlur: true)->columnSpan(1),
                TextInput::make('max_petugas')->label('Maks')->numeric()->integer()->minValue(1)->maxValue(50)->default(1)->required()
                    ->rules([
                        fn (Get $get): Closure => function (string $attribute, $value, Closure $fail) use ($get) {
                            if ((int) $value < (int) $get('min_petugas')) {
                                $fail('Maksimal harus ≥ minimal.');
                            }
                        },
                        // Max diturunkan & ada petugas di slot yang hilang → tolak.
                        fn (?RosterPosition $record): Closure => function (string $attribute, $value, Closure $fail) use ($record) {
                            if ($record?->exists && ($msg = app(RosterService::class)->maxChangeError($record, (int) $value))) {
                                $fail($msg);
                            }
                        },
                    ])
                    ->columnSpan(1),
                TagsInput::make('km')->label('KM')->placeholder('mis. 8,1 lalu Enter')->splitKeys([',', ' '])
                    ->helperText('Desimal pakai koma (8,1) — ketik lalu Enter.')
                    ->nestedRecursiveRules(['max:12'])->columnSpan(2),
                TextInput::make('catatan_tambahan')->label('Catatan tambahan')->maxLength(160)->placeholder('+ ambulans')->columnSpan(2),
                Textarea::make('deskripsi')->label('Jobdesk')->rows(2)->maxLength(1000)->columnSpanFull(),
            ])
            ->itemLabel(function (array $state): string {
                $nama = ($state['nama'] ?? '') !== '' ? $state['nama'] : 'Pos baru';
                $min = (int) ($state['min_petugas'] ?? 0);
                $max = (int) ($state['max_petugas'] ?? 0);
                $km = array_filter((array) ($state['km'] ?? []));

                return $nama.' · '.($min === $max ? $min : "{$min}–{$max}").' org'.($km ? ' · KM '.implode(' · ', $km) : '').(($state['aktif'] ?? true) ? '' : ' · NONAKTIF');
            })
            ->collapsible()
            ->collapsed()
            ->cloneable()
            ->cloneAction(fn (Action $action) => $action
                ->label('Duplikat pos')
                ->tooltip('Duplikat pos (nomor di nama otomatis +1)')
                ->action(function (array $arguments, Repeater $component): void {
                    $items = $component->getRawState();
                    $source = $items[$arguments['item']] ?? null;
                    if (! $source) {
                        return;
                    }
                    $source['nama'] = self::nextName((string) ($source['nama'] ?? ''), array_column($items, 'nama'));
                    $out = [];
                    foreach ($items as $key => $item) {
                        $out[$key] = $item;
                        if ($key === $arguments['item']) {
                            $out[$component->generateUuid() ?? (string) Str::uuid()] = $source; // tepat di bawah pos sumber
                        }
                    }
                    $component->rawState($out);
                    $component->callAfterStateUpdated();
                }))
            ->extraItemActions([
                Action::make('rapatkanSlot')
                    ->label('Rapatkan slot')
                    ->tooltip('Pindahkan petugas di slot atas ke slot kosong (sebelum menurunkan maks)')
                    ->icon(Heroicon::OutlinedArrowsUpDown)
                    ->visible(fn (array $arguments) => str_starts_with((string) ($arguments['item'] ?? ''), 'record-'))
                    ->requiresConfirmation()
                    ->modalDescription('Petugas di slot nomor di atas nilai "Maks" (yang sedang diisi di form) dipindah ke slot kosong. Simpan formulir setelahnya.')
                    ->action(function (array $arguments, Repeater $component): void {
                        $position = RosterPosition::find((int) Str::after($arguments['item'], 'record-'));
                        $max = (int) ($component->getRawState()[$arguments['item']]['max_petugas'] ?? 0);
                        if (! $position || $max < 1) {
                            return;
                        }
                        $r = app(RosterService::class)->compactSlots($position, $max);
                        $r['left']
                            ? Notification::make()->warning()->title("{$r['moved']} dipindah, {$r['left']} petugas tidak muat")
                                ->body('Slot kosong tidak cukup. Kosongkan sebagian petugas di halaman roster atau batalkan perubahan maks.')->send()
                            : Notification::make()->success()->title($r['moved'] ? "{$r['moved']} petugas dipindah ke slot kosong" : 'Tidak ada petugas yang perlu dipindah')
                                ->body('Sekarang simpan formulir.')->send();
                    }),
            ])
            ->addActionLabel('Tambah pos')
            ->defaultItems(0);
    }

    /** "Marshal 11" → "Marshal 12" (lewati nama yang sudah ada); tanpa angka → "Nama (salinan)". */
    public static function nextName(string $name, array $siblings): string
    {
        $taken = collect($siblings)->map(fn ($n) => mb_strtolower(trim((string) $n)))->all();
        if (preg_match('/^(.*?)(\d+)\s*$/u', trim($name), $m)) {
            $n = (int) $m[2];
            do {
                $candidate = $m[1].(++$n);
            } while (in_array(mb_strtolower($candidate), $taken, true));

            return $candidate;
        }
        $candidate = trim($name).' (salinan)';
        $i = 2;
        while (in_array(mb_strtolower($candidate), $taken, true)) {
            $candidate = trim($name)." (salinan {$i})";
            $i++;
        }

        return $candidate;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->withCount('rosterAreas'))
            ->columns([
                TextColumn::make('title')->label('Event')->searchable()->weight('bold'),
                TextColumn::make('date')->label('Tanggal')->date('d M Y')->sortable(),
                ToggleColumn::make('roster_aktif')->label('Aktif di roster'),
                TextColumn::make('roster_areas_count')->label('Area')->numeric(),
                TextColumn::make('kebutuhan')->label('Pos aktif · kebutuhan')
                    ->state(function (Event $record): string {
                        $q = RosterPosition::where('aktif', true)->whereHas('group.area', fn ($a) => $a->where('event_id', $record->id));
                        $n = (clone $q)->count();

                        return $n ? $n.' pos · '.(clone $q)->sum('min_petugas').'–'.(clone $q)->sum('max_petugas').' org' : '—';
                    }),
                TextColumn::make('terisi')->label('Terisi')
                    ->state(fn (Event $record): int => RosterAssignment::filled()
                        ->whereHas('position', fn ($p) => $p->where('aktif', true)->whereColumn('roster_assignments.slot_no', '<=', 'roster_positions.max_petugas')
                            ->whereHas('group.area', fn ($a) => $a->where('event_id', $record->id)))
                        ->count()),
            ])
            ->defaultSort('date', 'desc')
            ->recordActions([
                EditAction::make()->label('Atur struktur'),
            ])
            ->toolbarActions([]);
    }

    public static function canCreate(): bool
    {
        return false; // event dibuat di Konten → Events
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRosterStructures::route('/'),
            'edit' => EditRosterStructure::route('/{record}/edit'),
        ];
    }
}
