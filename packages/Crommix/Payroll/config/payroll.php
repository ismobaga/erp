<?php

return [

    'enabled' => env('PAYROLL_ENABLED', true),

    'tenant_aware' => true,

    'permissions' => [
        'payroll.view',
        'payroll.create',
        'payroll.update',
        'payroll.delete',
        'payroll.runs.view',
        'payroll.runs.create',
        'payroll.runs.process',
        'payroll.runs.approve',
    ],

];
