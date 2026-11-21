<?php

namespace App\Filament\Resources\RosterStructures\Pages;

use App\Filament\Resources\RosterStructures\RosterStructureResource;
use App\Models\Event;
use App\Models\RosterConsumptionSetting;
use App\Services\RosterService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class EditRosterStructure extends EditRecord
{
    protected static string $resource = RosterStructureResource::class;

    public function getTitle(): string
    {
        return 'Struktur Roster — '.$this->record->title;
    }

    protected function resolveRecord(int|string $key): Model
    {
        $event = parent::resolveRecord($key);
        RosterConsumptionSetting::forEvent($event); // setting konsumsi selalu ada (nilai default)

        return $event->load('rosterConsumption');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('openRoster')->label('Buka roster')->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                ->color('gray')->url('/crew/roster', shouldOpenInNewTab: true)
                ->visible(fn () => $this->record->roster_aktif),
            Action::make('copyStructure')
                ->label('Duplikat struktur dari event lain')
                ->icon(Heroicon::OutlinedDocumentDuplicate)
                ->color('gray')
                ->modalDescription('Menyalin area, grup, pos & setting konsumsi dari event lain (mis. tahun lalu) — tanpa nama petugas.')
                ->schema([
                    Select::make('source')->label('Event sumber')->required()->native(false)
                        ->options(fn () => Event::whereKeyNot($this->record->id)->whereHas('rosterAreas')->orderByDesc('date')->pluck('title', 'id')),
                    Toggle::make('replace')->label('Ganti struktur yang ada (petugas event ini ikut terhapus)')
                        ->visible(fn () => $this->record->rosterAreas()->exists()),
                ])
                ->modalSubmitActionLabel('Salin struktur')
                ->action(function (array $data): void {
                    try {
                        $n = app(RosterService::class)->copyStructure(Event::findOrFail($data['source']), $this->record, (bool) ($data['replace'] ?? false));
                    } catch (ValidationException $e) {
                        Notification::make()->danger()->title('Gagal menyalin')->body(collect($e->errors())->flatten()->first())->send();

                        return;
                    }
                    Notification::make()->success()->title("{$n} pos disalin")->send();
                    $this->redirect(RosterStructureResource::getUrl('edit', ['record' => $this->record]));
                }),
        ];
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Struktur roster disimpan';
    }
}
