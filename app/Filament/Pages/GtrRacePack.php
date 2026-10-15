<?php

namespace App\Filament\Pages;

use App\Models\GtrCategory;
use App\Models\GtrRegistration;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

/**
 * Pengambilan race pack: scan QR e-ticket (nomor registrasi) atau cari BIB/nama,
 * lalu tandai diambil. Menampilkan progres per kategori & sisa ukuran jersey.
 */
class GtrRacePack extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGift;

    protected static string|\UnitEnum|null $navigationGroup = 'GTR';

    protected static ?string $navigationLabel = 'Race Pack';

    protected static ?int $navigationSort = 4;

    protected string $view = 'filament.pages.gtr-race-pack';

    public string $query = '';

    public ?int $regId = null;

    /** @var array<int,int> kandidat hasil cari nama */
    public array $candidates = [];

    public string $pickedBy = '';

    public function getTitle(): string
    {
        return 'Pengambilan Race Pack';
    }

    /** Cari dari hasil scan QR (nomor registrasi) / BIB / nama. */
    public function lookup(?string $code = null): void
    {
        $q = trim($code ?? $this->query);
        $this->query = $q;
        $this->regId = null;
        $this->candidates = [];
        $this->pickedBy = '';
        if ($q === '') {
            return;
        }

        $exact = GtrRegistration::where('nomor_registrasi', $q)->orWhere('bib_number', $q)->first();
        if ($exact) {
            $this->regId = $exact->id;

            return;
        }

        if (mb_strlen($q) >= 3) {
            $this->candidates = GtrRegistration::where('full_name', 'like', '%' . $q . '%')
                ->orderBy('full_name')->limit(10)->pluck('id')->all();
            if (count($this->candidates) === 1) {
                $this->regId = $this->candidates[0];
                $this->candidates = [];

                return;
            }
        }

        if (! $this->candidates) {
            Notification::make()->title('Peserta tidak ditemukan')->body($q)->warning()->send();
        }
    }

    public function pick(int $id): void
    {
        $this->regId = $id;
        $this->candidates = [];
    }

    public function markCollected(): void
    {
        $reg = $this->registration();
        if (! $reg || $reg->payment_status !== 'paid' || $reg->racepack_collected_at) {
            return;
        }

        $reg->forceFill([
            'racepack_collected_at' => now(),
            'racepack_collected_by' => auth()->id(),
            'racepack_picked_by' => trim($this->pickedBy) ?: null,
        ])->save();

        Notification::make()->title('Race pack diambil')->body($reg->full_name . ' · BIB ' . ($reg->bib_number ?: '-') . ' · Jersey ' . $reg->size)->success()->send();
        $this->query = '';
        $this->pickedBy = '';
        $this->dispatch('racepack-done');
    }

    public function undo(): void
    {
        $reg = $this->registration();
        if (! $reg) {
            return;
        }
        $reg->forceFill(['racepack_collected_at' => null, 'racepack_collected_by' => null, 'racepack_picked_by' => null])->save();
        Notification::make()->title('Status pengambilan dibatalkan')->body($reg->full_name)->send();
    }

    public function registration(): ?GtrRegistration
    {
        return $this->regId ? GtrRegistration::with(['category', 'racepackCollector'])->find($this->regId) : null;
    }

    public function candidateRows()
    {
        return GtrRegistration::with('category')->whereIn('id', $this->candidates)->orderBy('full_name')->get();
    }

    /** Progres per kategori (peserta lunas). */
    public function stats(): array
    {
        return GtrCategory::orderBy('sort_order')->get()->map(function (GtrCategory $c) {
            $paid = $c->registrations()->where('payment_status', 'paid');

            return [
                'name' => $c->distance ?: $c->name,
                'total' => (clone $paid)->count(),
                'collected' => (clone $paid)->whereNotNull('racepack_collected_at')->count(),
            ];
        })->filter(fn ($r) => $r['total'] > 0)->values()->all();
    }

    /** Ukuran jersey yang masih harus disiapkan (lunas, belum diambil). */
    public function sizesLeft(): array
    {
        $rows = GtrRegistration::where('payment_status', 'paid')->whereNull('racepack_collected_at')
            ->selectRaw('size, COUNT(*) as n')->groupBy('size')->pluck('n', 'size');

        return collect(GtrRegistration::SIZES)->mapWithKeys(fn ($s) => [$s => (int) ($rows[$s] ?? 0)])
            ->filter()->all();
    }

    public function recent()
    {
        return GtrRegistration::with(['category', 'racepackCollector'])->whereNotNull('racepack_collected_at')
            ->latest('racepack_collected_at')->limit(8)->get();
    }
}
