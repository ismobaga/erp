<?php

namespace Crommix\CRM;

use Crommix\Core\Contracts\ModuleContract;
use Illuminate\Support\ServiceProvider;

class CRMServiceProvider extends ServiceProvider implements ModuleContract
{
    public static function isEnabled(): bool
    {
        return (bool) config('crm.enabled', true);
    }

    public static function permissions(): array
    {
        return (array) config('crm.permissions', []);
    }

    public static function moduleKey(): string
    {
        return 'crm';
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/crm.php', 'crm');
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/crm.php' => config_path('crm.php'),
        ], 'crm-config');

        if (!static::isEnabled()) {
            return;
        }

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
    }
}
