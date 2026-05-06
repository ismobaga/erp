<?php

namespace Crommix\Support\Filament\Resources\Tickets\Pages;

use Crommix\Support\Filament\Resources\Tickets\TicketResource;
use Filament\Resources\Pages\ListRecords;

class ListTickets extends ListRecords
{
    protected static string $resource = TicketResource::class;
}
