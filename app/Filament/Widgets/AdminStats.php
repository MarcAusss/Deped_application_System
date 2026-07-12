<?php

namespace App\Filament\Widgets;

use App\Models\Application;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AdminStats extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $totalApplications = Application::count();
        $pendingApplications = Application::where('status', 'pending')->count();
        $forApprovalApplications = Application::where('status', 'evaluated')->count();
        $approvedApplications = Application::where('status', 'approved')->count();
        $rejectedApplications = Application::where('status', 'rejected')->count();

        return [
            Stat::make(
                'Evaluators',
                User::where('role', 'evaluator')->count()
            )
                ->description('Registered evaluator accounts')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('primary'),

            Stat::make(
                'Total Applicants',
                $totalApplications
            )
                ->description('All submitted applications')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('info'),

            Stat::make(
                'Pending Applicants',
                $pendingApplications
            )
                ->description('Waiting for evaluation')
                ->descriptionIcon('heroicon-m-clock')
                ->color('gray'),

            Stat::make(
                'For Approval',
                $forApprovalApplications
            )
                ->description('Already evaluated')
                ->descriptionIcon('heroicon-m-document-check')
                ->color('warning'),

            Stat::make(
                'Approved',
                $approvedApplications
            )
                ->description('Approved applications')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make(
                'Rejected',
                $rejectedApplications
            )
                ->description('Rejected applications')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger'),
        ];
    }
}