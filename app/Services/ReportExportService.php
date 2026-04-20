<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Payment;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class ReportExportService
{
    private const SCHEDULE_CACHE_KEY = 'erp.report.schedules';

    public function generate(Carbon $start, Carbon $end, array $selectedModules, string $format = 'pdf', bool $includeCharts = true, ?int $userId = null): array
    {
        $report = $this->buildReport($start, $end, $selectedModules, $format, $includeCharts);
        $path = $this->storeExport($report, $format, $userId);
        $generatedAt = now()->format('d/m/Y H:i');

        app(\App\Services\AuditTrailService::class)->log('report_generated', null, [
            'path' => $path,
            'format' => strtoupper($format),
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'selected_modules' => $selectedModules,
        ], $userId);

        return [
            'summary' => $report['summary'],
            'previewRows' => $report['previewRows'],
            'path' => $path,
            'name' => basename($path),
            'generatedAt' => $generatedAt,
            'downloadUrl' => $this->temporaryDownloadUrl($path),
        ];
    }

    public function buildScheduledPlan(array $validated, ?int $userId = null): array
    {
        $nextRun = Carbon::parse((string) $validated['nextExecutionAt']);

        return [
            'id' => (string) Str::uuid(),
            'ownerId' => $userId,
            'description' => 'Export ' . strtoupper((string) $validated['exportFormat']) . ' financier',
            'frequency' => (string) $validated['scheduleFrequency'],
            'nextExecution' => $nextRun->format('d M Y - H:i'),
            'nextExecutionAt' => $nextRun->toDateTimeString(),
            'status' => __('erp.reports.scheduled_statuses.active'),
            'statusClasses' => 'bg-green-100 text-green-800',
            'email' => (string) $validated['scheduleEmail'],
            'exportFormat' => (string) $validated['exportFormat'],
            'startDate' => (string) $validated['startDate'],
            'endDate' => (string) $validated['endDate'],
            'selectedModules' => array_map(static fn(mixed $value): bool => (bool) $value, (array) $validated['selectedModules']),
            'includeCharts' => (bool) ($validated['includeCharts'] ?? true),
            'lastGenerated' => __('erp.reports.scheduled_statuses.never'),
            'lastPath' => null,
        ];
    }

    public function persistScheduledPlan(array $plan): array
    {
        $plans = Cache::get(self::SCHEDULE_CACHE_KEY, []);
        array_unshift($plans, $plan);
        $plans = array_slice($plans, 0, 24);

        Cache::forever(self::SCHEDULE_CACHE_KEY, $plans);

        return $plans;
    }

    public function loadScheduledPlans(?int $userId = null, array $defaults = []): array
    {
        $plans = collect(Cache::get(self::SCHEDULE_CACHE_KEY, []))
            ->filter(fn(array $plan): bool => ($plan['ownerId'] ?? null) === $userId)
            ->values()
            ->all();

        return count($plans) > 0 ? $plans : $defaults;
    }

    public function runDueScheduledExports(): int
    {
        $plans = Cache::get(self::SCHEDULE_CACHE_KEY, []);
        $processed = 0;

        foreach ($plans as $index => $plan) {
            $nextExecutionAt = isset($plan['nextExecutionAt']) ? Carbon::parse((string) $plan['nextExecutionAt']) : null;

            if ($nextExecutionAt === null || $nextExecutionAt->isFuture()) {
                continue;
            }

            $result = $this->generate(
                Carbon::parse((string) $plan['startDate'])->startOfDay(),
                Carbon::parse((string) $plan['endDate'])->endOfDay(),
                (array) ($plan['selectedModules'] ?? []),
                (string) ($plan['exportFormat'] ?? 'pdf'),
                (bool) ($plan['includeCharts'] ?? true),
                isset($plan['ownerId']) ? (int) $plan['ownerId'] : null,
            );

            $nextRun = $this->nextExecutionAt($nextExecutionAt, (string) ($plan['frequency'] ?? 'Hebdomadaire'));

            $plan['status'] = __('erp.reports.scheduled_statuses.recent');
            $plan['statusClasses'] = 'bg-sky-100 text-sky-800';
            $plan['nextExecutionAt'] = $nextRun->toDateTimeString();
            $plan['nextExecution'] = $nextRun->format('d M Y - H:i');
            $plan['lastGenerated'] = (string) $result['generatedAt'];
            $plan['lastPath'] = $result['path'];

            $plans[$index] = $plan;
            $processed++;
        }

        Cache::forever(self::SCHEDULE_CACHE_KEY, $plans);

        return $processed;
    }

    public function cleanupExpiredExports(?int $retentionDays = null): int
    {
        $retentionDays = max(1, $retentionDays ?? (int) config('erp.enterprise.report_retention_days', 30));
        $threshold = now()->subDays($retentionDays)->timestamp;
        $deleted = 0;

        foreach (Storage::disk('local')->allFiles('reports') as $file) {
            if (Storage::disk('local')->lastModified($file) > $threshold) {
                continue;
            }

            Storage::disk('local')->delete($file);
            $deleted++;
        }

        app(\App\Services\AuditTrailService::class)->log('report_exports_cleaned', null, [
            'deleted_count' => $deleted,
            'retention_days' => $retentionDays,
        ]);

        return $deleted;
    }

    public function temporaryDownloadUrl(string $path): string
    {
        return URL::temporarySignedRoute('reports.download', now()->addMinutes(30), [
            'report' => encrypt($path),
        ]);
    }

    protected function buildReport(Carbon $start, Carbon $end, array $selectedModules, string $format, bool $includeCharts): array
    {
        $invoices = Invoice::query()
            ->whereBetween('issue_date', [$start->toDateString(), $end->toDateString()])
            ->latest('issue_date')
            ->get();

        $payments = Payment::query()
            ->whereBetween('payment_date', [$start->toDateString(), $end->toDateString()])
            ->latest('payment_date')
            ->get();

        $expenses = Expense::query()
            ->whereBetween('expense_date', [$start->toDateString(), $end->toDateString()])
            ->latest('expense_date')
            ->get();

        $revenue = !empty($selectedModules['revenue']) ? (float) $invoices->sum('total') : 0.0;
        $expenseTotal = !empty($selectedModules['expenses']) ? (float) $expenses->sum('amount') : 0.0;
        $paymentTotal = !empty($selectedModules['payments']) ? (float) $payments->sum('amount') : 0.0;
        $taxTotal = !empty($selectedModules['taxes']) ? (float) $invoices->sum('tax_total') : 0.0;
        $netResult = $paymentTotal - $expenseTotal;

        $summary = [
            ['label' => __('erp.reports.summary.revenue'), 'value' => $this->formatMoney($revenue), 'tone' => 'text-primary'],
            ['label' => __('erp.reports.summary.payments'), 'value' => $this->formatMoney($paymentTotal), 'tone' => 'text-emerald-600'],
            ['label' => __('erp.reports.summary.expenses'), 'value' => $this->formatMoney($expenseTotal), 'tone' => 'text-rose-600'],
            ['label' => __('erp.reports.summary.taxes'), 'value' => $this->formatMoney($taxTotal), 'tone' => 'text-amber-600'],
            ['label' => __('erp.reports.summary.net'), 'value' => $this->formatMoney($netResult), 'tone' => $netResult >= 0 ? 'text-emerald-600' : 'text-rose-600'],
            ['label' => __('erp.reports.summary.format'), 'value' => strtoupper($format), 'tone' => 'text-sky-600'],
        ];

        $rows = collect();

        if (!empty($selectedModules['revenue'])) {
            $rows = $rows->merge($invoices->map(fn(Invoice $invoice): array => [
                'sort_date' => optional($invoice->issue_date)?->format('Y-m-d') ?? '',
                'date' => optional($invoice->issue_date)?->format('d/m/Y') ?? __('erp.common.none'),
                'title' => $invoice->invoice_number,
                'subtitle' => __('erp.reports.rows.client_invoice'),
                'amount' => $this->formatMoney((float) $invoice->total),
                'badge' => __('erp.resources.invoice.statuses.' . $invoice->status),
            ]));
        }

        if (!empty($selectedModules['payments'])) {
            $rows = $rows->merge($payments->map(fn(Payment $payment): array => [
                'sort_date' => optional($payment->payment_date)?->format('Y-m-d') ?? '',
                'date' => optional($payment->payment_date)?->format('d/m/Y') ?? __('erp.common.none'),
                'title' => $payment->reference ?: __('erp.reports.rows.unreferenced_payment'),
                'subtitle' => $payment->invoice?->invoice_number ?: __('erp.reports.rows.free_payment'),
                'amount' => $this->formatMoney((float) $payment->amount),
                'badge' => __('erp.common.transaction'),
            ]));
        }

        if (!empty($selectedModules['expenses'])) {
            $rows = $rows->merge($expenses->map(fn(Expense $expense): array => [
                'sort_date' => optional($expense->expense_date)?->format('Y-m-d') ?? '',
                'date' => optional($expense->expense_date)?->format('d/m/Y') ?? __('erp.common.none'),
                'title' => $expense->title,
                'subtitle' => __('erp.reports.rows.expense_prefix', ['category' => __('erp.resources.expense.categories.' . $expense->category)]),
                'amount' => $this->formatMoney((float) $expense->amount),
                'badge' => __('erp.common.expense'),
            ]));
        }

        $rows = $rows
            ->sortByDesc('sort_date')
            ->values()
            ->map(fn(array $row): array => Arr::except($row, ['sort_date']));

        return [
            'summary' => $summary,
            'previewRows' => $rows->take(8)->all(),
            'rows' => $rows->all(),
            'meta' => [
                'startDate' => $start->format('d/m/Y'),
                'endDate' => $end->format('d/m/Y'),
                'generatedAt' => now()->format('d/m/Y H:i'),
                'format' => strtoupper($format),
                'includeCharts' => $includeCharts,
            ],
        ];
    }

    protected function storeExport(array $report, string $format, ?int $userId = null): string
    {
        $extension = strtolower($format) === 'csv' ? 'csv' : 'pdf';
        $folder = 'reports/' . now()->format('Y/m');
        $fileName = 'rapport-financier-' . now()->format('Ymd-His') . '-' . ($userId ?? 'system') . '.' . $extension;
        $path = $folder . '/' . $fileName;

        $content = $extension === 'csv'
            ? $this->buildCsvContent($report)
            : Pdf::loadView('reports.export-pdf', [
                'summary' => $report['summary'],
                'rows' => $report['rows'],
                'meta' => $report['meta'],
            ])->setPaper('a4')->output();

        Storage::disk('local')->put($path, $content);

        return $path;
    }

    protected function buildCsvContent(array $report): string
    {
        $handle = fopen('php://temp', 'r+');

        fputcsv($handle, ['Rapport financier', $report['meta']['generatedAt']], ';');
        fputcsv($handle, ['Période', $report['meta']['startDate'] . ' -> ' . $report['meta']['endDate']], ';');
        fputcsv($handle, []);
        fputcsv($handle, ['Indicateur', 'Valeur'], ';');

        foreach ($report['summary'] as $item) {
            fputcsv($handle, [$item['label'], $item['value']], ';');
        }

        fputcsv($handle, []);
        fputcsv($handle, ['Date', 'Titre', 'Description', 'Montant', 'Statut'], ';');

        foreach ($report['rows'] as $row) {
            fputcsv($handle, [$row['date'], $row['title'], $row['subtitle'], $row['amount'], $row['badge']], ';');
        }

        rewind($handle);
        $content = stream_get_contents($handle) ?: '';
        fclose($handle);

        return $content;
    }

    protected function nextExecutionAt(Carbon $current, string $frequency): Carbon
    {
        return match ($frequency) {
            'Quotidienne' => $current->copy()->addDay(),
            'Mensuelle' => $current->copy()->addMonth(),
            default => $current->copy()->addWeek(),
        };
    }

    protected function formatMoney(float $amount): string
    {
        return number_format($amount, 2, ',', ' ') . ' FCFA';
    }
}
