<?php

namespace App\Filament\Widgets;

use App\Models\GtrCategory;
use Filament\Widgets\ChartWidget;

class GtrCategoryChart extends ChartWidget
{
    protected ?string $heading = 'Pendaftar per Kategori';

    protected ?string $description = 'Sebaran pendaftaran tiap kategori GTR';

    protected static ?int $sort = -1;

    protected ?string $maxHeight = '280px';

    protected int|string|array $columnSpan = 1;

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getData(): array
    {
        $cats = GtrCategory::withCount('registrations')->orderBy('sort_order')->get();
        $palette = ['#1B3FAE', '#E2F054', '#22C55E', '#F97316', '#E53935', '#3F62D8', '#9333EA'];

        return [
            'datasets' => [[
                'label' => 'Pendaftar',
                'data' => $cats->pluck('registrations_count')->all(),
                'backgroundColor' => array_slice($palette, 0, max(1, $cats->count())),
                'borderWidth' => 0,
            ]],
            'labels' => $cats->pluck('name')->all(),
        ];
    }
}
