<?php

namespace App\Filament\Resources\Clients\Pages;

use App\Filament\Resources\Clients\ClientResource;
use App\Models\Client;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;

class ViewClientDetails extends ViewRecord
{
    protected static string $resource = ClientResource::class;

    protected string $view = 'filament.resources.clients.pages.view-client-details';

    public function getTitle(): string|Htmlable
    {
        return 'Fiche client';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Retour à la liste')
                ->color('gray')
                ->url(ClientResource::getUrl('index')),
            EditAction::make()->label('Modifier'),
        ];
    }

    public function getClientTypeLabel(): string
    {
        /** @var Client $client */
        $client = $this->getRecord();

        return $client->type === 'individual' ? 'Particulier' : 'Entreprise';
    }

    public function getClientStatusLabel(): string
    {
        /** @var Client $client */
        $client = $this->getRecord();

        return match ($client->status) {
            'lead' => 'Prospect',
            'active' => 'Actif',
            'customer' => 'Client',
            'inactive' => 'Inactif',
            default => 'Non défini',
        };
    }
}
