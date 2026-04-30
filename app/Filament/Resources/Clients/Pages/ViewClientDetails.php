<?php

namespace App\Filament\Resources\Clients\Pages;

use App\Filament\Resources\Clients\ClientResource;
use App\Models\Client;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
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
            Action::make('portalLink')
                ->label('Lien portail client')
                ->icon('heroicon-o-link')
                ->color('info')
                ->action(function (): void {
                    /** @var Client $client */
                    $client = $this->getRecord();

                    $url = $client->portalUrl();

                    $this->js("
                        if (navigator.clipboard) {
                            navigator.clipboard.writeText('".addslashes($url)."');
                        } else {
                            var el = document.createElement('textarea');
                            el.value = '".addslashes($url)."';
                            document.body.appendChild(el);
                            el.select();
                            document.execCommand('copy');
                            document.body.removeChild(el);
                        }
                    ");

                    Notification::make()
                        ->title('Lien portail copié')
                        ->body('Partagez ce lien sécurisé avec votre client.')
                        ->success()
                        ->send();
                }),
            Action::make('back')
                ->label('Retour à la liste')
                ->color('gray')
                ->url(ClientResource::getUrl('index')),
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
