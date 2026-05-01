<?php

namespace App\Filament\Resources\Whatsapp\Pages;

use App\Filament\Resources\Whatsapp\WhatsappMessageLogResource;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ViewWhatsappMessageLog extends ViewRecord
{
    protected static string $resource = WhatsappMessageLogResource::class;

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Détails du message')->schema([
                TextEntry::make('phone')->label('Téléphone'),
                TextEntry::make('type')->badge()->label('Type'),
                TextEntry::make('status')->badge()->label('Statut'),
                TextEntry::make('message')->label('Message')->columnSpanFull(),
                TextEntry::make('gowa_message_id')->label('ID GoWA'),
                TextEntry::make('sent_at')->dateTime()->label('Envoyé le'),
                TextEntry::make('error_message')->label('Erreur')->columnSpanFull(),
            ])->columns(2),
        ]);
    }
}
