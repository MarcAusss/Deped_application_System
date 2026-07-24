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

    protected ?string $heading = 'Recruitment portfolio';

    protected ?string $description = 'A concise view of workload, decisions, and staffing capacity.';

    protected int|array|null $columns = [
        'default' => 1,
        'sm' => 2,
        'lg' => 3,
        '2xl' => 6,
    ];

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
                ->icon('heroicon-o-user-group')
                ->description('Registered accounts')
                ->color('primary')
                ->extraAttributes(['class' => 'metric-indigo']),

            Stat::make(
                'Total Applicants',
                $totalApplications
            )
                ->icon('heroicon-o-document-duplicate')
                ->description('All submissions')
                ->color('info')
                ->extraAttributes(['class' => 'metric-teal']),

            Stat::make(
                'Awaiting Evaluation',
                $pendingApplications
            )
                ->icon('heroicon-o-clock')
                ->description('In evaluator queue')
                ->color('gray')
                ->extraAttributes(['class' => 'metric-slate']),

            Stat::make(
                'For Final Action',
                $forApprovalApplications
            )
                ->icon('heroicon-o-shield-check')
                ->description('Evaluation complete')
                ->color('warning')
                ->extraAttributes(['class' => 'metric-amber']),

            Stat::make(
                'Approved',
                $approvedApplications
            )
                ->icon('heroicon-o-check-circle')
                ->description('Final approvals')
                ->color('success')
                ->extraAttributes(['class' => 'metric-emerald']),

            Stat::make(
                'Rejected',
                $rejectedApplications
            )
                ->icon('heroicon-o-x-circle')
                ->description('Final rejections')
                ->color('danger')
                ->extraAttributes(['class' => 'metric-rose']),
        ];
    }
}
