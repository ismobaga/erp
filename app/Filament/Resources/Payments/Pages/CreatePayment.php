<?php

namespace App\Filament\Resources\Payments\Pages;

use App\Actions\ApplyPaymentAction;
use App\Filament\Resources\Payments\PaymentResource;
use App\Models\Payment;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;

class CreatePayment extends CreateRecord
{
    protected static string $resource = PaymentResource::class;

    protected static bool $canCreateAnother = false;

    public function getTitle(): string|Htmlable
    {
        return 'Enregistrer un paiement';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['recorded_by'] = auth()->id();
        $data['reference'] = $data['reference'] ?: PaymentResource::generatePaymentReference($data['payment_date'] ?? null);

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        $payment = Payment::make($data);

        app(ApplyPaymentAction::class)->execute($payment);

        return $payment;
    }

    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()->label('Valider');
    }

    protected function getCancelFormAction(): Action
    {
        return parent::getCancelFormAction()->label('Annuler');
    }
}
