<?php

namespace App\Filament\Evaluator\Widgets;

use App\Models\Application;
use App\Models\ApplicationEvaluation;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ApplicationStats extends StatsOverviewWidget
{
    protected int|string|array $columnSpan = 'full';

    protected ?string $heading = 'Your evaluation desk';

    protected ?string $description = 'Workload and recommendation activity assigned to the evaluation role.';

    protected int|array|null $columns = [
        'default' => 1,
        'sm' => 2,
        'xl' => 4,
    ];

    protected function getStats(): array
    {
        $evaluatorId = auth()->id();

        return [
            Stat::make(
                'Total Applications',
                Application::count()
            )
                ->icon('heroicon-o-document-duplicate')
                ->description('All submissions')
                ->color('primary')
                ->extraAttributes(['class' => 'metric-indigo']),

            Stat::make(
                'Pending Queue',
                Application::where('status', 'pending')->count()
            )
                ->icon('heroicon-o-clock')
                ->description('Waiting for review')
                ->color('warning')
                ->extraAttributes(['class' => 'metric-amber']),

            Stat::make(
                'Evaluated by You',
                ApplicationEvaluation::where('evaluator_id', $evaluatorId)->count()
            )
                ->icon('heroicon-o-clipboard-document-check')
                ->description('Completed assessments')
                ->color('info')
                ->extraAttributes(['class' => 'metric-teal']),

            Stat::make(
                'Recommended by You',
                ApplicationEvaluation::query()
                    ->where('evaluator_id', $evaluatorId)
                    ->where('recommended', true)
                    ->count()
            )
                ->icon('heroicon-o-star')
                ->description('Positive recommendations')
                ->color('success')
                ->extraAttributes(['class' => 'metric-emerald']),
        ];
    }
}
