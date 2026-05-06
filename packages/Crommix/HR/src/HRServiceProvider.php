<?php

namespace Crommix\HR;

use Crommix\Core\Contracts\ModuleContract;
use Illuminate\Support\ServiceProvider;

class HRServiceProvider extends ServiceProvider implements ModuleContract
{
    public static function isEnabled(): bool
    {
        return (bool) config('hr.enabled', true);
    }

    public static function permissions(): array
    {
        return (array) config('hr.permissions', []);
    }

    public static function moduleKey(): string
    {
        return 'hr';
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/hr.php', 'hr');
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/hr.php' => config_path('hr.php'),
        ], 'hr-config');

        if (! static::isEnabled()) {
            return;
        }

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
    }
}
