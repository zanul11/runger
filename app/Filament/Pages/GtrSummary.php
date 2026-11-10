<?php

namespace App\Filament\Pages;

use App\Support\GtrEventSummary;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

/** Ringkasan event GTR (angka gabungan) untuk presentasi ke desa, sponsor, dan pemda. */
class GtrSummary extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPresentationChartBar;

    protected static string|\UnitEnum|null $navigationGroup = 'GTR';

    protected static ?string $navigationLabel = 'Ringkasan Event';

    protected static ?int $navigationSort = 9;

    protected string $view = 'filament.pages.gtr-summary';

    public function getTitle(): string
    {
        return 'Ringkasan Event';
    }

    public function getViewData(): array
    {
        $s = GtrEventSummary::build();

        return ['s' => $s, 'text' => GtrEventSummary::text($s)];
    }
}
