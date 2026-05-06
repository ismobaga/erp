<?php

use App\Providers\AppServiceProvider;
use App\Providers\Filament\AdminPanelProvider;
use Crommix\Blog\BlogServiceProvider;
use Crommix\Core\CoreServiceProvider;
use Crommix\HR\HRServiceProvider;
use Crommix\Payroll\PayrollServiceProvider;
use Crommix\CRM\CRMServiceProvider;
use Crommix\Inventory\InventoryServiceProvider;
use Crommix\Procurement\ProcurementServiceProvider;
use Crommix\POS\POSServiceProvider;
use Crommix\SaaS\SaaSServiceProvider;
use Crommix\Support\SupportServiceProvider;

return [
    AppServiceProvider::class,
    AdminPanelProvider::class,
    BlogServiceProvider::class,
    CoreServiceProvider::class,
    HRServiceProvider::class,
    PayrollServiceProvider::class,
    CRMServiceProvider::class,
    InventoryServiceProvider::class,
    ProcurementServiceProvider::class,
    POSServiceProvider::class,
    SaaSServiceProvider::class,
    SupportServiceProvider::class,
];
