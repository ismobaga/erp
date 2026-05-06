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
        'hr.leave_types.view',
        'hr.leave_types.create',
        'hr.leave_types.update',
        'hr.leave_types.delete',
        'hr.leave_requests.view',
        'hr.leave_requests.create',
        'hr.leave_requests.approve',
        'hr.leave_requests.reject',
        'hr.leave_balances.view',
        'hr.leave_balances.update',
        'hr.contracts.view',
        'hr.contracts.create',
        'hr.contracts.update',
        'hr.contracts.delete',
        'hr.documents.view',
        'hr.documents.create',
        'hr.documents.view_confidential',
        'hr.documents.delete',
        'hr.attendance.view',
        'hr.attendance.create',
        'hr.attendance.update',
        'hr.timesheets.view',
        'hr.timesheets.approve',
        'hr.shifts.view',
        'hr.shifts.create',
        'hr.shifts.update',
        'hr.shifts.delete',
    ],

];
