<?php

namespace App\Filament\Widgets;

use App\Models\Application;
use Filament\Widgets\ChartWidget;

class ApplicationStatusChart extends ChartWidget
{
    protected string $view = 'filament.widgets.application-status-chart';

    protected ?string $heading = 'Recruitment pipeline';

    protected ?string $description = 'Current volume at every decision stage.';

    protected static ?int $sort = 2;

    protected ?string $maxHeight = null;

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
                        '#4f46e5',
                        '#15803d',
                        '#dc2626',
                    ],
                    'borderColor' => [
                        '#64748b',
                        '#3730a3',
                        '#14532d',
                        '#991b1b',
                    ],
                    'borderWidth' => 1,
                    'borderRadius' => 7,
                    'borderSkipped' => false,
                    'barPercentage' => 0.9,
                    'categoryPercentage' => 0.95,
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
            'maintainAspectRatio' => false,
            'layout' => [
                'padding' => 0,
            ],
            'scales' => [
                'x' => [
                    'title' => [
                        'display' => true,
                        'text' => 'Status',
                    ],
                    'grid' => [
                        'display' => false,
                    ],
                ],
                'y' => [
                    'beginAtZero' => true,
                    'title' => [
                        'display' => true,
                        'text' => 'Applications',
                    ],
                    'ticks' => [
                        'precision' => 0,
                    ],
                    'grid' => [
                        'color' => 'rgba(148, 163, 184, 0.18)',
                    ],
                ],
            ],
        ];
    }
}
