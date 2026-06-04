<?php

namespace App\Filament\Widgets;

use App\Models\Volunteer;
use Filament\Widgets\ChartWidget;

class VolunteerTrendChart extends ChartWidget
{
    protected ?string $heading = 'Tren Pendaftaran Volunteer';

    protected ?string $description = '14 hari terakhir';

    protected static ?int $sort = -2;

    protected ?string $maxHeight = '280px';

    protected int|string|array $columnSpan = 1;

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $labels = [];
        $data = [];

        for ($i = 13; $i >= 0; $i--) {
            $day = now('Asia/Makassar')->subDays($i);
            $labels[] = $day->locale('id')->translatedFormat('d M');
            $data[] = Volunteer::whereDate('created_at', $day->toDateString())->count();
        }

        return [
            'datasets' => [[
                'label' => 'Pendaftar',
                'data' => $data,
                'borderColor' => '#1B3FAE',
                'backgroundColor' => 'rgba(27, 63, 174, 0.15)',
                'fill' => true,
                'tension' => 0.35,
                'pointBackgroundColor' => '#E2F054',
                'pointRadius' => 3,
            ]],
            'labels' => $labels,
        ];
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => ['precision' => 0],
                ],
            ],
            'plugins' => [
                'legend' => ['display' => false],
            ],
        ];
    }
}
