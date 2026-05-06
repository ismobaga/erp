<?php

namespace Crommix\Procurement\Filament\Resources\PurchaseOrders\Pages;

use Crommix\Procurement\Filament\Resources\PurchaseOrders\PurchaseOrderResource;
use Filament\Resources\Pages\ListRecords;

class ListPurchaseOrders extends ListRecords
{
    protected static string $resource = PurchaseOrderResource::class;
}
