<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\HasPermissionAccess;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Services\AuditTrailService;
use App\Services\ReportExportService;
use BackedEnum;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
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
                ->action(function (): void {
                    $context = $this->resolvePeriodContext();
                    $userId = auth()->id();

                    $report = app(ReportExportService::class)->generate(
                        $context['start'],
                        $context['end'],
                        [
                            'revenue' => true,
                            'expenses' => true,
                            'payments' => true,
                            'taxes' => true,
                            'audit' => false,
                        ],
                        'pdf',
                        false,
                        $userId,
                    );

                    app(AuditTrailService::class)->log('financial_insights_exported', null, [
                        'period' => $this->period,
                        'path' => $report['path'],
                        'generated_at' => $report['generatedAt'],
                    ]);

                    $this->redirect($report['downloadUrl'], navigate: false);
                }),

            Action::make('refreshMetrics')
                ->label(__('erp.actions.refresh_metrics'))
                ->action(function (): void {
                    // Re-render forces getViewData() to re-query all KPIs from the database.
                    // We also log the manual refresh for audit visibility.
                    app(AuditTrailService::class)->log('financial_insights_refreshed', null, [
                        'period' => $this->period,
                        'refreshed_by' => auth()->id(),
                    ]);

                    $this->dispatch('$refresh');

                    Notification::make()
                        ->title(__('erp.reports.metrics_refreshed'))
                        ->success()
                        ->send();
                }),
        ];
    }

    protected function getViewData(): array
    {
        $context = $this->resolvePeriodContext();

        $cacheKey = sprintf(
            'financial_insights:%s:%s:%s:%s',
            currentCompany()?->id ?? 'global',
            $this->period,
            $context['start']->toDateString(),
            $context['end']->toDateString(),
        );

        try {
            return Cache::remember($cacheKey, now()->addMinutes(2), function () use ($context): array {
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
            });
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

        $bucketRanges = $this->makeTimeBuckets($context);
        $invoiceTotals = $this->sumInvoiceRevenueByBuckets($bucketRanges);
        $expenseTotals = $this->sumExpensesByBuckets($bucketRanges);

        $rows = $bucketRanges->map(function (array $bucket) use ($invoiceTotals, $expenseTotals) {
            $bucketKey = $bucket['start']->toDateString() . '|' . $bucket['end']->toDateString();
            $revenue = (float) ($invoiceTotals[$bucketKey] ?? 0.0);
            $expenses = (float) ($expenseTotals[$bucketKey] ?? 0.0);

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

    protected function sumInvoiceRevenueByBuckets(Collection $buckets): array
    {
        $totals = [];

        foreach ($buckets as $bucket) {
            $key = $bucket['start']->toDateString() . '|' . $bucket['end']->toDateString();
            $totals[$key] = $this->sumInvoiceRevenue($bucket['start'], $bucket['end']);
        }

        return $totals;
    }

    protected function sumExpensesByBuckets(Collection $buckets): array
    {
        $totals = [];

        foreach ($buckets as $bucket) {
            $key = $bucket['start']->toDateString() . '|' . $bucket['end']->toDateString();
            $totals[$key] = $this->sumExpenses($bucket['start'], $bucket['end']);
        }

        return $totals;
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
                'revenue' => ['label' => 'Chiffre d’affaires', 'value' => 'FCFA 0', 'trend' => '+0%', 'trendTone' => 'positive', 'note' => 'Aucune facture sur la période', 'icon' => 'account_balance_wallet'],
                'margin' => ['label' => 'Marge brute', 'value' => '0.0%', 'trend' => '+0%', 'trendTone' => 'positive', 'note' => 'Aucune marge calculable sans activité', 'icon' => 'percent'],
                'expenses' => ['label' => 'Dépenses opérationnelles', 'value' => 'FCFA 0', 'trend' => '+0%', 'trendTone' => 'positive', 'note' => 'Aucune dépense enregistrée', 'icon' => 'credit_card'],
                'cashflow' => ['label' => 'Flux de trésorerie net', 'value' => 'FCFA 0', 'trend' => '+0%', 'trendTone' => 'positive', 'note' => 'Ratio de liquidité : 0.0', 'icon' => 'payments'],
            ],
            'monthly' => [
                ['label' => 'janv.', 'revenueHeight' => 10, 'expenseHeight' => 8, 'active' => false],
                ['label' => 'févr.', 'revenueHeight' => 10, 'expenseHeight' => 8, 'active' => false],
                ['label' => 'mars', 'revenueHeight' => 10, 'expenseHeight' => 8, 'active' => true],
            ],
            'breakdown' => [
                ['label' => 'Aucune donnée', 'share' => '0%', 'color' => '#c4c6cf'],
            ],
            'aging' => [
                ['label' => 'À jour (0-30 jours)', 'value' => 'FCFA 0', 'width' => 8, 'tone' => 'bg-[#70d8c8]'],
                ['label' => '31-60 jours', 'value' => 'FCFA 0', 'width' => 8, 'tone' => 'bg-[#b9c7df]'],
                ['label' => '61+ jours', 'value' => 'FCFA 0', 'width' => 8, 'tone' => 'bg-[#ba1a1a]'],
            ],
            'transactions' => [
                ['date' => now()->locale('fr')->translatedFormat('d M Y'), 'entity' => 'Aucune transaction sur cette période', 'type' => '—', 'amount_label' => 'FCFA 0', 'status' => 'Surveillance', 'badge' => 'pending', 'initials' => 'NA'],
            ],
            'insight' => 'Aucune donnée financière réelle n’est encore disponible pour la période sélectionnée.',
        ];
    }
}
