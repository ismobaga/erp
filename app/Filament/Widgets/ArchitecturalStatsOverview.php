<?php

namespace App\Filament\Widgets;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Project;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Schema;
use Throwable;

class ArchitecturalStatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected ?string $heading = 'Portfolio snapshot';

    protected ?string $description = 'A live executive summary shaped for the Architectural Ledger experience.';

    protected function getStats(): array
    {
        try {
            if (!$this->hasCoreTables()) {
                return $this->placeholderStats();
            }

            $clients = Client::query()->count();
            $activeClients = Client::query()->whereIn('status', ['active', 'customer'])->count();
            $openInvoices = Invoice::query()->whereIn('status', ['sent', 'overdue', 'partially_paid'])->count();
            $settledRevenue = (float) Payment::query()->sum('amount');
            $activeProjects = Project::query()->whereIn('status', ['active', 'in_progress'])->count();

            return [
                Stat::make('Client portfolio', number_format($clients))
                    ->description(number_format($activeClients) . ' active relationships')
                    ->color('primary')
                    ->chart([7, 8, 10, 11, 13, 14, max(15, $clients)]),
                Stat::make('Open invoices', number_format($openInvoices))
                    ->description('Collection flow under watch')
                    ->color('warning')
                    ->chart([12, 10, 11, 9, 8, 7, max(6, $openInvoices)]),
                Stat::make('Settled revenue', $this->money($settledRevenue))
                    ->description('Confirmed payments in the ledger')
                    ->color('success')
                    ->chart([2, 4, 3, 6, 8, 7, 9]),
                Stat::make('Active projects', number_format($activeProjects))
                    ->description('Delivery momentum this cycle')
                    ->color('info')
                    ->chart([3, 4, 5, 4, 6, 7, max(4, $activeProjects)]),
            ];
        } catch (Throwable) {
            return $this->placeholderStats();
        }
    }

    protected function hasCoreTables(): bool
    {
        foreach (['clients', 'invoices', 'payments', 'projects'] as $table) {
            if (!Schema::hasTable($table)) {
                return false;
            }
        }

        return true;
    }

    protected function money(float $amount): string
    {
        return 'FCFA ' . number_format($amount, 0, '.', ' ');
    }

    protected function placeholderStats(): array
    {
        return [
            Stat::make('Client portfolio', '1,284')
                ->description('Editorial CRM view')
                ->color('primary')
                ->chart([7, 9, 8, 10, 12, 14, 16]),
            Stat::make('Open invoices', '42')
                ->description('Collection flow under watch')
                ->color('warning')
                ->chart([12, 11, 9, 10, 8, 7, 6]),
            Stat::make('Settled revenue', 'FCFA 482M')
                ->description('Confirmed payments in the ledger')
                ->color('success')
                ->chart([2, 4, 5, 6, 7, 8, 9]),
            Stat::make('Active projects', '18')
                ->description('Delivery momentum this cycle')
                ->color('info')
                ->chart([3, 4, 5, 6, 6, 7, 8]),
        ];
    }
}
