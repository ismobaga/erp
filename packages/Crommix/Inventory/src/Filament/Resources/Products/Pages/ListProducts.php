<?php

namespace Crommix\Inventory\Filament\Resources\Products\Pages;

use Crommix\Inventory\Filament\Resources\Products\ProductResource;
use Filament\Resources\Pages\ListRecords;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;
}
