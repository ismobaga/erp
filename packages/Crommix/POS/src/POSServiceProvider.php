<?php

namespace Crommix\POS;

use Crommix\Core\Contracts\ModuleContract;
use Crommix\Inventory\Services\InventoryService;
use Crommix\POS\Services\PosService;
use Illuminate\Support\ServiceProvider;

class POSServiceProvider extends ServiceProvider implements ModuleContract
{
    public static function isEnabled(): bool
    {
        return (bool) config('pos.enabled', true);
    }

    public static function permissions(): array
    {
        return (array) config('pos.permissions', []);
    }

    public static function moduleKey(): string
    {
        return 'pos';
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/pos.php', 'pos');

        $this->app->bind(PosService::class, function ($app): PosService {
            return new PosService($app->make(InventoryService::class));
        });
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/pos.php' => config_path('pos.php'),
        ], 'pos-config');

        if (!static::isEnabled()) {
            return;
        }

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
    }
}
