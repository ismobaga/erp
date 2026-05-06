<?php

namespace Crommix\SaaS\Filament\Resources\TenantPlans\Pages;

use Crommix\SaaS\Filament\Resources\TenantPlans\TenantPlanResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTenantPlan extends CreateRecord
{
    protected static string $resource = TenantPlanResource::class;
}
