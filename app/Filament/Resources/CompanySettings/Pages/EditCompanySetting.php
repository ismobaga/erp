<?php

namespace App\Filament\Resources\CompanySettings\Pages;

use App\Filament\Resources\CompanySettings\CompanySettingResource;
use App\Models\Company;
use App\Services\Whatsapp\GowaClient;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;

class EditCompanySetting extends EditRecord
{
    protected static string $resource = CompanySettingResource::class;

    public function getTitle(): string|Htmlable
    {
        return 'Modifier les paramètres société';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('waStatus')
                ->label('WhatsApp statut')
                ->icon('heroicon-o-signal')
                ->color('info')
                ->action(function (GowaClient $gowa): void {
                    /** @var Company $company */
                    $company = $this->getRecord();

                    if (blank($company->whatsapp_device_id)) {
                        Notification::make()
                            ->title('Device ID manquant')
                            ->body('Renseignez d\'abord le WhatsApp Device ID dans la fiche société.')
                            ->warning()
                            ->send();

                        return;
                    }

                    try {
                        $response = $gowa->checkStatus((string) $company->whatsapp_device_id);

                        $connected = data_get($response, 'results.is_connected');
                        $loggedIn = data_get($response, 'results.is_logged_in');

                        Notification::make()
                            ->title('Statut WhatsApp récupéré')
                            ->body(sprintf(
                                'Connecté: %s | Authentifié: %s',
                                $connected ? 'oui' : 'non',
                                $loggedIn ? 'oui' : 'non',
                            ))
                            ->success()
                            ->send();
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->title('Échec récupération statut')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
            Action::make('waListDevices')
                ->label('Lister appareils')
                ->icon('heroicon-o-list-bullet')
                ->color('gray')
                ->action(function (GowaClient $gowa): void {
                    try {
                        $response = $gowa->listDevices();
                        $devices = collect((array) data_get($response, 'results', []))
                            ->map(fn(array $item): string => (string) ($item['id'] ?? $item['device_id'] ?? ''))
                            ->filter()
                            ->values();

                        Notification::make()
                            ->title('Appareils GoWA')
                            ->body($devices->isEmpty() ? 'Aucun appareil enregistré.' : $devices->implode(', '))
                            ->success()
                            ->send();
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->title('Échec récupération appareils')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
            Action::make('waAddDevice')
                ->label('Ajouter appareil')
                ->icon('heroicon-o-plus')
                ->color('success')
                ->form([
                    TextInput::make('device_id')
                        ->label('Device ID (optionnel)')
                        ->placeholder('my-custom-device-id'),
                ])
                ->action(function (array $data, GowaClient $gowa): void {
                    /** @var Company $company */
                    $company = $this->getRecord();

                    try {
                        $response = $gowa->addDevice($data['device_id'] ?? null);
                        $newDeviceId = (string) (data_get($response, 'results.id')
                            ?? data_get($response, 'results.device_id')
                            ?? ($data['device_id'] ?? ''));

                        if (filled($newDeviceId)) {
                            $company->update(['whatsapp_device_id' => $newDeviceId]);
                            $this->refreshFormData(['whatsapp_device_id']);
                        }

                        Notification::make()
                            ->title('Appareil ajouté')
                            ->body(filled($newDeviceId) ? 'Device ID défini: ' . $newDeviceId : 'Appareil ajouté avec succès.')
                            ->success()
                            ->send();
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->title('Échec ajout appareil')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
            Action::make('waLoginQr')
                ->label('Login QR')
                ->icon('heroicon-o-qr-code')
                ->color('warning')
                ->action(function (GowaClient $gowa): void {
                    /** @var Company $company */
                    $company = $this->getRecord();

                    if (blank($company->whatsapp_device_id)) {
                        Notification::make()->title('Device ID manquant')->warning()->send();

                        return;
                    }

                    try {
                        $response = $gowa->login((string) $company->whatsapp_device_id);
                        $qrLink = (string) data_get($response, 'results.qr_link', '');

                        Notification::make()
                            ->title('Login QR initié')
                            ->body(filled($qrLink) ? $qrLink : 'QR généré côté GoWA.')
                            ->success()
                            ->send();
                    } catch (\Throwable $e) {
                        Notification::make()->title('Échec login QR')->body($e->getMessage())->danger()->send();
                    }
                }),
            Action::make('waLoginCode')
                ->label('Login code')
                ->icon('heroicon-o-device-phone-mobile')
                ->color('warning')
                ->form([
                    TextInput::make('phone')
                        ->label('Téléphone (international)')
                        ->placeholder('628912344551')
                        ->required(),
                ])
                ->action(function (array $data, GowaClient $gowa): void {
                    /** @var Company $company */
                    $company = $this->getRecord();

                    if (blank($company->whatsapp_device_id)) {
                        Notification::make()->title('Device ID manquant')->warning()->send();

                        return;
                    }

                    try {
                        $response = $gowa->loginWithCode((string) $company->whatsapp_device_id, (string) $data['phone']);
                        $pairCode = (string) data_get($response, 'results.pair_code', '');

                        Notification::make()
                            ->title('Pairing code généré')
                            ->body(filled($pairCode) ? $pairCode : 'Code généré côté GoWA.')
                            ->success()
                            ->send();
                    } catch (\Throwable $e) {
                        Notification::make()->title('Échec login code')->body($e->getMessage())->danger()->send();
                    }
                }),
            Action::make('waReconnect')
                ->label('Reconnecter')
                ->icon('heroicon-o-arrow-path')
                ->color('info')
                ->action(function (GowaClient $gowa): void {
                    /** @var Company $company */
                    $company = $this->getRecord();

                    if (blank($company->whatsapp_device_id)) {
                        Notification::make()->title('Device ID manquant')->warning()->send();

                        return;
                    }

                    try {
                        $gowa->reconnect((string) $company->whatsapp_device_id);
                        Notification::make()->title('Reconnexion demandée')->success()->send();
                    } catch (\Throwable $e) {
                        Notification::make()->title('Échec reconnexion')->body($e->getMessage())->danger()->send();
                    }
                }),
            Action::make('waLogout')
                ->label('Déconnecter')
                ->icon('heroicon-o-power')
                ->color('danger')
                ->requiresConfirmation()
                ->action(function (GowaClient $gowa): void {
                    /** @var Company $company */
                    $company = $this->getRecord();

                    if (blank($company->whatsapp_device_id)) {
                        Notification::make()->title('Device ID manquant')->warning()->send();

                        return;
                    }

                    try {
                        $gowa->logout((string) $company->whatsapp_device_id);
                        Notification::make()->title('Déconnexion demandée')->success()->send();
                    } catch (\Throwable $e) {
                        Notification::make()->title('Échec déconnexion')->body($e->getMessage())->danger()->send();
                    }
                }),
            Action::make('waRemoveDevice')
                ->label('Supprimer appareil')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->form([
                    TextInput::make('device_id')
                        ->label('Device ID à supprimer')
                        ->default(fn(): ?string => $this->getRecord()?->whatsapp_device_id)
                        ->required(),
                    Placeholder::make('warning')
                        ->label('Attention')
                        ->content('Cette action supprime le device côté GoWA. Vérifiez le Device ID avant confirmation.'),
                ])
                ->action(function (array $data, GowaClient $gowa): void {
                    /** @var Company $company */
                    $company = $this->getRecord();

                    try {
                        $deviceId = (string) $data['device_id'];
                        $gowa->removeDevice($deviceId);

                        if ((string) $company->whatsapp_device_id === $deviceId) {
                            $company->update(['whatsapp_device_id' => null]);
                            $this->refreshFormData(['whatsapp_device_id']);
                        }

                        Notification::make()->title('Appareil supprimé')->success()->send();
                    } catch (\Throwable $e) {
                        Notification::make()->title('Échec suppression appareil')->body($e->getMessage())->danger()->send();
                    }
                }),
            DeleteAction::make(),
        ];
    }

    protected function getSaveFormAction(): Action
    {
        return parent::getSaveFormAction()->label('Enregistrer les modifications');
    }
}
