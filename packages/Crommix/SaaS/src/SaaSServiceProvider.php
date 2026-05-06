<?php

namespace Crommix\SaaS;

use Crommix\Core\Contracts\ModuleContract;
use Crommix\SaaS\Services\TenantManager;
use Illuminate\Support\ServiceProvider;

class SaaSServiceProvider extends ServiceProvider implements ModuleContract
{
    public static function isEnabled(): bool
    {
        return (bool) config('saas.enabled', true);
    }

    public static function permissions(): array
    {
        return (array) config('saas.permissions', []);
    }

    public static function moduleKey(): string
    {
        return 'saas';
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/saas.php', 'saas');

        // Register TenantManager as a singleton so it can be injected
        // or resolved anywhere in the application.
        $this->app->singleton(TenantManager::class);
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/saas.php' => config_path('saas.php'),
        ], 'saas-config');

        if (!static::isEnabled()) {
            return;
        }

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
    }
}
