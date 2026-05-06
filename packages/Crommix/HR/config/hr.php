<?php

return [

    /*
    |--------------------------------------------------------------------------
    | HR Module
    |--------------------------------------------------------------------------
    |
    | Set "enabled" to false to completely disable all HR routes, migrations,
    | Filament resources, and permissions.
    |
    */

    'enabled' => env('HR_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Tenant Awareness
    |--------------------------------------------------------------------------
    |
    | When true, all HR models are scoped to the current company.
    |
    */

    'tenant_aware' => true,

    /*
    |--------------------------------------------------------------------------
    | Permissions
    |--------------------------------------------------------------------------
    |
    | List of permissions registered by this module. Seeded automatically
    | on first boot if using the spatie/laravel-permission package.
    |
    */

    'permissions' => [
        'hr.view',
        'hr.create',
        'hr.update',
        'hr.delete',
        'hr.employees.view',
        'hr.employees.create',
        'hr.employees.update',
        'hr.employees.delete',
        'hr.departments.view',
        'hr.departments.create',
        'hr.departments.update',
        'hr.departments.delete',
        'hr.leave_requests.view',
        'hr.leave_requests.create',
        'hr.leave_requests.approve',
    ],

];
