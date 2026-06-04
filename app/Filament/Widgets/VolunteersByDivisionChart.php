<?php

namespace App\Filament\Widgets;

use App\Models\Volunteer;
use Filament\Widgets\ChartWidget;

class VolunteersByDivisionChart extends ChartWidget
{
    protected ?string $heading = 'Volunteer per Bidang';

    protected ?string $description = 'Sebaran minat kepanitiaan GTR';

    protected static ?int $sort = -1;

    protected ?string $maxHeight = '280px';

    protected int|string|array $columnSpan = 1;

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getData(): array
    {
        $counts = array_fill_keys(array_keys(Volunteer::INTERESTS), 0);

        foreach (Volunteer::all(['interests']) as $volunteer) {
            foreach ((array) $volunteer->interests as $key) {
                if (array_key_exists($key, $counts)) {
                    $counts[$key]++;
                }
            }
        }

        return [
            'datasets' => [[
                'label' => 'Volunteer',
                'data' => array_values($counts),
                'backgroundColor' => ['#1B3FAE', '#E2F054', '#22C55E', '#F97316'],
                'borderWidth' => 0,
            ]],
            'labels' => array_values(Volunteer::INTERESTS),
        ];
    }
}
