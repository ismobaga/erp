<?php

namespace Crommix\HR\Filament\Resources\Employees\Pages;

use Crommix\HR\Filament\Resources\Employees\EmployeeResource;
use Filament\Resources\Pages\ListRecords;

class ListEmployees extends ListRecords
{
    protected static string $resource = EmployeeResource::class;
}
