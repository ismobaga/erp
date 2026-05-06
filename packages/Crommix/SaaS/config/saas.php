<?php

return [

    'enabled' => env('SAAS_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Default Plan & Trial
    |--------------------------------------------------------------------------
    | The slug of the plan automatically assigned to new tenants.
    */
    'default_plan' => env('SAAS_DEFAULT_PLAN', 'starter'),

    'trial_days' => max(0, (int) env('SAAS_TRIAL_DAYS', 14)),

    /*
    |--------------------------------------------------------------------------
    | Available Feature Keys
    |--------------------------------------------------------------------------
    | Feature keys that can be toggled per-tenant via feature flags or plans.
    */
    'features' => [
        'invoicing'      => 'Invoicing & Billing',
        'crm'            => 'CRM & Lead Management',
        'hr'             => 'HR & Payroll',
        'inventory'      => 'Inventory Management',
        'procurement'    => 'Procurement',
        'pos'            => 'Point of Sale',
        'projects'       => 'Project Management',
        'reporting'      => 'Advanced Reporting',
        'whatsapp'       => 'WhatsApp Integration',
        'portal'         => 'Client Portal',
        'multi_user'     => 'Multi-User Access',
        'api_access'     => 'API Access',
        'white_label'    => 'White-Label Branding',
        'custom_domain'  => 'Custom Domain',
        'payroll'        => 'Payroll',
        'blog'           => 'Blog / CMS',
    ],

    /*
    |--------------------------------------------------------------------------
    | Quota Metrics
    |--------------------------------------------------------------------------
    | Metric keys tracked per-tenant for usage quota enforcement.
    */
    'quota_metrics' => [
        'users'               => 'Users',
        'clients'             => 'Clients',
        'invoices_monthly'    => 'Monthly Invoices',
        'storage_mb'          => 'Storage (MB)',
        'api_calls_daily'     => 'Daily API Calls',
    ],

    /*
    |--------------------------------------------------------------------------
    | Onboarding Steps
    |--------------------------------------------------------------------------
    | Ordered steps for the tenant onboarding workflow.
    */
    'onboarding_steps' => [
        'company_profile'    => 'Complete company profile',
        'invite_users'       => 'Invite team members',
        'first_client'       => 'Add first client',
        'first_invoice'      => 'Create first invoice',
        'payment_method'     => 'Configure payment settings',
        'integrate_whatsapp' => 'Connect WhatsApp',
        'explore_modules'    => 'Explore additional modules',
    ],

    /*
    |--------------------------------------------------------------------------
    | Module – Feature Map
    |--------------------------------------------------------------------------
    | Maps ERP module keys to SaaS feature keys so the TenantManager
    | can check whether a given module is licensed for the tenant's plan.
    */
    'module_feature_map' => [
        'hr'          => 'hr',
        'payroll'     => 'payroll',
        'crm'         => 'crm',
        'inventory'   => 'inventory',
        'procurement' => 'procurement',
        'pos'         => 'pos',
        'support'     => 'portal',
        'blog'        => 'blog',
    ],

    /*
    |--------------------------------------------------------------------------
    | Permissions
    |--------------------------------------------------------------------------
    */
    'permissions' => [
        'saas.plans.view',
        'saas.plans.manage',
        'saas.subscriptions.view',
        'saas.subscriptions.manage',
        'saas.billing.view',
        'saas.onboarding.manage',
    ],

];
