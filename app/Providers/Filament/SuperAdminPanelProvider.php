<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\Login;
use Filament\Enums\ThemeMode;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class SuperAdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('superadmin')
            ->path('superadmin')
            ->login(Login::class)
            ->brandName('CGL Admin')
            ->brandLogo(asset('images/cgl-logo.svg'))
            ->brandLogoHeight('2.5rem')
            ->font('Inter')
            ->darkMode()
            ->defaultThemeMode(ThemeMode::System)
            ->sidebarCollapsibleOnDesktop()
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->colors([
                'primary' => Color::hex('#3a1a2e'),
                'gray' => Color::Slate,
                'info' => Color::hex('#3a1a2e'),
                'success' => Color::hex('#3a9e44'),
                'warning' => Color::hex('#b98a06'),
                'danger' => Color::hex('#ba1a1a'),
            ])
            ->discoverResources(
                in: app_path('Filament/SuperAdmin/Resources'),
                for: 'App\Filament\SuperAdmin\Resources',
            )
            ->discoverPages(
                in: app_path('Filament/SuperAdmin/Pages'),
                for: 'App\Filament\SuperAdmin\Pages',
            )
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
