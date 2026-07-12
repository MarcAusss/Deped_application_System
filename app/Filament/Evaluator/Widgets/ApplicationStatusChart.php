<?php

namespace App\Filament\Evaluator\Widgets;

use App\Models\Application;
use Filament\Widgets\ChartWidget;

class ApplicationStatusChart extends ChartWidget
{
    protected ?string $heading = 'Application Status Distribution';
    protected int|string|array $columnSpan = 2;
    protected function getData(): array
    {
        $statuses = [
            'pending',
            'evaluated',
            'approved',
            'rejected',
        ];

        $counts = collect($statuses)->map(function ($status) {
            return Application::where('status', $status)->count();
        });

        return [
            'datasets' => [
                [
                    'label' => 'Applications',
                    'data' => $counts,
                ],
            ],

            'labels' => [
                'Pending',
                'Evaluated',
                'Approved',
                'Rejected',
            ],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
