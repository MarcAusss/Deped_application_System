<?php

namespace App\Filament\Evaluator\Widgets;

use App\Models\Application;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

class MonthlyApplicationsChart extends ChartWidget
{
    protected ?string $heading = 'Applications per Month';
protected int|string|array $columnSpan = 'full';
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
                ],
            ],

            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
