<?php

namespace App\Filament\Resources\GtrPhotos\Pages;

use App\Filament\Resources\GtrPhotos\GtrPhotoResource;
use App\Models\GtrPhoto;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ListGtrPhotos extends ListRecords
{
    protected static string $resource = GtrPhotoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('upload')
                ->label('Upload Foto')
                ->icon('heroicon-o-arrow-up-tray')
                ->modalHeading('Upload foto race (massal)')
                ->modalDescription('Pilih banyak foto sekaligus. Foto otomatis dikompres. Setelah itu ketik nomor BIB di kolom tabel.')
                ->schema([
                    FileUpload::make('files')
                        ->label('Foto')
                        ->image()
                        ->multiple()
                        ->maxFiles(60)
                        ->maxSize(15360)
                        ->disk('public')
                        ->directory('gtr/photos')
                        ->saveUploadedFileUsing(fn (TemporaryUploadedFile $file) => \App\Support\ImageCompressor::store($file, 'gtr/photos', 2000, 82))
                        ->required(),
                    TextInput::make('spot')->label('Lokasi foto (opsional)')->placeholder('CP1 / Finish / Tanjakan Keteri'),
                    TextInput::make('photographer')->label('Fotografer (opsional)'),
                    TextInput::make('bibs')->label('BIB untuk semua foto ini (opsional)')->placeholder('Isi bila semua foto berisi pelari yang sama'),
                ])
                ->action(function (array $data) {
                    $bibs = GtrPhoto::parseBibs($data['bibs'] ?? '');
                    foreach ((array) $data['files'] as $path) {
                        GtrPhoto::create([
                            'path' => $path,
                            'spot' => $data['spot'] ?? null,
                            'photographer' => $data['photographer'] ?? null,
                            'bibs' => $bibs ?: null,
                        ]);
                    }
                    Notification::make()->title(count((array) $data['files']) . ' foto diunggah')->success()->send();
                }),
        ];
    }
}
