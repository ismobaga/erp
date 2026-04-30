<?php

namespace App\Filament\Widgets;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Payment;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Schema;
use Throwable;

class OnboardingChecklistWidget extends Widget
{
    protected string $view = 'filament.widgets.onboarding-checklist';

    protected static ?int $sort = 0;

    protected int|string|array $columnSpan = 'full';

    /**
     * Only show the checklist if at least one step is incomplete.
     */
    public static function canView(): bool
    {
        try {
            if (! Schema::hasTable('clients') || ! Schema::hasTable('invoices') || ! Schema::hasTable('payments')) {
                return false;
            }

            return ! Client::query()->exists()
                || ! Invoice::query()->exists()
                || ! Payment::query()->exists();
        } catch (Throwable) {
            return false;
        }
    }

    public function getSteps(): array
    {
        try {
            $hasClient = Client::query()->exists();
            $hasInvoice = Invoice::query()->exists();
            $hasPayment = Payment::query()->exists();
        } catch (Throwable) {
            return [];
        }

        return [
            [
                'done' => $hasClient,
                'label' => 'Ajouter votre premier client',
                'description' => 'Créez le profil d\'un client ou d\'une entreprise.',
                'url' => route('filament.admin.resources.clients.create'),
                'cta' => 'Créer un client',
            ],
            [
                'done' => $hasInvoice,
                'label' => 'Créer votre première facture',
                'description' => 'Émettez une facture officielle rattachée à un client.',
                'url' => route('filament.admin.resources.invoices.create'),
                'cta' => 'Créer une facture',
            ],
            [
                'done' => $hasPayment,
                'label' => 'Enregistrer un paiement',
                'description' => 'Confirmez un encaissement et rapprochez-le d\'une facture.',
                'url' => route('filament.admin.resources.payments.create'),
                'cta' => 'Enregistrer un paiement',
            ],
        ];
    }
}
