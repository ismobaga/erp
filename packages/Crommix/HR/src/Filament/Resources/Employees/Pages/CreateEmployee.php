<?php

namespace Crommix\HR\Filament\Resources\Employees\Pages;

use Crommix\HR\Filament\Resources\Employees\EmployeeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateEmployee extends CreateRecord
{
    protected static string $resource = EmployeeResource::class;
}
