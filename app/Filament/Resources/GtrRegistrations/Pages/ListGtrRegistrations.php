<?php

namespace App\Filament\Resources\GtrRegistrations\Pages;

use App\Filament\Resources\GtrRegistrations\GtrRegistrationResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListGtrRegistrations extends ListRecords
{
    protected static string $resource = GtrRegistrationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Input Peserta')->icon(Heroicon::OutlinedUserPlus),
            // Unduh/cetak formulir pendaftaran kosong untuk pendaftaran offline.
            Action::make('downloadForm')
                ->label('Form Pendaftaran')
                ->icon(Heroicon::OutlinedDocumentArrowDown)
                ->color('gray')
                ->url(fn (): string => route('gtr.registration-form', ['print' => 1]))
                ->openUrlInNewTab(),
        ];
    }
}
