<?php

namespace Crommix\CRM\Filament\Resources\Leads\Pages;

use Crommix\CRM\Filament\Resources\Leads\LeadResource;
use Filament\Resources\Pages\CreateRecord;

class CreateLead extends CreateRecord
{
    protected static string $resource = LeadResource::class;
}
