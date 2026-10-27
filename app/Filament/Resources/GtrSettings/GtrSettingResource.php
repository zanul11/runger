<?php

namespace App\Filament\Resources\GtrSettings;

use App\Filament\Resources\GtrSettings\Pages\EditGtrSetting;
use App\Filament\Resources\GtrSettings\Pages\ListGtrSettings;
use App\Models\GtrSetting;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class GtrSettingResource extends Resource
{
    protected static ?string $model = GtrSetting::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;

    protected static ?string $navigationLabel = 'Header GTR';

    protected static ?string $modelLabel = 'Header GTR';

    protected static string|\UnitEnum|null $navigationGroup = 'GTR';

    protected static ?int $navigationSort = 0;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Header Halaman GTR')
                ->description('Foto & teks yang tampil di bagian atas (hero) halaman Gerung Trail Run.')
                ->columns(2)
                ->components([
                    FileUpload::make('header_image')
                        ->label('Foto Header')
                        ->image()
                        ->maxSize(5120)
                        ->disk('public')
                        ->directory('gtr/header')
                        ->imageEditor()
                        ->helperText('Gambar otomatis dikompres saat disimpan.')
                        ->saveUploadedFileUsing(fn (TemporaryUploadedFile $file) => \App\Support\ImageCompressor::store($file, 'gtr/header', 1920))
                        ->columnSpanFull(),
                    TextInput::make('eyebrow')
                        ->label('Teks Kecil (Eyebrow)')
                        ->placeholder('1st Edition · Bukit Keteri Trail')
                        ->columnSpanFull(),
                    TextInput::make('title')
                        ->label('Judul')
                        ->placeholder('Gerung Trail Run 2026')
                        ->columnSpanFull(),
                    TextInput::make('date_text')
                        ->label('Tanggal Event')
                        ->placeholder('Minggu, 29 November 2026'),
                    TextInput::make('categories_text')
                        ->label('Kategori')
                        ->placeholder('Kategori 7K & 15K'),
                    TextInput::make('location_text')
                        ->label('Lokasi')
                        ->placeholder('Bukit Keteri, Gerung — Lombok Barat')
                        ->columnSpanFull(),
                ]),

            Section::make('Race Pack & Race Day')
                ->description('Tampil di app peserta & email pengingat H-7 / H-1.')
                ->columns(2)
                ->components([
                    TextInput::make('racepack_location')
                        ->label('Lokasi Pengambilan Race Pack')
                        ->placeholder('Kantor Desa Banyu Urip, Gerung'),
                    TextInput::make('racepack_schedule')
                        ->label('Jadwal Pengambilan')
                        ->placeholder('Jumat–Sabtu, 27–28 Nov 2026 · 10.00–20.00 WITA'),
                    Textarea::make('racepack_note')
                        ->label('Catatan Race Pack')
                        ->placeholder('Bawa e-ticket (QR) & KTP. Boleh diwakilkan dengan surat kuasa + foto KTP peserta.')
                        ->rows(2)
                        ->columnSpanFull(),
                    TextInput::make('assembly_time')
                        ->label('Jam Kumpul Race Day')
                        ->placeholder('05.15 WITA'),
                    Toggle::make('results_published')
                        ->label('Publikasikan hasil resmi')
                        ->helperText('Aktifkan setelah hasil diverifikasi — halaman Results, hasil personal, e-sertifikat & kartu story jadi bisa diakses.'),
                ]),

            Section::make('Race Guide: Syarat Pengambilan Race Pack (RPC)')
                ->description('Tampil di slide "RPC Requirements" pada Race Guide. Satu baris = satu poin.')
                ->collapsible()
                ->columns(2)
                ->components([
                    Textarea::make('rpc_requirements')
                        ->label('Syarat Pengambilan')
                        ->placeholder("Email konfirmasi pendaftaran / e-ticket (QR).\nKTP / identitas asli sesuai data pendaftaran.\nMandatory gear sesuai kategori.")
                        ->rows(4)
                        ->columnSpanFull(),
                    Textarea::make('rpc_proxy_note')
                        ->label('Jika Peserta Tidak Bisa Hadir')
                        ->placeholder("Diwakilkan: bawa surat kuasa + fotokopi KTP peserta.\nDikirim: race pack dapat dikirim, ongkir ditanggung peserta.")
                        ->rows(3)
                        ->columnSpanFull(),
                    TextInput::make('rpc_contact')
                        ->label('Kontak Info Lebih Lanjut')
                        ->placeholder('+62 812-3456-7890 (Panitia GTR)')
                        ->columnSpanFull(),
                ]),

            Section::make('Race Guide: Race Village & Parkir')
                ->description('Tampil di slide "Race Village" dan "Parking" pada Race Guide. Satu baris = satu poin.')
                ->collapsible()
                ->columns(2)
                ->components([
                    TextInput::make('venue_name')
                        ->label('Nama Race Village / Venue')
                        ->placeholder('Kantor Desa Banyu Urip'),
                    TextInput::make('venue_maps_url')
                        ->label('Link Google Maps Venue')
                        ->url()
                        ->placeholder('https://maps.app.goo.gl/...')
                        ->helperText('Dijadikan QR code di slide Race Village.'),
                    TextInput::make('venue_address')
                        ->label('Alamat Venue')
                        ->placeholder('Jl. ..., Banyu Urip, Gerung, Lombok Barat')
                        ->columnSpanFull(),
                    Textarea::make('venue_directions')
                        ->label('Petunjuk Menuju Venue')
                        ->placeholder("Buka Google Maps, tuju \"Kantor Desa Banyu Urip\".\nIkuti penunjuk arah GTR dari pertigaan ...")
                        ->rows(3)
                        ->columnSpanFull(),
                    Textarea::make('venue_facilities')
                        ->label('Fasilitas Race Village')
                        ->placeholder("Toilet\nTenda medis\nPenitipan barang\nRefill air")
                        ->rows(3)
                        ->columnSpanFull(),
                    TextInput::make('parking_name')
                        ->label('Lokasi Parkir')
                        ->placeholder('Lapangan Desa Banyu Urip'),
                    Textarea::make('parking_notes')
                        ->label('Catatan Parkir')
                        ->placeholder("Parkir tidak berada di venue, ikuti arahan petugas.\nDari parkir ke venue ±200 m jalan kaki.")
                        ->rows(3)
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('header_image')->label('Foto')->disk('public'),
                TextColumn::make('title')->label('Judul'),
                TextColumn::make('date_text')->label('Tanggal'),
                TextColumn::make('updated_at')->label('Diperbarui')->dateTime(),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGtrSettings::route('/'),
            'edit' => EditGtrSetting::route('/{record}/edit'),
        ];
    }
}
