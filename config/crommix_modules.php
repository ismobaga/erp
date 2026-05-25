<?php

return [
    'core' => env('CROMMIX_CORE_ENABLED', true),
    'support' => env('CROMMIX_SUPPORT_ENABLED', true),
    'saas' => env('CROMMIX_SAAS_ENABLED', false),
    'blog' => env('CROMMIX_BLOG_ENABLED', false),

    'hr' => env('CROMMIX_HR_ENABLED', false),
    'payroll' => env('CROMMIX_PAYROLL_ENABLED', false),
    'crm' => env('CROMMIX_CRM_ENABLED', false),
    'inventory' => env('CROMMIX_INVENTORY_ENABLED', false),
    'procurement' => env('CROMMIX_PROCUREMENT_ENABLED', false),
    'pos' => env('CROMMIX_POS_ENABLED', false),
];
