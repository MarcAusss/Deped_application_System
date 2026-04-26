<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\User;
use App\Models\Application;

class AdminStats extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [

            // 👥 TOTAL EVALUATORS
            Stat::make('Evaluators', User::where('role', 'evaluator')->count()),

            // 🟡 PENDING APPLICATIONS
            Stat::make('Pending Applicants', Application::where('status', 'pending')->count())
                ->color('gray'),

            // 🟠 FOR ADMIN APPROVAL (evaluated)
            Stat::make('For Approval', Application::where('status', 'evaluated')->count())
                ->color('warning'),

            // 🟢 APPROVED
            Stat::make('Approved', Application::where('status', 'approved')->count())
                ->color('success'),

            // 🔴 REJECTED
            Stat::make('Rejected', Application::where('status', 'rejected')->count())
                ->color('danger'),
        ];
    }
}