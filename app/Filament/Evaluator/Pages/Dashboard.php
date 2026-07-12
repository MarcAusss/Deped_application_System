<?php

namespace App\Filament\Evaluator\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

use App\Filament\Evaluator\Widgets\ApplicationStats;
use App\Filament\Evaluator\Widgets\ApplicationsPerPositionChart;
use App\Filament\Evaluator\Widgets\ApplicationStatusChart;
use App\Filament\Evaluator\Widgets\MonthlyApplicationsChart;
use App\Filament\Evaluator\Widgets\RecentApplications;
use App\Filament\Evaluator\Widgets\RecentEvaluations;

class Dashboard extends BaseDashboard
{
    protected static ?string $title = 'Evaluator Dashboard';

    protected function getHeaderWidgets(): array
    {
        return [
            ApplicationStats::class,

            ApplicationsPerPositionChart::class,
            ApplicationStatusChart::class,

            MonthlyApplicationsChart::class,

            RecentApplications::class,

            RecentEvaluations::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 4;
    }
}
