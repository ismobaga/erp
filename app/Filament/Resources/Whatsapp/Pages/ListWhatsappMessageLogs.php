<?php

namespace App\Filament\Resources\Whatsapp\Pages;

use App\Filament\Resources\Whatsapp\WhatsappMessageLogResource;
use Filament\Resources\Pages\ListRecords;

class ListWhatsappMessageLogs extends ListRecords
{
    protected static string $resource = WhatsappMessageLogResource::class;
}
