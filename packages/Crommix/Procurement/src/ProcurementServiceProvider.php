<?php

namespace Crommix\Procurement;

use Crommix\Core\Contracts\ModuleContract;
use Illuminate\Support\ServiceProvider;

class ProcurementServiceProvider extends ServiceProvider implements ModuleContract
{
    public static function isEnabled(): bool
    {
        return (bool) config('procurement.enabled', true);
    }

    public static function permissions(): array
    {
        return (array) config('procurement.permissions', []);
    }

    public static function moduleKey(): string
    {
        return 'procurement';
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/procurement.php', 'procurement');
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/procurement.php' => config_path('procurement.php'),
        ], 'procurement-config');

        if (!static::isEnabled()) {
            return;
        }

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
    }
}
