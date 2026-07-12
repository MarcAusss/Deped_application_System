<?php

namespace App\Filament\Widgets;

use App\Models\JobPosition;
use Filament\Widgets\ChartWidget;

class ApplicationsPerJobChart extends ChartWidget
{
    protected ?string $heading = 'Applications Per Job Position';

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = [
        'default' => 'full',
        'md' => 1,
    ];

    protected function getData(): array
    {
        $jobPositions = JobPosition::query()
            ->withCount('applications')
            ->orderByDesc('applications_count')
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Number of Applicants',
                    'data' => $jobPositions
                        ->pluck('applications_count')
                        ->values()
                        ->all(),

                    'backgroundColor' => '#3b82f6',
                    'borderColor' => '#2563eb',
                    'borderWidth' => 1,
                    'borderRadius' => 6,
                ],
            ],

            'labels' => $jobPositions
                ->pluck('title')
                ->values()
                ->all(),
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
                ],
            ],
        ];
    }
}