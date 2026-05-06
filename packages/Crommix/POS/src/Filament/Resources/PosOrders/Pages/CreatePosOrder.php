<?php

namespace Crommix\POS\Filament\Resources\PosOrders\Pages;

use Crommix\POS\Filament\Resources\PosOrders\PosOrderResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePosOrder extends CreateRecord
{
    protected static string $resource = PosOrderResource::class;
}
