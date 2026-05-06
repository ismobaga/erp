<?php

namespace Crommix\HR\Filament\Resources\Employees\Pages;

use Crommix\HR\Filament\Resources\Employees\EmployeeResource;
use Filament\Resources\Pages\EditRecord;

class EditEmployee extends EditRecord
{
    protected static string $resource = EmployeeResource::class;
}
