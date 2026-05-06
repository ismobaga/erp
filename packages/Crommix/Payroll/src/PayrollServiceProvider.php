<?php

namespace Crommix\Payroll;

use Crommix\Core\Contracts\ModuleContract;
use Illuminate\Support\ServiceProvider;

class PayrollServiceProvider extends ServiceProvider implements ModuleContract
{
    public static function isEnabled(): bool
    {
        return (bool) config('payroll.enabled', true);
    }

    public static function permissions(): array
    {
        return (array) config('payroll.permissions', []);
    }

    public static function moduleKey(): string
    {
        return 'payroll';
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/payroll.php', 'payroll');
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/payroll.php' => config_path('payroll.php'),
        ], 'payroll-config');

        if (!static::isEnabled()) {
            return;
        }

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
    }
}
