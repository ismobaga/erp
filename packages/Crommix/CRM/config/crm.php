<?php

return [

    'enabled' => env('CRM_ENABLED', true),

    'tenant_aware' => true,

    'permissions' => [
        'crm.view',
        'crm.create',
        'crm.update',
        'crm.delete',
        'crm.leads.view',
        'crm.leads.create',
        'crm.leads.update',
        'crm.leads.delete',
        'crm.leads.convert',
        'crm.contacts.view',
        'crm.contacts.create',
        'crm.contacts.update',
        'crm.contacts.delete',
    ],

];
