<?php

namespace Crommix\Procurement\Filament\Resources\PurchaseOrders\Pages;

use Crommix\Procurement\Filament\Resources\PurchaseOrders\PurchaseOrderResource;
use Filament\Resources\Pages\EditRecord;

class EditPurchaseOrder extends EditRecord
{
    protected static string $resource = PurchaseOrderResource::class;
}
