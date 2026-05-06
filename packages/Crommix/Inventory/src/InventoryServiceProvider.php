<?php

namespace Crommix\Inventory;

use Crommix\Core\Contracts\ModuleContract;
use Illuminate\Support\ServiceProvider;

class InventoryServiceProvider extends ServiceProvider implements ModuleContract
{
    public static function isEnabled(): bool
    {
        return (bool) config('inventory.enabled', true);
    }

    public static function permissions(): array
    {
        return (array) config('inventory.permissions', []);
    }

    public static function moduleKey(): string
    {
        return 'inventory';
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/inventory.php', 'inventory');
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/inventory.php' => config_path('inventory.php'),
        ], 'inventory-config');

        if (!static::isEnabled()) {
            return;
        }

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
    }
}
