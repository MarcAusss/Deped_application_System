<?php

namespace App\Filament\Evaluator\Pages;

use App\Filament\Evaluator\Widgets\ApplicationStats;
use App\Filament\Evaluator\Widgets\ApplicationStatusChart;
use App\Filament\Evaluator\Widgets\ApplicationsPerPositionChart;
use App\Filament\Evaluator\Widgets\MonthlyApplicationsChart;
use App\Filament\Evaluator\Widgets\RecentApplications;
use App\Filament\Evaluator\Widgets\RecentEvaluations;
use App\Models\Application;
use App\Models\ApplicationEvaluation;
use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Contracts\View\View;

class Dashboard extends BaseDashboard
{
    protected static ?string $title = 'Evaluation Workspace';

    public function getHeader(): ?View
    {
        $evaluatorId = auth()->id();

        return view('filament.evaluator.pages.dashboard-header', [
            'pendingCount' => Application::where('status', 'pending')->count(),
            'evaluatedTodayCount' => ApplicationEvaluation::query()
                ->where('evaluator_id', $evaluatorId)
                ->whereDate('evaluated_at', today())
                ->count(),
            'recommendedCount' => ApplicationEvaluation::query()
                ->where('evaluator_id', $evaluatorId)
                ->where('recommended', true)
                ->count(),
        ]);
    }

    public function getPageClasses(): array
    {
        return ['recruitment-dashboard-page'];
    }

    public function getWidgets(): array
    {
        return [
            ApplicationStats::class,
            MonthlyApplicationsChart::class,
            ApplicationsPerPositionChart::class,
            ApplicationStatusChart::class,
            RecentApplications::class,
            RecentEvaluations::class,
        ];
    }

    public function getColumns(): int|array
    {
        return [
            'default' => 1,
            'lg' => 4,
        ];
    }
}
