<?php

namespace App\Filament\Evaluator\Widgets;

use App\Models\Application;
use Filament\Widgets\ChartWidget;

class ApplicationsPerPositionChart extends ChartWidget
{
    protected ?string $heading = 'Position workload';

    protected ?string $description = 'Application volume currently associated with each position.';

    protected int|string|array $columnSpan = 2;

    protected static ?int $sort = 2;

    protected ?string $maxHeight = '310px';

    protected function getData(): array
    {
        $applications = Application::query()
            ->selectRaw('job_position_id, COUNT(*) as total')
            ->groupBy('job_position_id')
            ->with('jobPosition')
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Applications',
                    'data' => $applications->pluck('total')->toArray(),
                    'backgroundColor' => '#4f46e5',
                    'borderColor' => '#3730a3',
                    'borderWidth' => 1,
                    'borderRadius' => 7,
                    'borderSkipped' => false,
                ],
            ],

            'labels' => $applications
                ->map(fn ($application) => $application->jobPosition?->title ?? 'Unknown')
                ->toArray(),
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
