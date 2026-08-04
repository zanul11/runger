<?php

namespace App\Filament\Pages;

use App\Models\GtrRegistration;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

/**
 * Pratinjau format email (konfirmasi pendaftaran & pembayaran) di admin.
 */
class GtrEmailPreview extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;

    protected static string|\UnitEnum|null $navigationGroup = 'GTR';

    protected static ?string $navigationLabel = 'Preview Email';

    protected static ?int $navigationSort = 8;

    protected string $view = 'filament.pages.gtr-email-preview';

    public string $type = 'payment';

    public ?string $registrationId = null;

    public function getTitle(): string
    {
        return 'Preview Format Email';
    }

    /** Opsi peserta untuk dropdown (terbaru dulu). */
    public function registrationOptions(): array
    {
        return GtrRegistration::query()
            ->latest('id')
            ->limit(100)
            ->get()
            ->mapWithKeys(fn (GtrRegistration $r) => [
                $r->id => trim(($r->nomor_registrasi ?: '#' . $r->id) . ' · ' . $r->full_name
                    . ' (' . ucfirst($r->payment_status) . ')'),
            ])
            ->all();
    }

    /** URL render email sesuai pilihan. */
    public function getPreviewUrl(): string
    {
        return route('gtr.email-preview', array_filter([
            'type' => $this->type,
            'registration' => $this->registrationId,
        ]));
    }
}
