<?php

namespace Crommix\POS\Filament\Resources\PosOrders\Pages;

use Crommix\POS\Filament\Resources\PosOrders\PosOrderResource;
use Filament\Resources\Pages\ListRecords;

class ListPosOrders extends ListRecords
{
    protected static string $resource = PosOrderResource::class;
}
