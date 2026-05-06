<?php

return [

    'enabled' => env('PROCUREMENT_ENABLED', true),

    'tenant_aware' => true,

    'permissions' => [
        'procurement.view',
        'procurement.create',
        'procurement.update',
        'procurement.delete',
        'procurement.purchase_orders.view',
        'procurement.purchase_orders.create',
        'procurement.purchase_orders.approve',
        'procurement.purchase_orders.receive',
        'procurement.suppliers.view',
        'procurement.suppliers.manage',
    ],

];
