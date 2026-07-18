<?php

namespace App\Filament\Resources\GtrSponsors\Schemas;

use App\Models\GtrSponsor;
use App\Support\ImageCompressor;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class GtrSponsorForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nama Sponsor')
                    ->required()
                    ->maxLength(191),
                TextInput::make('role')
                    ->label('Keterangan')
                    ->placeholder('mis. Official Jersey')
                    ->datalist([
                        'Official Jersey',
                        'Official Timekeeper',
                        'Official Medal',
                        'Official Hydration',
                        'Official Medical',
                        'Media Partner',
                        'Community Partner',
                        'Supported By',
                    ])
                    ->maxLength(191)
                    ->helperText('Peran/kategori sponsor. Kosongkan bila tidak ada.'),
                FileUpload::make('logo')
                    ->label('Logo')
                    ->image()
                    ->maxSize(5120)
                    ->disk('public')
                    ->directory('gtr/sponsors')
                    ->imageEditor()
                    ->helperText('Logo otomatis dikompres (WebP) saat disimpan. Idealnya PNG transparan.')
                    ->saveUploadedFileUsing(fn (TemporaryUploadedFile $file) => ImageCompressor::store($file, 'gtr/sponsors', 800)),
                TextInput::make('link')
                    ->label('Website / Link')
                    ->url()
                    ->placeholder('https://…'),
                Select::make('tier')
                    ->label('Tier')
                    ->options(GtrSponsor::TIERS)
                    ->default('partner')
                    ->native(false)
                    ->required(),
                TextInput::make('sort_order')
                    ->label('Urutan')
                    ->numeric()
                    ->default(0),
                Toggle::make('is_active')
                    ->label('Aktif')
                    ->default(true),
            ]);
    }
}
