<?php

namespace Tests\Feature;

use Crommix\CRM\CRMServiceProvider;
use Crommix\CRM\Filament\Resources\Leads\LeadResource;
use Crommix\HR\Filament\Resources\Departments\DepartmentResource;
use Crommix\HR\Filament\Resources\Employees\EmployeeResource;
use Crommix\HR\Filament\Resources\LeaveRequests\LeaveRequestResource;
use Crommix\HR\Filament\Resources\LeaveTypes\LeaveTypeResource;
use Crommix\HR\HRServiceProvider;
use Crommix\Inventory\Filament\Resources\Products\ProductResource;
use Crommix\Inventory\InventoryServiceProvider;
use Crommix\POS\Filament\Resources\PosOrders\PosOrderResource;
use Crommix\POS\POSServiceProvider;
use Crommix\Payroll\Filament\Resources\PayrollRuns\PayrollRunResource;
use Crommix\Payroll\PayrollServiceProvider;
use Crommix\Procurement\Filament\Resources\PurchaseOrders\PurchaseOrderResource;
use Crommix\Procurement\ProcurementServiceProvider;
use Tests\TestCase;

class CrommixModulesDisabledTest extends TestCase
{
    public function test_non_core_crommix_modules_are_disabled_by_default(): void
    {
        $this->assertFalse(config('crommix_modules.hr'));
        $this->assertFalse(config('crommix_modules.payroll'));
        $this->assertFalse(config('crommix_modules.crm'));
        $this->assertFalse(config('crommix_modules.inventory'));
        $this->assertFalse(config('crommix_modules.procurement'));
        $this->assertFalse(config('crommix_modules.pos'));
    }

    public function test_non_core_crommix_service_providers_read_the_new_module_flags(): void
    {
        config()->set('crommix_modules.hr', false);
        config()->set('crommix_modules.payroll', false);
        config()->set('crommix_modules.crm', false);
        config()->set('crommix_modules.inventory', false);
        config()->set('crommix_modules.procurement', false);
        config()->set('crommix_modules.pos', false);

        $this->assertFalse(HRServiceProvider::isEnabled());
        $this->assertFalse(PayrollServiceProvider::isEnabled());
        $this->assertFalse(CRMServiceProvider::isEnabled());
        $this->assertFalse(InventoryServiceProvider::isEnabled());
        $this->assertFalse(ProcurementServiceProvider::isEnabled());
        $this->assertFalse(POSServiceProvider::isEnabled());
    }

    public function test_non_core_crommix_resources_do_not_register_navigation_or_allow_access_when_disabled(): void
    {
        config()->set('crommix_modules.hr', false);
        config()->set('crommix_modules.payroll', false);
        config()->set('crommix_modules.crm', false);
        config()->set('crommix_modules.inventory', false);
        config()->set('crommix_modules.procurement', false);
        config()->set('crommix_modules.pos', false);

        $resources = [
            EmployeeResource::class,
            DepartmentResource::class,
            LeaveRequestResource::class,
            LeaveTypeResource::class,
            PayrollRunResource::class,
            LeadResource::class,
            ProductResource::class,
            PurchaseOrderResource::class,
            PosOrderResource::class,
        ];

        foreach ($resources as $resource) {
            $this->assertFalse($resource::shouldRegisterNavigation());
            $this->assertFalse($resource::canAccess());
        }
    }
}
