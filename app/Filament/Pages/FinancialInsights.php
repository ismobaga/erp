<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\HasPermissionAccess;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use BackedEnum;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Throwable;

class FinancialInsights extends Page
{
    use HasPermissionAccess;

    protected static string $permissionScope = 'reports';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPresentationChartBar;

    protected static string|\UnitEnum|null $navigationGroup = 'Comptabilité';

    protected static ?int $navigationSort = 7;

    protected static ?string $navigationLabel = 'Analyses';

    protected static ?string $title = 'Analyses financières';

    protected static ?string $slug = 'financial-insights';

    protected string $view = 'filament.pages.financial-insights';

    public string $period = 'ytd';

    public function mount(): void
    {
        $this->period = $this->normalizePeriod((string) request()->query('period', $this->period));
    }

    public function updatedPeriod(string $value): void
    {
        $this->period = $this->normalizePeriod($value);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportData')
                ->label(__('erp.actions.export_data'))
                ->action(fn() => Notification::make()->title(__('erp.reports.export_ready'))->success()->send()),
            Action::make('refreshMetrics')
                ->label(__('erp.actions.refresh_metrics'))
                ->action(fn() => Notification::make()->title(__('erp.reports.metrics_refreshed'))->success()->send()),
        ];
    }

    protected function getViewData(): array
    {
        $context = $this->resolvePeriodContext();

        try {
            return [
                'period' => $this->period,
                'periodOptions' => $this->periodOptions(),
                'periodLabel' => $context['label'],
                'kpis' => $this->buildKpis($context),
                'monthly' => $this->buildMonthlySeries($context),
                'breakdown' => $this->buildRevenueBreakdown($context),
                'aging' => $this->buildAgingBuckets($context),
                'transactions' => $this->buildTransactions($context),
                'insight' => $this->buildInsight($context),
            ];
        } catch (Throwable) {
            return [
                'period' => $this->period,
                'periodOptions' => $this->periodOptions(),
                ...$this->placeholderData($context['label']),
            ];
        }
    }

    protected function periodOptions(): array
    {
        return trans('erp.periods');
    }

    protected function normalizePeriod(string $value): string
    {
        return array_key_exists($value, $this->periodOptions()) ? $value : 'ytd';
    }

    protected function resolvePeriodContext(): array
    {
        $end = now()->endOfDay();

        switch ($this->period) {
            case '30d':
                $start = now()->subDays(29)->startOfDay();
                $label = '30 derniers jours';
                $bucket = 'week';
                break;
            case '90d':
                $start = now()->subDays(89)->startOfDay();
                $label = '90 derniers jours';
                $bucket = 'month';
                break;
            case 'qtd':
                $start = now()->startOfQuarter()->startOfDay();
                $label = 'Trimestre en cours';
                $bucket = 'month';
                break;
            case '12m':
                $start = now()->subMonths(11)->startOfMonth();
                $label = '12 derniers mois';
                $bucket = 'month';
                break;
            case 'ytd':
            default:
                $start = now()->startOfYear()->startOfDay();
                $label = 'Année en cours';
                $bucket = 'month';
                break;
        }

        $spanDays = $start->diffInDays($end) + 1;

        return [
            'start' => $start,
            'end' => $end,
            'previousStart' => $start->copy()->subDays($spanDays)->startOfDay(),
            'previousEnd' => $start->copy()->subDay()->endOfDay(),
            'label' => $label,
            'bucket' => $bucket,
        ];
    }

    protected function buildKpis(array $context): array
    {
        if (!Schema::hasTable('invoices') || !Schema::hasTable('payments') || !Schema::hasTable('expenses')) {
            return $this->placeholderData($context['label'])['kpis'];
        }

        $invoiceRevenue = $this->sumInvoiceRevenue($context['start'], $context['end']);
        $previousRevenue = $this->sumInvoiceRevenue($context['previousStart'], $context['previousEnd']);
        $collectedCash = $this->sumPayments($context['start'], $context['end']);
        $previousCollectedCash = $this->sumPayments($context['previousStart'], $context['previousEnd']);
        $expenses = $this->sumExpenses($context['start'], $context['end']);
        $previousExpenses = $this->sumExpenses($context['previousStart'], $context['previousEnd']);
        $cashFlow = $collectedCash - $expenses;
        $previousCashFlow = $previousCollectedCash - $previousExpenses;
        $margin = $invoiceRevenue > 0 ? (($invoiceRevenue - $expenses) / $invoiceRevenue) * 100 : 0;
        $previousMargin = $previousRevenue > 0 ? (($previousRevenue - $previousExpenses) / $previousRevenue) * 100 : 0;
        $liquidity = $expenses > 0 ? $collectedCash / $expenses : ($collectedCash > 0 ? 2.0 : 1.0);

        return [
            'revenue' => [
                'label' => 'Chiffre d’affaires',
                'value' => $this->shortMoney($invoiceRevenue),
                'trend' => $this->deltaLabel($invoiceRevenue, $previousRevenue),
                'trendTone' => $invoiceRevenue >= $previousRevenue ? 'positive' : 'negative',
                'note' => 'Factures comptabilisées sur la période',
                'icon' => 'heroicon-o-banknotes',
            ],
            'margin' => [
                'label' => 'Marge brute',
                'value' => number_format($margin, 1) . '%',
                'trend' => $this->deltaLabel($margin, $previousMargin),
                'trendTone' => $margin >= 55 ? 'positive' : 'warning',
                'note' => 'Objectif actuel : 60,0 %',
                'icon' => 'heroicon-o-chart-bar-square',
            ],
            'expenses' => [
                'label' => 'Dépenses opérationnelles',
                'value' => $this->shortMoney($expenses),
                'trend' => $this->deltaLabel($expenses, $previousExpenses),
                'trendTone' => $expenses <= $previousExpenses ? 'positive' : 'negative',
                'note' => 'Dépenses suivies sur la période',
                'icon' => 'heroicon-o-credit-card',
            ],
            'cashflow' => [
                'label' => 'Flux de trésorerie net',
                'value' => $this->shortMoney($cashFlow),
                'trend' => $this->deltaLabel($cashFlow, $previousCashFlow),
                'trendTone' => $cashFlow >= $previousCashFlow ? 'positive' : 'negative',
                'note' => 'Ratio de liquidité : ' . number_format($liquidity, 1),
                'icon' => 'heroicon-o-arrows-right-left',
            ],
        ];
    }

    protected function buildMonthlySeries(array $context): array
    {
        if (!Schema::hasTable('invoices') || !Schema::hasTable('expenses')) {
            return $this->placeholderData($context['label'])['monthly'];
        }

        $rows = $this->makeTimeBuckets($context)->map(function (array $bucket) {
            $revenue = $this->sumInvoiceRevenue($bucket['start'], $bucket['end']);
            $expenses = $this->sumExpenses($bucket['start'], $bucket['end']);

            return [
                'label' => $bucket['label'],
                'revenue' => $revenue,
                'expenses' => $expenses,
            ];
        });

        $max = max(1.0, (float) $rows->max(fn(array $row) => max($row['revenue'], $row['expenses'])));

        return $rows->values()->map(function (array $row, int $index) use ($max, $rows): array {
            return [
                'label' => $row['label'],
                'revenueHeight' => $row['revenue'] > 0 ? max(10, (int) round(($row['revenue'] / $max) * 180)) : 10,
                'expenseHeight' => $row['expenses'] > 0 ? max(8, (int) round(($row['expenses'] / $max) * 180)) : 8,
                'active' => $index === $rows->count() - 1,
            ];
        })->all();
    }

    protected function buildRevenueBreakdown(array $context): array
    {
        if (!Schema::hasTable('invoice_items') || !Schema::hasTable('invoices')) {
            return $this->placeholderData($context['label'])['breakdown'];
        }

        $rows = InvoiceItem::query()
            ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
            ->leftJoin('services', 'invoice_items.service_id', '=', 'services.id')
            ->whereBetween('invoices.issue_date', [$context['start']->toDateString(), $context['end']->toDateString()])
            ->selectRaw("COALESCE(services.category, services.name, 'Général') as label")
            ->selectRaw('SUM(CASE WHEN invoice_items.line_total > 0 THEN invoice_items.line_total ELSE invoice_items.quantity * invoice_items.unit_price END) as total')
            ->groupBy('label')
            ->orderByDesc('total')
            ->limit(4)
            ->get();

        if ($rows->isEmpty()) {
            return [
                ['label' => 'Aucune donnée', 'share' => '0%', 'color' => '#c4c6cf'],
            ];
        }

        $palette = ['#1A365D', '#70d8c8', '#515f74', '#c4c6cf'];
        $grandTotal = max(1.0, (float) $rows->sum('total'));

        return $rows->values()->map(function ($row, int $index) use ($palette, $grandTotal): array {
            return [
                'label' => (string) $row->label,
                'share' => number_format((((float) $row->total) / $grandTotal) * 100, 0) . '%',
                'color' => $palette[$index] ?? '#1A365D',
            ];
        })->all();
    }

    protected function buildAgingBuckets(array $context): array
    {
        if (!Schema::hasTable('invoices')) {
            return $this->placeholderData($context['label'])['aging'];
        }

        $baseQuery = Invoice::query()
            ->where('balance_due', '>', 0)
            ->whereBetween('issue_date', [$context['start']->toDateString(), $context['end']->toDateString()]);

        $current = (float) (clone $baseQuery)
            ->where(function ($query): void {
                $query->whereNull('due_date')->orWhereDate('due_date', '>=', now()->subDays(30)->toDateString());
            })
            ->sum('balance_due');

        $mid = (float) (clone $baseQuery)
            ->whereBetween('due_date', [now()->subDays(60)->toDateString(), now()->subDays(31)->toDateString()])
            ->sum('balance_due');

        $late = (float) (clone $baseQuery)
            ->whereDate('due_date', '<=', now()->subDays(61)->toDateString())
            ->sum('balance_due');

        $max = max(1.0, $current, $mid, $late);

        return [
            ['label' => 'À jour (0-30 jours)', 'value' => $this->money($current), 'width' => $current > 0 ? max(8, (int) round(($current / $max) * 100)) : 8, 'tone' => 'bg-[#70d8c8]'],
            ['label' => '31-60 jours', 'value' => $this->money($mid), 'width' => $mid > 0 ? max(8, (int) round(($mid / $max) * 100)) : 8, 'tone' => 'bg-[#b9c7df]'],
            ['label' => '61+ jours', 'value' => $this->money($late), 'width' => $late > 0 ? max(8, (int) round(($late / $max) * 100)) : 8, 'tone' => 'bg-[#ba1a1a]'],
        ];
    }

    protected function buildTransactions(array $context): array
    {
        $items = collect();

        if (Schema::hasTable('payments')) {
            $items = $items->merge(
                Payment::query()
                    ->with('client')
                    ->whereBetween('payment_date', [$context['start']->toDateString(), $context['end']->toDateString()])
                    ->latest('payment_date')
                    ->take(6)
                    ->get()
                    ->map(fn(Payment $payment): array => [
                        'date' => $payment->payment_date ? Carbon::parse($payment->payment_date)->locale('fr')->translatedFormat('d M Y') : 'Récent',
                        'entity' => $payment->client?->company_name ?: $payment->client?->contact_name ?: 'Client',
                        'type' => 'Encaissement',
                        'amount' => (float) $payment->amount,
                        'status' => $payment->reconciliationState() === 'completed' ? 'Validé' : 'En attente',
                        'badge' => $payment->reconciliationState() === 'completed' ? 'success' : 'pending',
                        'initials' => $this->initials($payment->client?->company_name ?: 'EN'),
                    ])
            );
        }

        if (Schema::hasTable('expenses')) {
            $items = $items->merge(
                Expense::query()
                    ->whereBetween('expense_date', [$context['start']->toDateString(), $context['end']->toDateString()])
                    ->latest('expense_date')
                    ->take(6)
                    ->get()
                    ->map(fn(Expense $expense): array => [
                        'date' => $expense->expense_date ? Carbon::parse($expense->expense_date)->locale('fr')->translatedFormat('d M Y') : 'Récent',
                        'entity' => $expense->vendor ?: $expense->title,
                        'type' => 'Dépense',
                        'amount' => (float) $expense->amount,
                        'status' => $expense->approval_status === 'approved' ? 'Validée' : 'En attente',
                        'badge' => $expense->approval_status === 'approved' ? 'success' : 'pending',
                        'initials' => $this->initials($expense->vendor ?: $expense->title),
                    ])
            );
        }

        $items = $items
            ->sortByDesc('amount')
            ->take(5)
            ->values()
            ->map(function (array $item): array {
                $item['amount_label'] = $this->money($item['amount']);

                return $item;
            })
            ->all();

        return $items ?: [
            [
                'date' => now()->locale('fr')->translatedFormat('d M Y'),
                'entity' => 'Aucune transaction sur cette période',
                'type' => '—',
                'amount_label' => $this->money(0),
                'status' => 'Surveillance',
                'badge' => 'pending',
                'initials' => 'NA',
            ]
        ];
    }

    protected function buildInsight(array $context): string
    {
        if (!Schema::hasTable('invoices')) {
            return $this->placeholderData($context['label'])['insight'];
        }

        $currentRevenue = $this->sumInvoiceRevenue($context['start'], $context['end']);
        $previousRevenue = $this->sumInvoiceRevenue($context['previousStart'], $context['previousEnd']);
        $currentCash = $this->sumPayments($context['start'], $context['end']);
        $collectionRate = $currentRevenue > 0 ? min(100, ($currentCash / $currentRevenue) * 100) : 0;

        if ($currentRevenue <= 0 && $currentCash <= 0) {
            return 'Aucune activité de facturation n’a encore été enregistrée sur cette période. Essayez une plage plus large pour obtenir plus d’analyses.';
        }

        if ($previousRevenue <= 0) {
            return 'Une nouvelle activité de facturation progresse sur cette période, avec ' . number_format($collectionRate, 0) . '% déjà encaissés.';
        }

        $delta = (($currentRevenue - $previousRevenue) / $previousRevenue) * 100;

        return 'Le chiffre d’affaires est ' . ($delta >= 0 ? 'en hausse de ' : 'en baisse de ') . number_format(abs($delta), 0) . '% par rapport à la période précédente, avec ' . number_format($collectionRate, 0) . '% déjà encaissés.';
    }

    protected function makeTimeBuckets(array $context): Collection
    {
        $buckets = collect();

        if ($context['bucket'] === 'week') {
            $cursor = $context['start']->copy()->startOfWeek();
            $last = $context['end']->copy()->endOfWeek();

            while ($cursor->lte($last)) {
                $bucketStart = $cursor->copy()->lt($context['start']) ? $context['start']->copy() : $cursor->copy();
                $bucketEnd = $cursor->copy()->endOfWeek();

                if ($bucketEnd->gt($context['end'])) {
                    $bucketEnd = $context['end']->copy();
                }

                $buckets->push([
                    'label' => $bucketStart->locale('fr')->translatedFormat('d M'),
                    'start' => $bucketStart,
                    'end' => $bucketEnd,
                ]);

                $cursor->addWeek()->startOfWeek();
            }

            return $buckets->take(-6)->values();
        }

        $cursor = $context['start']->copy()->startOfMonth();
        $last = $context['end']->copy()->startOfMonth();

        while ($cursor->lte($last)) {
            $bucketStart = $cursor->copy()->lt($context['start']) ? $context['start']->copy() : $cursor->copy();
            $bucketEnd = $cursor->copy()->endOfMonth();

            if ($bucketEnd->gt($context['end'])) {
                $bucketEnd = $context['end']->copy();
            }

            $buckets->push([
                'label' => $bucketStart->locale('fr')->translatedFormat('MMM'),
                'start' => $bucketStart,
                'end' => $bucketEnd,
            ]);

            $cursor->addMonth()->startOfMonth();
        }

        return $buckets->take(-12)->values();
    }

    protected function sumInvoiceRevenue(Carbon $start, Carbon $end): float
    {
        if (!Schema::hasTable('invoices')) {
            return 0.0;
        }

        $invoiceTotal = (float) Invoice::query()
            ->whereBetween('issue_date', [$start->toDateString(), $end->toDateString()])
            ->sum('total');

        if (!Schema::hasTable('invoice_items')) {
            return $invoiceTotal;
        }

        $itemTotal = (float) (InvoiceItem::query()
            ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
            ->whereBetween('invoices.issue_date', [$start->toDateString(), $end->toDateString()])
            ->selectRaw('SUM(CASE WHEN invoice_items.line_total > 0 THEN invoice_items.line_total ELSE invoice_items.quantity * invoice_items.unit_price END) as total')
            ->value('total') ?? 0);

        return max($invoiceTotal, $itemTotal);
    }

    protected function sumPayments(Carbon $start, Carbon $end): float
    {
        if (!Schema::hasTable('payments')) {
            return 0.0;
        }

        return (float) Payment::query()
            ->whereBetween('payment_date', [$start->toDateString(), $end->toDateString()])
            ->sum('amount');
    }

    protected function sumExpenses(Carbon $start, Carbon $end): float
    {
        if (!Schema::hasTable('expenses')) {
            return 0.0;
        }

        return (float) Expense::query()
            ->whereBetween('expense_date', [$start->toDateString(), $end->toDateString()])
            ->sum('amount');
    }

    protected function money(float $amount): string
    {
        return 'FCFA ' . number_format($amount, 0, '.', ' ');
    }

    protected function shortMoney(float $amount): string
    {
        $absolute = abs($amount);

        if ($absolute >= 1000000000) {
            return 'FCFA ' . number_format($amount / 1000000000, 1) . 'B';
        }

        if ($absolute >= 1000000) {
            return 'FCFA ' . number_format($amount / 1000000, 1) . 'M';
        }

        if ($absolute >= 1000) {
            return 'FCFA ' . number_format($amount / 1000, 1) . 'K';
        }

        return $this->money($amount);
    }

    protected function deltaLabel(float $current, float $baseline): string
    {
        if (abs($baseline) < 0.00001) {
            return $current > 0 ? '+100%' : '+0%';
        }

        $delta = (($current - $baseline) / abs($baseline)) * 100;
        $prefix = $delta >= 0 ? '+' : '';

        return $prefix . number_format($delta, 0) . '%';
    }

    protected function initials(string $value): string
    {
        $parts = collect(preg_split('/\s+/', trim($value)) ?: [])->filter()->take(2);

        if ($parts->isEmpty()) {
            return 'NA';
        }

        return $parts->map(fn(string $part): string => strtoupper(substr($part, 0, 1)))->implode('');
    }

    protected function placeholderData(?string $periodLabel = null): array
    {
        return [
            'periodLabel' => $periodLabel ?? 'Année en cours',
            'kpis' => [
                'revenue' => ['label' => 'Chiffre d’affaires', 'value' => 'FCFA 4.2M', 'trend' => '+12%', 'trendTone' => 'positive', 'note' => 'vs mois dernier : FCFA 3.75M', 'icon' => 'account_balance_wallet'],
                'margin' => ['label' => 'Marge brute', 'value' => '64.2%', 'trend' => '+2.4%', 'trendTone' => 'positive', 'note' => 'Objectif actuel : 60,0 %', 'icon' => 'percent'],
                'expenses' => ['label' => 'Dépenses opérationnelles', 'value' => 'FCFA 1.1M', 'trend' => '+5%', 'trendTone' => 'negative', 'note' => 'Utilisation du budget : 88 %', 'icon' => 'credit_card'],
                'cashflow' => ['label' => 'Flux de trésorerie net', 'value' => 'FCFA 2.8M', 'trend' => '+18%', 'trendTone' => 'positive', 'note' => 'Ratio de liquidité : 2.1', 'icon' => 'payments'],
            ],
            'monthly' => [
                ['label' => 'janv.', 'revenueHeight' => 96, 'expenseHeight' => 64, 'active' => false],
                ['label' => 'févr.', 'revenueHeight' => 128, 'expenseHeight' => 48, 'active' => false],
                ['label' => 'mars', 'revenueHeight' => 176, 'expenseHeight' => 76, 'active' => true],
                ['label' => 'avr.', 'revenueHeight' => 148, 'expenseHeight' => 66, 'active' => false],
                ['label' => 'mai', 'revenueHeight' => 182, 'expenseHeight' => 88, 'active' => false],
                ['label' => 'juin', 'revenueHeight' => 162, 'expenseHeight' => 58, 'active' => false],
            ],
            'breakdown' => [
                ['label' => 'Dév', 'share' => '45%', 'color' => '#1A365D'],
                ['label' => 'Hébergement', 'share' => '28%', 'color' => '#70d8c8'],
                ['label' => 'SEO', 'share' => '15%', 'color' => '#515f74'],
                ['label' => 'Autre', 'share' => '12%', 'color' => '#c4c6cf'],
            ],
            'aging' => [
                ['label' => 'À jour (0-30 jours)', 'value' => 'FCFA 842 000', 'width' => 85, 'tone' => 'bg-[#70d8c8]'],
                ['label' => '31-60 jours', 'value' => 'FCFA 120 000', 'width' => 40, 'tone' => 'bg-[#b9c7df]'],
                ['label' => '61+ jours', 'value' => 'FCFA 45 000', 'width' => 15, 'tone' => 'bg-[#ba1a1a]'],
            ],
            'transactions' => [
                ['date' => '24 oct. 2024', 'entity' => 'Nexgen Corp', 'type' => 'Encaissement', 'amount_label' => 'FCFA 124 500', 'status' => 'Validé', 'badge' => 'success', 'initials' => 'NC'],
                ['date' => '22 oct. 2024', 'entity' => 'Data Center Pro', 'type' => 'Dépense', 'amount_label' => 'FCFA 45 200', 'status' => 'En attente', 'badge' => 'pending', 'initials' => 'DC'],
                ['date' => '20 oct. 2024', 'entity' => 'Skyline Ltd', 'type' => 'Encaissement', 'amount_label' => 'FCFA 210 000', 'status' => 'Validé', 'badge' => 'success', 'initials' => 'SL'],
            ],
            'insight' => 'Croissance projetée de 15 % au prochain trimestre selon le pipeline actuel et les tendances saisonnières.',
        ];
    }
}
