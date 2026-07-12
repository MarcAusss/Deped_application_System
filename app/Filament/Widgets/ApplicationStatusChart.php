<?php

namespace App\Filament\Widgets;

use App\Models\Application;
use Filament\Widgets\ChartWidget;

class ApplicationStatusChart extends ChartWidget
{
    protected ?string $heading = 'Application Status';

    protected static ?int $sort = 2;

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
                        '#22c55e',
                        '#ef4444',
                    ],
                    'borderColor' => [
                        '#64748b',
                        '#d97706',
                        '#16a34a',
                        '#dc2626',
                    ],
                    'borderWidth' => 1,
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
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                ],
            ],
            'maintainAspectRatio' => false,
        ];
    }
}