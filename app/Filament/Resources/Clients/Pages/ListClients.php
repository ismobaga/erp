<?php

namespace App\Filament\Resources\Clients\Pages;

use App\Filament\Resources\Clients\ClientResource;
use App\Models\Client;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;

class ListClients extends ListRecords
{
    protected static string $resource = ClientResource::class;

    protected function getTableQuery(): Builder
    {
        return parent::getTableQuery()
            ->withSum('invoices', 'balance_due')
            ->withSum('payments', 'amount');
    }

    public function getTitle(): string|Htmlable
    {
        return 'Clients';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('quickCreate')
                ->label('Ajout rapide')
                ->icon('heroicon-o-bolt')
                ->color('gray')
                ->visible(fn (): bool => auth()->user()?->can('clients.create') ?? false)
                ->form([
                    Radio::make('type')
                        ->label('Type')
                        ->options([
                            'company' => 'Entreprise',
                            'individual' => 'Particulier',
                        ])
                        ->default('company')
                        ->inline()
                        ->required(),
                    TextInput::make('company_name')
                        ->label('Nom')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('contact_name')
                        ->label('Contact')
                        ->maxLength(255),
                    TextInput::make('phone')
                        ->label('Téléphone')
                        ->tel()
                        ->maxLength(255),
                ])
                ->action(function (array $data): void {
                    $client = Client::create([
                        'type' => $data['type'],
                        'company_name' => $data['company_name'],
                        'contact_name' => $data['contact_name'] ?: $data['company_name'],
                        'phone' => $data['phone'] ?? null,
                        'status' => 'active',
                        'created_by' => auth()->id(),
                        'updated_by' => auth()->id(),
                    ]);

                    Notification::make()
                        ->title('Client ajouté')
                        ->body('Client '.$client->company_name.' enregistré.')
                        ->success()
                        ->send();
                }),
            CreateAction::make()->label('Nouveau client'),
        ];
    }
}
