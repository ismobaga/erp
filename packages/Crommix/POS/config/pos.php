<?php

return [

    'enabled' => env('POS_ENABLED', true),

    'tenant_aware' => true,

    'permissions' => [
        'pos.view',
        'pos.create',
        'pos.update',
        'pos.delete',
        'pos.orders.view',
        'pos.orders.create',
        'pos.orders.process',
        'pos.sessions.open',
        'pos.sessions.close',
        'pos.refund',
    ],

];
