<?php

namespace App\Services;

use App\Jobs\GenerateScheduledReportJob;
use App\Models\ActivityLog;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\ReportSchedule;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

class ReportExportService
{
    public function generate(Carbon $start, Carbon $end, array $selectedModules, string $format = 'pdf', bool $includeCharts = true, ?int $userId = null): array
    {
        $report = $this->buildReport($start, $end, $selectedModules, $format, $includeCharts, $userId);
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
            'ownerId' => $userId,
            'description' => 'Export ' . strtoupper((string) $validated['exportFormat']) . ' financier',
            'frequency' => (string) $validated['scheduleFrequency'],
            'nextExecutionAt' => $nextRun->toDateTimeString(),
            'email' => (string) $validated['scheduleEmail'],
            'exportFormat' => (string) $validated['exportFormat'],
            'startDate' => (string) $validated['startDate'],
            'endDate' => (string) $validated['endDate'],
            'selectedModules' => array_map(static fn(mixed $value): bool => (bool) $value, (array) $validated['selectedModules']),
            'includeCharts' => (bool) ($validated['includeCharts'] ?? true),
        ];
    }

    public function persistScheduledPlan(array $plan): array
    {
        $schedule = ReportSchedule::create([
            'owner_id' => $plan['ownerId'] ?? null,
            'description' => $plan['description'],
            'frequency' => $plan['frequency'],
            'export_format' => $plan['exportFormat'],
            'start_date' => $plan['startDate'] ?: null,
            'end_date' => $plan['endDate'] ?: null,
            'selected_modules' => $plan['selectedModules'],
            'include_charts' => $plan['includeCharts'],
            'schedule_email' => $plan['email'] ?? null,
            'next_execution_at' => Carbon::parse($plan['nextExecutionAt']),
            'status' => 'active',
        ]);

        return $this->loadScheduledPlans($plan['ownerId'] ?? null);
    }

    public function loadScheduledPlans(?int $userId = null, array $defaults = []): array
    {
        $plans = ReportSchedule::query()
            ->forOwner($userId)
            ->orderByDesc('created_at')
            ->take(24)
            ->get()
            ->map(fn(ReportSchedule $s): array => [
                'id' => $s->id,
                'ownerId' => $s->owner_id,
                'description' => $s->description,
                'frequency' => $s->frequency,
                'nextExecution' => $s->nextExecutionLabel(),
                'nextExecutionAt' => $s->next_execution_at->toDateTimeString(),
                'status' => $s->statusLabel(),
                'statusClasses' => $s->statusClasses(),
                'email' => (string) $s->schedule_email,
                'exportFormat' => $s->export_format,
                'startDate' => $s->start_date?->toDateString() ?? '',
                'endDate' => $s->end_date?->toDateString() ?? '',
                'selectedModules' => $s->selected_modules ?? [],
                'includeCharts' => $s->include_charts,
                'lastGenerated' => $s->lastGeneratedLabel(),
                'lastPath' => $s->last_path,
            ])
            ->all();

        return count($plans) > 0 ? $plans : $defaults;
    }

    public function runDueScheduledExports(): int
    {
        $schedules = ReportSchedule::due()->get();
        $processed = 0;

        foreach ($schedules as $schedule) {
            GenerateScheduledReportJob::dispatch($schedule->id);
            $processed++;
        }

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

    protected function buildReport(Carbon $start, Carbon $end, array $selectedModules, string $format, bool $includeCharts, ?int $userId = null): array
    {
        $selectedModules = array_merge([
            'revenue' => false,
            'expenses' => false,
            'payments' => false,
            'taxes' => false,
            'audit' => false,
        ], $selectedModules);

        $invoices = Invoice::query()
            ->with('client')
            ->whereBetween('issue_date', [$start->toDateString(), $end->toDateString()])
            ->latest('issue_date')
            ->get();

        $payments = Payment::query()
            ->with(['client', 'invoice'])
            ->whereBetween('payment_date', [$start->toDateString(), $end->toDateString()])
            ->latest('payment_date')
            ->get();

        $expenses = Expense::query()
            ->whereBetween('expense_date', [$start->toDateString(), $end->toDateString()])
            ->latest('expense_date')
            ->get();

        $activityLogs = !empty($selectedModules['audit'])
            ? ActivityLog::query()
                ->whereBetween('created_at', [$start, $end])
                ->latest('created_at')
                ->get()
            : collect();

        $revenue = !empty($selectedModules['revenue']) ? (float) $invoices->sum('total') : 0.0;
        $expenseTotal = !empty($selectedModules['expenses']) ? (float) $expenses->sum('amount') : 0.0;
        $paymentTotal = !empty($selectedModules['payments']) ? (float) $payments->sum('amount') : 0.0;
        $taxTotal = !empty($selectedModules['taxes']) ? (float) $invoices->sum('tax_total') : 0.0;
        $netResult = $paymentTotal - $expenseTotal;
        $ledgerRows = $this->buildLedgerRows($invoices, $payments, $expenses, $selectedModules);
        $auditRows = !empty($selectedModules['audit']) ? $this->buildAuditRows($activityLogs) : [];

        $summary = [
            ['label' => __('erp.reports.summary.revenue'), 'value' => $this->formatMoney($revenue), 'tone' => 'text-primary'],
            ['label' => __('erp.reports.summary.payments'), 'value' => $this->formatMoney($paymentTotal), 'tone' => 'text-emerald-600'],
            ['label' => __('erp.reports.summary.expenses'), 'value' => $this->formatMoney($expenseTotal), 'tone' => 'text-rose-600'],
            ['label' => __('erp.reports.summary.taxes'), 'value' => $this->formatMoney($taxTotal), 'tone' => 'text-amber-600'],
            ['label' => __('erp.reports.summary.net'), 'value' => $this->formatMoney($netResult), 'tone' => $netResult >= 0 ? 'text-emerald-600' : 'text-rose-600'],
            ['label' => __('erp.reports.summary.entries'), 'value' => number_format(count($ledgerRows)), 'tone' => 'text-slate-600'],
            ['label' => __('erp.reports.summary.audit'), 'value' => number_format(count($auditRows)), 'tone' => 'text-violet-600'],
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

        if (!empty($selectedModules['audit'])) {
            $rows = $rows->merge($activityLogs->map(fn(ActivityLog $log): array => [
                'sort_date' => optional($log->created_at)?->format('Y-m-d H:i:s') ?? '',
                'date' => optional($log->created_at)?->format('d/m/Y H:i') ?? __('erp.common.none'),
                'title' => $log->action,
                'subtitle' => __('erp.reports.rows.audit_event'),
                'amount' => __('erp.common.none'),
                'badge' => class_basename((string) ($log->subject_type ?: ActivityLog::class)),
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
            'ledgerRows' => $ledgerRows,
            'auditRows' => $auditRows,
            'meta' => [
                'startDate' => $start->format('d/m/Y'),
                'endDate' => $end->format('d/m/Y'),
                'generatedAt' => now()->format('d/m/Y H:i'),
                'format' => strtoupper($format),
                'includeCharts' => $includeCharts,
                'generatedBy' => $userId ? 'Utilisateur #' . $userId : 'Système',
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

        fputcsv($handle, ['Journal comptable prêt pour audit', $report['meta']['generatedAt']], ';');
        fputcsv($handle, ['Période', $report['meta']['startDate'] . ' -> ' . $report['meta']['endDate']], ';');
        fputcsv($handle, ['Généré par', $report['meta']['generatedBy'] ?? 'Système'], ';');
        fputcsv($handle, []);
        fputcsv($handle, ['Indicateur', 'Valeur'], ';');

        foreach ($report['summary'] as $item) {
            fputcsv($handle, [$item['label'], $item['value']], ';');
        }

        fputcsv($handle, []);
        fputcsv($handle, ['Journal comptable'], ';');
        fwrite($handle, "Date;Type de pièce;Référence;Tiers;Description;Débit;Crédit;Taxes;Solde dû;Statut\n");

        foreach ($report['ledgerRows'] as $row) {
            fputcsv($handle, [
                $row['date'],
                $row['document_type'],
                $row['reference'],
                $row['counterparty'],
                $row['description'],
                $row['debit'],
                $row['credit'],
                $row['tax'],
                $row['balance_due'],
                $row['status'],
            ], ';');
        }

        if (!empty($report['auditRows'])) {
            fputcsv($handle, []);
            fputcsv($handle, ['Piste d\'audit'], ';');
            fwrite($handle, "Horodatage;Utilisateur;Action;Sujet;Identifiant;Contexte\n");

            foreach ($report['auditRows'] as $row) {
                fputcsv($handle, [
                    $row['timestamp'],
                    $row['user'],
                    $row['action'],
                    $row['subject'],
                    $row['subject_id'],
                    $row['context'],
                ], ';');
            }
        }

        rewind($handle);
        $content = stream_get_contents($handle) ?: '';
        fclose($handle);

        return $content;
    }

    protected function buildLedgerRows($invoices, $payments, $expenses, array $selectedModules): array
    {
        $rows = collect();

        if (!empty($selectedModules['revenue'])) {
            $rows = $rows->merge($invoices->map(fn(Invoice $invoice): array => [
                'date_key' => optional($invoice->issue_date)?->format('Y-m-d') ?? '',
                'date' => optional($invoice->issue_date)?->format('d/m/Y') ?? __('erp.common.none'),
                'document_type' => __('erp.common.invoice'),
                'reference' => $invoice->invoice_number,
                'counterparty' => $invoice->client?->company_name ?: $invoice->client?->contact_name ?: __('erp.common.account_client'),
                'description' => __('erp.reports.rows.client_invoice'),
                'debit' => '',
                'credit' => $this->formatMoney((float) $invoice->total),
                'tax' => $this->formatMoney((float) $invoice->tax_total),
                'balance_due' => $this->formatMoney((float) $invoice->balance_due),
                'status' => __('erp.resources.invoice.statuses.' . $invoice->status),
            ]));
        }

        if (!empty($selectedModules['payments'])) {
            $rows = $rows->merge($payments->map(fn(Payment $payment): array => [
                'date_key' => optional($payment->payment_date)?->format('Y-m-d') ?? '',
                'date' => optional($payment->payment_date)?->format('d/m/Y') ?? __('erp.common.none'),
                'document_type' => __('erp.common.transaction'),
                'reference' => $payment->reference ?: __('erp.reports.rows.unreferenced_payment'),
                'counterparty' => $payment->client?->company_name ?: $payment->client?->contact_name ?: __('erp.common.account_client'),
                'description' => $payment->invoice?->invoice_number ?: __('erp.reports.rows.free_payment'),
                'debit' => '',
                'credit' => $this->formatMoney((float) $payment->amount),
                'tax' => '',
                'balance_due' => $this->formatMoney((float) ($payment->invoice?->balance_due ?? 0)),
                'status' => __('erp.resources.payment.reconciliation.' . $payment->reconciliationState()),
            ]));
        }

        if (!empty($selectedModules['expenses'])) {
            $rows = $rows->merge($expenses->map(fn(Expense $expense): array => [
                'date_key' => optional($expense->expense_date)?->format('Y-m-d') ?? '',
                'date' => optional($expense->expense_date)?->format('d/m/Y') ?? __('erp.common.none'),
                'document_type' => __('erp.common.expense'),
                'reference' => $expense->reference ?: ('EXP-' . $expense->getKey()),
                'counterparty' => $expense->vendor ?: __('erp.common.not_provided'),
                'description' => $expense->title,
                'debit' => $this->formatMoney((float) $expense->amount),
                'credit' => '',
                'tax' => '',
                'balance_due' => '',
                'status' => __('erp.resources.expense.approval_statuses.' . ($expense->approval_status ?? 'pending')),
            ]));
        }

        return $rows
            ->sortBy('date_key')
            ->values()
            ->map(fn(array $row): array => Arr::except($row, ['date_key']))
            ->all();
    }

    protected function buildAuditRows($activityLogs): array
    {
        return $activityLogs
            ->map(fn(ActivityLog $log): array => [
                'timestamp' => optional($log->created_at)?->format('d/m/Y H:i:s') ?? __('erp.common.none'),
                'user' => (string) ($log->user_id ?? 'system'),
                'action' => $log->action,
                'subject' => class_basename((string) ($log->subject_type ?: ActivityLog::class)),
                'subject_id' => (string) ($log->subject_id ?? ''),
                'context' => json_encode($log->meta_json ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ])
            ->values()
            ->all();
    }

    protected function formatMoney(float $amount): string
    {
        return number_format($amount, 2, ',', ' ') . ' FCFA';
    }
}
