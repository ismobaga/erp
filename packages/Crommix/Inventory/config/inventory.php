<?php

return [

    'enabled' => env('INVENTORY_ENABLED', true),

    'tenant_aware' => true,

    'permissions' => [
        'inventory.view',
        'inventory.create',
        'inventory.update',
        'inventory.delete',
        'inventory.products.view',
        'inventory.products.create',
        'inventory.products.update',
        'inventory.products.delete',
        'inventory.stock.adjust',
        'inventory.warehouses.view',
        'inventory.warehouses.manage',
    ],

];
