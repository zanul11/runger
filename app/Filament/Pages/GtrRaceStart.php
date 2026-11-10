<?php

namespace App\Filament\Pages;

use App\Casts\DateTimeMs;
use App\Models\GtrCategory;
use App\Services\GtrTimingAdmin;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;

/**
 * Start BERSAMA semua kategori (gun time resmi). Tidak ada scan di garis start: semua peserta
 * mengikuti gun time ini. Tombol START mencatat jam server saat ditekan (presisi ms), dengan
 * konfirmasi; "Koreksi" untuk mengisi jam tembak sebenarnya bila tombol telat ditekan.
 */
class GtrRaceStart extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFlag;

    protected static string|\UnitEnum|null $navigationGroup = 'Race Timing';

    protected static ?string $navigationLabel = 'Start Race';

    protected static ?int $navigationSort = 0;

    protected string $view = 'filament.pages.gtr-race-start';

    public function getTitle(): string
    {
        return 'Start Race (Gun Time)';
    }

    /** @return Collection<int, GtrCategory> */
    public function getCategories(): Collection
    {
        return GtrCategory::with('gunFiredBy:id,name')
            ->where('is_active', true)
            ->withCount(['registrations as eligible_count' => fn ($q) => $q->raceEligible()])
            ->orderBy('sort_order')->orderBy('id')->get();
    }

    public function startAllAction(): Action
    {
        return Action::make('startAll')
            ->label('START')
            ->icon(Heroicon::OutlinedPlay)
            ->color('success')
            ->size('xl')
            ->requiresConfirmation()
            ->modalHeading(fn () => 'Start '.$this->getCategories()->pluck('name')->join(' & ').' sekarang?')
            ->modalDescription('Jam server saat tombol ditekan menjadi gun time resmi SEMUA kategori.')
            ->modalSubmitActionLabel('YA, START')
            ->action(function () {
                $fired = app(GtrTimingAdmin::class)->fireGunAll(auth()->user());
                $at = GtrCategory::where('is_active', true)->value('gun_fired_at');

                $fired
                    ? Notification::make()->title('START')->body(DateTimeMs::toCarbon($at)->format('H:i:s.v').' WITA')->success()->send()
                    : Notification::make()->title('Race sudah di-start')->body('Pakai "Koreksi" untuk mengubah jam.')->warning()->send();
            });
    }

    public function correctAllAction(): Action
    {
        return Action::make('correctAll')
            ->label('Koreksi gun time')
            ->icon(Heroicon::OutlinedPencilSquare)
            ->color('gray')
            ->modalHeading('Koreksi gun time (semua kategori)')
            ->modalDescription('Isi jam tembak sebenarnya (WITA). Semua hasil dihitung ulang.')
            ->fillForm(fn () => [
                'fired_at' => (GtrCategory::where('is_active', true)->whereNotNull('gun_fired_at')->first()?->gun_fired_at ?? now())->format(DateTimeMs::FORMAT),
            ])
            ->schema([
                TextInput::make('fired_at')
                    ->label('Gun time (YYYY-MM-DD HH:MM:SS.mmm)')
                    ->required()
                    ->regex('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}(\.\d{1,3})?$/'),
            ])
            ->action(function (array $data) {
                app(GtrTimingAdmin::class)->fireGunAll(auth()->user(), DateTimeMs::toCarbon($data['fired_at']), force: true);

                Notification::make()->title('Gun time dikoreksi')->body($data['fired_at'].' WITA')->success()->send();
            });
    }
}
