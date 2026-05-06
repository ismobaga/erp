<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\EditProfile;
use App\Filament\Pages\Dashboard;
use App\Filament\Widgets\CompanySwitcher;
use App\Http\Middleware\SetCurrentCompany;
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

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->profile(EditProfile::class, isSimple: false)
            ->brandName('CGL')
            ->brandLogo(asset('images/cgl-logo.svg'))
            ->brandLogoHeight('2.5rem')
            ->font('Inter')
            ->darkMode()
            ->defaultThemeMode(ThemeMode::System)
            ->sidebarCollapsibleOnDesktop()
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->colors([
                'primary' => Color::hex('#1a3b2e'),
                'gray' => Color::Slate,
                'info' => Color::hex('#1a3b2e'),
                'success' => Color::hex('#3a9e44'),
                'warning' => Color::hex('#b98a06'),
                'danger' => Color::hex('#ba1a1a'),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverResources(in: base_path('packages/Crommix/HR/src/Filament/Resources'), for: 'Crommix\HR\Filament\Resources')
            ->discoverResources(in: base_path('packages/Crommix/CRM/src/Filament/Resources'), for: 'Crommix\CRM\Filament\Resources')
            ->discoverResources(in: base_path('packages/Crommix/Inventory/src/Filament/Resources'), for: 'Crommix\Inventory\Filament\Resources')
            ->discoverResources(in: base_path('packages/Crommix/Payroll/src/Filament/Resources'), for: 'Crommix\Payroll\Filament\Resources')
            ->discoverResources(in: base_path('packages/Crommix/Procurement/src/Filament/Resources'), for: 'Crommix\Procurement\Filament\Resources')
            ->discoverResources(in: base_path('packages/Crommix/POS/src/Filament/Resources'), for: 'Crommix\POS\Filament\Resources')
            ->discoverResources(in: base_path('packages/Crommix/Support/src/Filament/Resources'), for: 'Crommix\Support\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                CompanySwitcher::class,
            ])
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
                SetCurrentCompany::class,
            ]);
    }
}
