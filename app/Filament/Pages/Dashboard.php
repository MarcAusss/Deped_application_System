<?php

namespace App\Filament\Pages;

use App\Models\Application;
use App\Models\JobPosition;
use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Contracts\View\View;

class Dashboard extends BaseDashboard
{
    protected static string $routePath = '/';

    protected static ?string $title = 'Recruitment Overview';

    public function getHeader(): ?View
    {
        return view('filament.pages.dashboard-header', [
            'pendingCount' => Application::where('status', 'pending')->count(),
            'approvalCount' => Application::where('status', 'evaluated')->count(),
            'openPositionCount' => JobPosition::where('is_open', true)->count(),
        ]);
    }

    public function getPageClasses(): array
    {
        return ['recruitment-dashboard-page'];
    }

    public function getColumns(): int|array
    {
        return [
            'default' => 1,
            'md' => 2,
        ];
    }
}
