<?php

namespace App\Filament\Evaluator\Widgets;

use App\Models\Application;
use Filament\Widgets\ChartWidget;

class ApplicationStatusChart extends ChartWidget
{
    protected ?string $heading = 'Queue by status';

    protected ?string $description = 'Current applications across the evaluation and decision stages.';

    protected int|string|array $columnSpan = 2;

    protected ?string $maxHeight = '310px';

    protected function getData(): array
    {
        $statuses = [
            'pending',
            'evaluated',
            'qualified',
            'disqualified',
        ];

        $counts = collect($statuses)->map(function ($status) {
            return Application::where('status', $status)->count();
        });

        return [
            'datasets' => [
                [
                    'label' => 'Applications',
                    'data' => $counts,
                    'backgroundColor' => [
                        '#94a3b8',
                        '#f59e0b',
                        '#10b981',
                        '#f43f5e',
                    ],
                    'borderWidth' => 0,
                    'borderRadius' => 8,
                    'borderSkipped' => false,
                    'barThickness' => 23,
                ],
            ],

            'labels' => [
                'Pending',
                'Evaluated',
                'Qualified',
                'Disqualified',
            ],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'indexAxis' => 'y',
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
            ],
            'scales' => [
                'x' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'precision' => 0,
                    ],
                    'grid' => [
                        'color' => 'rgba(148, 163, 184, 0.18)',
                    ],
                ],
                'y' => [
                    'grid' => [
                        'display' => false,
                    ],
                ],
            ],
        ];
    }
}
