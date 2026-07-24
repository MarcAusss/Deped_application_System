<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;

use App\Filament\Evaluator\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Width;

use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class EvaluatorPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('evaluator')
            ->path('evaluator')
            ->authGuard('web')
            ->viteTheme('resources/css/filament/shared/theme.css')
            ->brandName('DepEd Recruitment')
            ->brandLogo(fn () => view('filament.components.brand', [
                'workspace' => 'Evaluation Desk',
            ]))
            ->brandLogoHeight('2.5rem')
            ->darkMode(false)
            ->colors([
                'primary' => Color::Indigo,
                'info' => Color::Cyan,
                'success' => Color::Emerald,
                'warning' => Color::Amber,
                'danger' => Color::Rose,
                'gray' => Color::Slate,
            ])
            ->maxContentWidth(Width::Full)
            ->sidebarCollapsibleOnDesktop()
            ->sidebarWidth('18rem')
            ->discoverResources(
                in: app_path('Filament/Evaluator/Resources'),
                for: 'App\\Filament\\Evaluator\\Resources'
            )

            ->discoverPages(
                in: app_path('Filament/Evaluator/Pages'),
                for: 'App\\Filament\\Evaluator\\Pages'
            )

            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(
                in: app_path('Filament/Evaluator/Widgets'),
                for: 'App\\Filament\\Evaluator\\Widgets'
            )
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])

            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
