<?php

namespace App\Filament\Pages;

use App\Models\GtrCategory;
use App\Models\GtrRegistration;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

/** Cetak BIB peserta GTR (PDF siap cetak, A5 landscape) per kategori atau per nomor BIB. */
class GtrBibPrint extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedIdentification;

    protected static string|\UnitEnum|null $navigationGroup = 'GTR';

    protected static ?string $navigationLabel = 'Cetak BIB';

    protected static ?int $navigationSort = 8;

    protected string $view = 'filament.pages.gtr-bib-print';

    /** Panel elevasi dibalik 180° (terbaca pelari saat menunduk). */
    public bool $flip = true;

    public function getTitle(): string
    {
        return 'Cetak BIB';
    }

    public function getViewData(): array
    {
        $counts = GtrRegistration::where('payment_status', 'paid')
            ->selectRaw('gtr_category_id, COUNT(*) as paid, SUM(CASE WHEN bib_number IS NULL OR bib_number = \'\' THEN 1 ELSE 0 END) as no_bib')
            ->groupBy('gtr_category_id')->get()->keyBy('gtr_category_id');

        return [
            'categories' => GtrCategory::orderBy('sort_order')->get()->map(fn (GtrCategory $c) => [
                'id' => $c->id,
                'name' => $c->name,
                'label' => trim(($c->distance ?: $c->name) . ($c->tag ? ' · ' . $c->tag : '')),
                'color' => $c->color ?: '#E53935',
                'paid' => (int) ($counts[$c->id]->paid ?? 0),
                'no_bib' => (int) ($counts[$c->id]->no_bib ?? 0),
                'has_ele' => count($c->elevation_profile ?? []) >= 2,
            ]),
        ];
    }
}
