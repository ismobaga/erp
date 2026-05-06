<?php

namespace Crommix\CRM\Filament\Resources\Leads\Pages;

use Crommix\CRM\Filament\Resources\Leads\LeadResource;
use Filament\Resources\Pages\ListRecords;

class ListLeads extends ListRecords
{
    protected static string $resource = LeadResource::class;
}
