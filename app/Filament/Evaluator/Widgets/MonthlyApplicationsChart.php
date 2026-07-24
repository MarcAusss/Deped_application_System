<?php

namespace App\Filament\Evaluator\Widgets;

use App\Models\Application;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

class MonthlyApplicationsChart extends ChartWidget
{
    protected ?string $heading = 'Submission activity';

    protected ?string $description = 'Six-month application intake trend for workload planning.';

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '285px';

    protected function getData(): array
    {
        $labels = [];
        $data = [];

        for ($i = 5; $i >= 0; $i--) {

            $month = Carbon::now()->subMonths($i);

            $labels[] = $month->format('M Y');

            $data[] = Application::whereYear('created_at', $month->year)
                ->whereMonth('created_at', $month->month)
                ->count();
        }

        return [
            'datasets' => [
                [
                    'label' => 'Applications',
                    'data' => $data,
                    'borderColor' => '#4f46e5',
                    'backgroundColor' => 'rgba(79, 70, 229, 0.12)',
                    'pointBackgroundColor' => '#ffffff',
                    'pointBorderColor' => '#4f46e5',
                    'pointBorderWidth' => 2,
                    'pointRadius' => 4,
                    'pointHoverRadius' => 6,
                    'borderWidth' => 3,
                    'fill' => true,
                    'tension' => 0.35,
                ],
            ],

            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'precision' => 0,
                    ],
                    'grid' => [
                        'color' => 'rgba(148, 163, 184, 0.18)',
                    ],
                ],
                'x' => [
                    'grid' => [
                        'display' => false,
                    ],
                ],
            ],
        ];
    }
}
