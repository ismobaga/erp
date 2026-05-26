<?php

namespace App\Filament\Widgets;

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
                ['label' => 'Nouveau client', 'url' => route('filament.admin.resources.clients.create')],
                ['label' => 'Nouvelle facture', 'url' => route('filament.admin.resources.invoices.create')],
                ['label' => 'Enregistrer paiement', 'url' => route('filament.admin.resources.payments.create')],
                ['label' => 'Nouvelle dépense', 'url' => route('filament.admin.resources.expenses.create')],
            ],
            'activities' => $activities,
        ];
    }
}
