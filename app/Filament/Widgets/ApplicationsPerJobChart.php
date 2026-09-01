<?php

namespace App\Filament\Widgets;

use App\Models\JobPosition;
use Filament\Widgets\ChartWidget;

class ApplicationsPerJobChart extends ChartWidget
{
    protected ?string $heading = 'Applicant demand';

    protected ?string $description = 'Share of applications received by position.';

    protected static ?int $sort = 3;

    protected ?string $maxHeight = '315px';

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

        $total = $jobPositions->sum('applications_count');

        return [
            'datasets' => [
                [
                    'label' => 'Number of Applicants',
                    'data' => $jobPositions
                        ->pluck('applications_count')
                        ->values()
                        ->all(),

                    'backgroundColor' => [
                        '#4f46e5',
                        '#0f766e',
                        '#f59e0b',
                        '#64748b',
                        '#db2777',
                        '#0891b2',
                    ],
                    'borderColor' => '#ffffff',
                    'borderWidth' => 3,
                    'hoverOffset' => 5,
                ],
            ],

            'labels' => $jobPositions
                ->map(fn ($jobPosition) => $total > 0
                    ? "{$jobPosition->title} (" . round(($jobPosition->applications_count / $total) * 100) . '%)'
                    : "{$jobPosition->title} (0%)")
                ->values()
                ->all(),
        ];
    }

    protected function getType(): string
    {
        return 'pie';
    }

    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,

            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                    'labels' => [
                        'usePointStyle' => true,
                        'boxWidth' => 8,
                        'padding' => 16,
                    ],
                ],
            ],
        ];
    }
}
