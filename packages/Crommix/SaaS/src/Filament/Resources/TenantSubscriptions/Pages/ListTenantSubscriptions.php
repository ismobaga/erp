<?php

namespace Crommix\SaaS\Filament\Resources\TenantSubscriptions\Pages;

use Crommix\SaaS\Filament\Resources\TenantSubscriptions\TenantSubscriptionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTenantSubscriptions extends ListRecords
{
    protected static string $resource = TenantSubscriptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
