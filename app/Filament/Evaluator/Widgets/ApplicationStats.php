<?php

namespace App\Filament\Evaluator\Widgets;

use App\Models\Application;
use App\Models\ApplicationEvaluation;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ApplicationStats extends StatsOverviewWidget
{
    protected int|string|array $columnSpan = 'full';
    protected function getStats(): array
    {
        return [

            Stat::make(
                'Total Applications',
                Application::count()
            )
                ->description('All submitted applications')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('primary'),

            Stat::make(
                'Pending',
                Application::where('status', 'pending')->count()
            )
                ->description('Waiting for evaluation')
                ->descriptionIcon('heroicon-m-clock')
                ->color('gray'),

            Stat::make(
                'Evaluated',
                Application::where('status', 'evaluated')->count()
            )
                ->description('Already evaluated')
                ->descriptionIcon('heroicon-m-clipboard-document-check')
                ->color('warning'),

            Stat::make(
                'Approved',
                Application::where('status', 'approved')->count()
            )
                ->description('Qualified applicants')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make(
                'Rejected',
                Application::where('status', 'rejected')->count()
            )
                ->description('Not qualified')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger'),

            Stat::make(
                'Recommended',
                ApplicationEvaluation::where('recommended', true)->count()
            )
                ->description('Recommended by evaluators')
                ->descriptionIcon('heroicon-m-star')
                ->color('info'),

        ];
    }
}
