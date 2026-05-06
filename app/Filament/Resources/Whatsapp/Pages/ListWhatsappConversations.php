<?php

namespace App\Filament\Resources\Whatsapp\Pages;

use App\Filament\Resources\Whatsapp\WhatsappConversationResource;
use Filament\Resources\Pages\ListRecords;

class ListWhatsappConversations extends ListRecords
{
    protected static string $resource = WhatsappConversationResource::class;
}
