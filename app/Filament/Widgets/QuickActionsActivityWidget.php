<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Clients\ClientResource;
use App\Filament\Resources\Expenses\ExpenseResource;
use App\Filament\Resources\Invoices\InvoiceResource;
use App\Filament\Resources\Payments\PaymentResource;
use App\Models\ActivityLog;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class QuickActionsActivityWidget extends Widget
{
    protected string $view = 'filament.widgets.quick-actions-activity';

    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected function getViewData(): array
    {
        $activities = [];

        if (Schema::hasTable('activity_logs')) {
            $activities = ActivityLog::query()
                ->latest('id')
                ->limit(6)
                ->get(['action', 'created_at'])
                ->map(fn (ActivityLog $log): array => [
                    'label' => Str::headline(str_replace('_', ' ', (string) $log->action)),
                    'time' => $log->created_at?->diffForHumans(),
                ])
                ->all();
        }

        return [
            'quickActions' => [
                ['label' => 'Nouveau client', 'url' => ClientResource::getUrl('create')],
                ['label' => 'Nouvelle facture', 'url' => InvoiceResource::getUrl('create')],
                ['label' => 'Enregistrer paiement', 'url' => PaymentResource::getUrl('create')],
                ['label' => 'Nouvelle dépense', 'url' => ExpenseResource::getUrl('create')],
            ],
            'activities' => $activities,
        ];
    }
}
