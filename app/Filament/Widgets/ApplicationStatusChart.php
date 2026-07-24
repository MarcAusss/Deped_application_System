<?php

namespace App\Filament\Widgets;

use App\Models\Application;
use Filament\Widgets\ChartWidget;

class ApplicationStatusChart extends ChartWidget
{
    protected ?string $heading = 'Recruitment pipeline';

    protected ?string $description = 'Current volume at every decision stage.';

    protected static ?int $sort = 2;

    protected ?string $maxHeight = '315px';

    protected int|string|array $columnSpan = [
        'default' => 'full',
        'md' => 1,
    ];

    protected function getData(): array
    {
        $pending = Application::where('status', 'pending')->count();
        $evaluated = Application::where('status', 'evaluated')->count();
        $approved = Application::where('status', 'approved')->count();
        $rejected = Application::where('status', 'rejected')->count();

        return [
            'datasets' => [
                [
                    'label' => 'Applications',
                    'data' => [
                        $pending,
                        $evaluated,
                        $approved,
                        $rejected,
                    ],
                    'backgroundColor' => [
                        '#94a3b8',
                        '#f59e0b',
                        '#10b981',
                        '#f43f5e',
                    ],
                    'borderWidth' => 0,
                    'borderRadius' => 8,
                    'borderSkipped' => false,
                    'barThickness' => 24,
                ],
            ],

            'labels' => [
                'Pending',
                'For Approval',
                'Approved',
                'Rejected',
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
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
            ],
            'indexAxis' => 'y',
            'maintainAspectRatio' => false,
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
