<?php

namespace App\Filament\Resources\GtrDocuments\Pages;

use App\Filament\Resources\GtrDocuments\GtrDocumentResource;
use App\Support\GtrDocumentPdf;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditGtrDocument extends EditRecord
{
    protected static string $resource = GtrDocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Simpan dulu, lalu buka PDF di tab baru — supaya pratinjau selalu sesuai isi form.
            ActionGroup::make(collect($this->record->langs())->map(fn ($lang) => Action::make('preview_' . $lang)
                ->label('Pratinjau ' . ($lang === 'en' ? 'English' : 'Indonesia'))
                ->action(function () use ($lang) {
                    $this->save(shouldRedirect: false, shouldSendSavedNotification: false);
                    $url = route('gtr.docs.preview', [$this->record, $lang]) . '?t=' . time();
                    $this->js('window.open(' . json_encode($url) . ', "_blank")');
                }))->all())
                ->label('Simpan & Pratinjau PDF')
                ->icon(Heroicon::OutlinedEye)
                ->color('gray')
                ->button(),

            Action::make('publish')
                ->label(fn () => $this->record->is_published ? 'Simpan & Perbarui di Website' : 'Simpan & Publikasikan')
                ->icon(Heroicon::OutlinedGlobeAlt)
                ->color('success')
                ->requiresConfirmation()
                ->modalDescription('Isi form disimpan, PDF dibuat, lalu tampil di menu Race Info website GTR.')
                ->action(function () {
                    $this->save(shouldRedirect: false, shouldSendSavedNotification: false);
                    GtrDocumentPdf::publish($this->record->refresh());
                    Notification::make()->title($this->record->nav_label . ' tayang di website')->success()->send();
                }),

            GtrDocumentResource::unpublishAction()->record($this->record),

            DeleteAction::make(),
        ];
    }
}
