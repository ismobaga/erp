<?php

return [

    'enabled' => env('SUPPORT_ENABLED', true),

    'tenant_aware' => true,

    'permissions' => [
        'support.view',
        'support.create',
        'support.update',
        'support.delete',
        'support.tickets.view',
        'support.tickets.create',
        'support.tickets.assign',
        'support.tickets.close',
        'support.tickets.reply',
        'support.categories.manage',
    ],

];
