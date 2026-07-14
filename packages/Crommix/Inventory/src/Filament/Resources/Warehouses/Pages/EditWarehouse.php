<?php

namespace Crommix\Inventory\Filament\Resources\Warehouses\Pages;

use Crommix\Inventory\Filament\Resources\Warehouses\WarehouseResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditWarehouse extends EditRecord
{
    protected static string $resource = WarehouseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->visible(fn (): bool => ! $this->getRecord()->stockMovements()->exists()),
        ];
    }
}
