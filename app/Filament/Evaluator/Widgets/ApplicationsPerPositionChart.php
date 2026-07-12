<?php

namespace App\Filament\Evaluator\Widgets;

use App\Models\Application;
use Filament\Widgets\ChartWidget;

class ApplicationsPerPositionChart extends ChartWidget
{
    protected ?string $heading = 'Applications per Position';
protected int|string|array $columnSpan = 2;
    protected static ?int $sort = 2;

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
}
