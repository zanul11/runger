<?php

namespace App\Filament\Resources\GtrDocuments;

use App\Filament\Resources\GtrDocuments\Pages\CreateGtrDocument;
use App\Filament\Resources\GtrDocuments\Pages\EditGtrDocument;
use App\Filament\Resources\GtrDocuments\Pages\ListGtrDocuments;
use App\Models\GtrDocument;
use App\Support\GtrDocumentPdf;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

/**
 * Dokumen peserta GTR (Waiver, PAR-Q, Surat Kuasa, …): diedit dengan editor teks penuh, dipratinjau sebagai PDF,
 * lalu dipublikasikan untuk diunduh dari menu Race Info di website GTR.
 */
class GtrDocumentResource extends Resource
{
    protected static ?string $model = GtrDocument::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $navigationLabel = 'Dokumen Peserta';

    protected static ?string $modelLabel = 'Dokumen';

    protected static ?string $pluralModelLabel = 'Dokumen Peserta';

    protected static string|\UnitEnum|null $navigationGroup = 'GTR';

    protected static ?int $navigationSort = 7;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('Dokumen')->columnSpanFull()->persistTabInQueryString()->tabs([
                Tab::make('Pengaturan')->icon(Heroicon::OutlinedCog6Tooth)->schema([
                    Section::make()->columns(2)->schema([
                        TextInput::make('nav_label')
                            ->label('Nama di Menu Race Info')
                            ->placeholder('Waiver')
                            ->required()
                            ->maxLength(40),
                        TextInput::make('slug')
                            ->label('Slug URL')
                            ->helperText('Dipakai di alamat unduhan, mis. /gerung-trail-run/dokumen/waiver/id')
                            ->required()
                            ->alphaDash()
                            ->unique(ignoreRecord: true)
                            ->maxLength(60),
                        TextInput::make('title_id')
                            ->label('Judul Dokumen (Indonesia)')
                            ->required(),
                        TextInput::make('title_en')
                            ->label('Judul Dokumen (English)')
                            ->visible(fn (Get $get) => (bool) $get('has_en')),
                        Textarea::make('meta_id')
                            ->label('Info di Header (Indonesia)')
                            ->helperText('Satu baris = satu label, mis. tanggal, lokasi, kategori.')
                            ->rows(3),
                        Textarea::make('meta_en')
                            ->label('Info di Header (English)')
                            ->rows(3)
                            ->visible(fn (Get $get) => (bool) $get('has_en')),
                        TextInput::make('footer_id')
                            ->label('Catatan Kaki (Indonesia)')
                            ->placeholder('Serahkan formulir saat pengambilan race pack (RPC)'),
                        TextInput::make('footer_en')
                            ->label('Catatan Kaki (English)')
                            ->visible(fn (Get $get) => (bool) $get('has_en')),
                        TextInput::make('font_size')
                            ->label('Ukuran Huruf')
                            ->numeric()
                            ->minValue(70)
                            ->maxValue(140)
                            ->suffix('%')
                            ->default(100)
                            ->helperText('Bila isi tidak muat 1 halaman, huruf diperkecil otomatis (min. 66%).'),
                        TextInput::make('sort_order')
                            ->label('Urutan di Menu')
                            ->numeric()
                            ->default(0),
                        Toggle::make('has_en')
                            ->label('Ada versi English')
                            ->default(true)
                            ->live(),
                    ]),
                ]),
                Tab::make('Isi · Indonesia')->icon(Heroicon::OutlinedDocumentText)->schema([
                    self::editor('body_id'),
                ]),
                Tab::make('Isi · English')->icon(Heroicon::OutlinedLanguage)
                    ->visible(fn (Get $get) => (bool) $get('has_en'))
                    ->schema([
                        self::editor('body_en'),
                    ]),
            ]),
        ]);
    }

    /** Panduan singkat penanda khusus, tampil di bawah editor. */
    private static function guide(): HtmlString
    {
        $rows = [
            ['___', 'garis isian. Baris yang <b>diakhiri</b> ___ (mis. <i>Nama Lengkap ___</i>) diberi garis sampai ujung.'],
            ['[ ]', 'kotak centang, mis. <i>Kategori [ ] 7K [ ] 15K</i>. Baris yang <b>diawali</b> [ ] dan berisi beberapa pilihan ditata rapi dalam grid.'],
            ['Tanya? [ ] Ya [ ] Tidak', 'pertanyaan dengan pilihan rata kanan (teks sebelum kotak harus diakhiri tanda tanya).'],
            ['[ttd]', 'kotak tanda tangan (tulis di baris tersendiri).'],
            ['Judul H2 / H3', 'judul bagian bergaris merah / sub-judul merah kecil.'],
            ['Kutipan (blockquote)', 'kotak bergaris, mis. untuk data pelari & tanda tangan.'],
            ['Grid (ikon kolom)', 'membagi isi menjadi 2–3 kolom berdampingan.'],
        ];

        return new HtmlString('<div style="font-size:12.5px;line-height:1.7"><b>Penanda khusus untuk PDF:</b><br>'
            . implode('<br>', array_map(fn ($r) => '<code style="background:rgba(127,127,127,.15);padding:1px 6px;border-radius:4px">' . e($r[0]) . '</code> — ' . $r[1], $rows))
            . '</div>');
    }

    private static function editor(string $name): RichEditor
    {
        return RichEditor::make($name)
            ->hiddenLabel()
            ->toolbarButtons([
                ['bold', 'italic', 'underline', 'strike', 'link'],
                ['h2', 'h3'],
                ['alignStart', 'alignCenter', 'alignEnd', 'alignJustify'],
                ['bulletList', 'orderedList', 'blockquote', 'horizontalRule'],
                ['grid', 'gridDelete', 'table'],
                ['undo', 'redo'],
            ])
            ->extraInputAttributes(['style' => 'min-height: 65vh'])
            ->helperText(self::guide());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sort_order')->label('No.')->sortable(),
                TextColumn::make('nav_label')->label('Menu')->weight('bold')->searchable(),
                TextColumn::make('title_id')->label('Judul')->wrap(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->state(fn (GtrDocument $r) => ! $r->is_published ? 'Draf' : ($r->hasUnpublishedChanges() ? 'Ada perubahan' : 'Tayang'))
                    ->color(fn (string $state) => match ($state) { 'Tayang' => 'success', 'Ada perubahan' => 'warning', default => 'gray' }),
                TextColumn::make('published_at')->label('Dipublikasikan')->since()->placeholder('—'),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->recordActions([
                EditAction::make(),
                ActionGroup::make([
                    Action::make('previewId')
                        ->label('Pratinjau PDF (Indonesia)')
                        ->icon(Heroicon::OutlinedEye)
                        ->url(fn (GtrDocument $r) => route('gtr.docs.preview', [$r, 'id']))
                        ->openUrlInNewTab(),
                    Action::make('previewEn')
                        ->label('Pratinjau PDF (English)')
                        ->icon(Heroicon::OutlinedEye)
                        ->visible(fn (GtrDocument $r) => $r->has_en)
                        ->url(fn (GtrDocument $r) => route('gtr.docs.preview', [$r, 'en']))
                        ->openUrlInNewTab(),
                    self::publishAction(),
                    self::unpublishAction(),
                    DeleteAction::make(),
                ]),
            ]);
    }

    public static function publishAction(): Action
    {
        return Action::make('publish')
            ->label(fn (GtrDocument $record) => $record->is_published ? 'Perbarui di Website' : 'Publikasikan')
            ->icon(Heroicon::OutlinedGlobeAlt)
            ->color('success')
            ->requiresConfirmation()
            ->modalDescription('PDF dibuat dari isi yang tersimpan, lalu tampil di menu Race Info website GTR.')
            ->action(function (GtrDocument $record) {
                GtrDocumentPdf::publish($record);
                Notification::make()->title($record->nav_label . ' tayang di website')->success()->send();
            });
    }

    public static function unpublishAction(): Action
    {
        return Action::make('unpublish')
            ->label('Tarik dari Website')
            ->icon(Heroicon::OutlinedEyeSlash)
            ->color('danger')
            ->visible(fn (GtrDocument $record) => $record->is_published)
            ->requiresConfirmation()
            ->action(function (GtrDocument $record) {
                GtrDocumentPdf::unpublish($record);
                Notification::make()->title($record->nav_label . ' ditarik dari website')->warning()->send();
            });
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGtrDocuments::route('/'),
            'create' => CreateGtrDocument::route('/create'),
            'edit' => EditGtrDocument::route('/{record}/edit'),
        ];
    }
}
