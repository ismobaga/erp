<?php

namespace Crommix\Support;

use Crommix\Core\Contracts\ModuleContract;
use Illuminate\Support\ServiceProvider;

class SupportServiceProvider extends ServiceProvider implements ModuleContract
{
    public static function isEnabled(): bool
    {
        return (bool) config('support.enabled', true);
    }

    public static function permissions(): array
    {
        return (array) config('support.permissions', []);
    }

    public static function moduleKey(): string
    {
        return 'support';
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/support.php', 'support');
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/support.php' => config_path('support.php'),
        ], 'support-config');

        if (!static::isEnabled()) {
            return;
        }

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
    }
}
