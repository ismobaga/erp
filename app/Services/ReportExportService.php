<?php

namespace App\Services;

use App\Jobs\GenerateScheduledReportJob;
use App\Models\ActivityLog;
use App\Models\Client;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\ReportSchedule;
use App\Models\WhatsappConversation;
use App\Models\WhatsappMessage;
use App\Models\WhatsappMessageLog;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

class ReportExportService
{
    private const QUERY_CHUNK_SIZE = 500;

    public function generate(Carbon $start, Carbon $end, array $selectedModules, string $format = 'pdf', bool $includeCharts = true, ?int $userId = null): array
    {
        $format = match (strtolower($format)) {
            'csv' => 'csv',
            'pdf' => 'pdf',
            'excel', 'xls', 'xlsx' => 'excel',
            default => throw new \InvalidArgumentException("Unsupported report format: {$format}. Allowed: csv, pdf, excel."),
        };

        $includeFullData = in_array($format, ['pdf', 'excel'], true);
        $report = $this->buildReport($start, $end, $selectedModules, $format, $includeCharts, $userId, $includeFullData);
        $path = match ($format) {
            'csv' => $this->storeCsvExportStreamed($start, $end, $selectedModules, $report, $userId),
            'excel' => $this->storeExcelExport($report, $userId),
            default => $this->storeExport($report, $format, $userId),
        };
        $generatedAt = now()->format('d/m/Y H:i');

        app(AuditTrailService::class)->log('report_generated', null, [
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
            'description' => 'Export '.strtoupper((string) $validated['exportFormat']).' financier',
            'frequency' => (string) $validated['scheduleFrequency'],
            'nextExecutionAt' => $nextRun->toDateTimeString(),
            'email' => (string) $validated['scheduleEmail'],
            'exportFormat' => (string) $validated['exportFormat'],
            'startDate' => (string) $validated['startDate'],
            'endDate' => (string) $validated['endDate'],
            'selectedModules' => array_map(static fn (mixed $value): bool => (bool) $value, (array) $validated['selectedModules']),
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
            ->map(fn (ReportSchedule $s): array => [
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
            $claimed = ReportSchedule::query()
                ->whereKey($schedule->getKey())
                ->where('status', 'active')
                ->where('next_execution_at', $schedule->next_execution_at)
                ->update(['status' => 'processing']);

            if ($claimed !== 1) {
                continue;
            }

            GenerateScheduledReportJob::dispatch($schedule->id, $schedule->company_id);
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

        app(AuditTrailService::class)->log('report_exports_cleaned', null, [
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

    protected function buildReport(
        Carbon $start,
        Carbon $end,
        array $selectedModules,
        string $format,
        bool $includeCharts,
        ?int $userId = null,
        bool $includeFullData = true,
    ): array {
        $selectedModules = array_merge([
            'revenue' => false,
            'expenses' => false,
            'payments' => false,
            'taxes' => false,
            'audit' => false,
            'whatsapp' => false,
            'engagement' => false,
        ], $selectedModules);

        $includeRevenue = ! empty($selectedModules['revenue']);
        $includeExpenses = ! empty($selectedModules['expenses']);
        $includePayments = ! empty($selectedModules['payments']);
        $includeTaxes = ! empty($selectedModules['taxes']);
        $includeAudit = ! empty($selectedModules['audit']);
        $includeWhatsapp = ! empty($selectedModules['whatsapp']);
        $includeEngagement = ! empty($selectedModules['engagement']);

        $revenue = $includeRevenue
            ? (float) Invoice::query()->whereBetween('issue_date', [$start->toDateString(), $end->toDateString()])->sum('total')
            : 0.0;
        $expenseTotal = $includeExpenses
            ? (float) Expense::query()->whereBetween('expense_date', [$start->toDateString(), $end->toDateString()])->sum('amount')
            : 0.0;
        $paymentTotal = $includePayments
            ? (float) Payment::query()->whereBetween('payment_date', [$start->toDateString(), $end->toDateString()])->sum('amount')
            : 0.0;
        $taxTotal = $includeTaxes
            ? (float) Invoice::query()->whereBetween('issue_date', [$start->toDateString(), $end->toDateString()])->sum('tax_total')
            : 0.0;
        $netResult = $paymentTotal - $expenseTotal;
        $cashFlow = $paymentTotal - $expenseTotal;
        $profitLoss = $revenue - $expenseTotal;
        $collectionRate = $revenue > 0 ? round(($paymentTotal / $revenue) * 100, 1) : 0.0;

        $entryCount =
            ($includeRevenue ? (int) Invoice::query()->whereBetween('issue_date', [$start->toDateString(), $end->toDateString()])->count() : 0)
            + ($includePayments ? (int) Payment::query()->whereBetween('payment_date', [$start->toDateString(), $end->toDateString()])->count() : 0)
            + ($includeExpenses ? (int) Expense::query()->whereBetween('expense_date', [$start->toDateString(), $end->toDateString()])->count() : 0);
        $auditCount = $includeAudit
            ? (int) ActivityLog::query()->whereBetween('created_at', [$start, $end])->count()
            : 0;

        $whatsappSummary = $this->buildWhatsappAnalytics($start, $end, $includeWhatsapp);
        $engagementSummary = $this->buildClientEngagementAnalytics($start, $end, $includeEngagement);

        if (! $includeFullData) {
            $summary = [
                ['label' => __('erp.reports.summary.revenue'), 'value' => $this->formatMoney($revenue), 'tone' => 'text-primary'],
                ['label' => __('erp.reports.summary.payments'), 'value' => $this->formatMoney($paymentTotal), 'tone' => 'text-emerald-600'],
                ['label' => __('erp.reports.summary.expenses'), 'value' => $this->formatMoney($expenseTotal), 'tone' => 'text-rose-600'],
                ['label' => __('erp.reports.summary.taxes'), 'value' => $this->formatMoney($taxTotal), 'tone' => 'text-amber-600'],
                ['label' => __('erp.reports.summary.net'), 'value' => $this->formatMoney($netResult), 'tone' => $netResult >= 0 ? 'text-emerald-600' : 'text-rose-600'],
                ['label' => __('erp.reports.summary.cash_flow'), 'value' => $this->formatMoney($cashFlow), 'tone' => $cashFlow >= 0 ? 'text-emerald-600' : 'text-rose-600'],
                ['label' => __('erp.reports.summary.profit_loss'), 'value' => $this->formatMoney($profitLoss), 'tone' => $profitLoss >= 0 ? 'text-emerald-600' : 'text-rose-600'],
                ['label' => __('erp.reports.summary.collection_rate'), 'value' => number_format($collectionRate, 1, ',', ' ').' %', 'tone' => 'text-cyan-700'],
                ['label' => __('erp.reports.summary.entries'), 'value' => number_format($entryCount), 'tone' => 'text-slate-600'],
                ['label' => __('erp.reports.summary.audit'), 'value' => number_format($auditCount), 'tone' => 'text-violet-600'],
                ['label' => __('erp.reports.summary.whatsapp_messages'), 'value' => number_format($whatsappSummary['messages']), 'tone' => 'text-indigo-700'],
                ['label' => __('erp.reports.summary.whatsapp_read_rate'), 'value' => number_format($whatsappSummary['readRate'], 1, ',', ' ').' %', 'tone' => 'text-indigo-700'],
                ['label' => __('erp.reports.summary.engaged_clients'), 'value' => number_format($engagementSummary['engagedClients']), 'tone' => 'text-fuchsia-700'],
                ['label' => __('erp.reports.summary.engagement_rate'), 'value' => number_format($engagementSummary['engagementRate'], 1, ',', ' ').' %', 'tone' => 'text-fuchsia-700'],
                ['label' => __('erp.reports.summary.format'), 'value' => strtoupper($format), 'tone' => 'text-sky-600'],
            ];

            return [
                'summary' => $summary,
                'previewRows' => $this->collectPreviewRows($start, $end, $selectedModules),
                'rows' => [],
                'ledgerRows' => [],
                'auditRows' => [],
                'whatsappRows' => [],
                'engagementRows' => [],
                'meta' => [
                    'startDate' => $start->format('d/m/Y'),
                    'endDate' => $end->format('d/m/Y'),
                    'generatedAt' => now()->format('d/m/Y H:i'),
                    'format' => strtoupper($format),
                    'includeCharts' => $includeCharts,
                    'generatedBy' => $userId ? 'Utilisateur #'.$userId : 'Système',
                ],
            ];
        }

        $rows = [];
        $ledgerRows = [];
        $auditRows = [];
        $whatsappRows = [];
        $engagementRows = [];

        if ($includeRevenue) {
            Invoice::query()
                ->with('client')
                ->select(['id', 'client_id', 'invoice_number', 'issue_date', 'status', 'total', 'tax_total', 'balance_due'])
                ->whereBetween('issue_date', [$start->toDateString(), $end->toDateString()])
                ->orderBy('id')
                ->chunk(self::QUERY_CHUNK_SIZE, function ($chunk) use (&$rows, &$ledgerRows): void {
                    foreach ($chunk as $invoice) {
                        /** @var Invoice $invoice */
                        $rows[] = [
                            'sort_date' => optional($invoice->issue_date)?->format('Y-m-d') ?? '',
                            'date' => optional($invoice->issue_date)?->format('d/m/Y') ?? __('erp.common.none'),
                            'title' => $invoice->invoice_number,
                            'subtitle' => __('erp.reports.rows.client_invoice'),
                            'amount' => $this->formatMoney((float) $invoice->total),
                            'badge' => __('erp.resources.invoice.statuses.'.$invoice->status),
                        ];

                        $ledgerRows[] = [
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
                            'status' => __('erp.resources.invoice.statuses.'.$invoice->status),
                        ];
                    }
                });
        }

        if ($includePayments) {
            Payment::query()
                ->with(['client', 'invoice'])
                ->select(['id', 'client_id', 'invoice_id', 'payment_date', 'reference', 'amount', 'reconciled_at'])
                ->whereBetween('payment_date', [$start->toDateString(), $end->toDateString()])
                ->orderBy('id')
                ->chunk(self::QUERY_CHUNK_SIZE, function ($chunk) use (&$rows, &$ledgerRows): void {
                    foreach ($chunk as $payment) {
                        /** @var Payment $payment */
                        $rows[] = [
                            'sort_date' => optional($payment->payment_date)?->format('Y-m-d') ?? '',
                            'date' => optional($payment->payment_date)?->format('d/m/Y') ?? __('erp.common.none'),
                            'title' => $payment->reference ?: __('erp.reports.rows.unreferenced_payment'),
                            'subtitle' => $payment->invoice?->invoice_number ?: __('erp.reports.rows.free_payment'),
                            'amount' => $this->formatMoney((float) $payment->amount),
                            'badge' => __('erp.common.transaction'),
                        ];

                        $ledgerRows[] = [
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
                            'status' => __('erp.resources.payment.reconciliation.'.$payment->reconciliationState()),
                        ];
                    }
                });
        }

        if ($includeExpenses) {
            Expense::query()
                ->select(['id', 'expense_date', 'reference', 'vendor', 'title', 'amount', 'category', 'approval_status'])
                ->whereBetween('expense_date', [$start->toDateString(), $end->toDateString()])
                ->orderBy('id')
                ->chunk(self::QUERY_CHUNK_SIZE, function ($chunk) use (&$rows, &$ledgerRows): void {
                    foreach ($chunk as $expense) {
                        /** @var Expense $expense */
                        $rows[] = [
                            'sort_date' => optional($expense->expense_date)?->format('Y-m-d') ?? '',
                            'date' => optional($expense->expense_date)?->format('d/m/Y') ?? __('erp.common.none'),
                            'title' => $expense->title,
                            'subtitle' => __('erp.reports.rows.expense_prefix', ['category' => __('erp.resources.expense.categories.'.$expense->category)]),
                            'amount' => $this->formatMoney((float) $expense->amount),
                            'badge' => __('erp.common.expense'),
                        ];

                        $ledgerRows[] = [
                            'date_key' => optional($expense->expense_date)?->format('Y-m-d') ?? '',
                            'date' => optional($expense->expense_date)?->format('d/m/Y') ?? __('erp.common.none'),
                            'document_type' => __('erp.common.expense'),
                            'reference' => $expense->reference ?: ('EXP-'.$expense->getKey()),
                            'counterparty' => $expense->vendor ?: __('erp.common.not_provided'),
                            'description' => $expense->title,
                            'debit' => $this->formatMoney((float) $expense->amount),
                            'credit' => '',
                            'tax' => '',
                            'balance_due' => '',
                            'status' => __('erp.resources.expense.approval_statuses.'.($expense->approval_status ?? 'pending')),
                        ];
                    }
                });
        }

        if ($includeAudit) {
            ActivityLog::query()
                ->select(['id', 'created_at', 'user_id', 'action', 'subject_type', 'subject_id', 'meta_json'])
                ->whereBetween('created_at', [$start, $end])
                ->orderBy('id')
                ->chunk(self::QUERY_CHUNK_SIZE, function ($chunk) use (&$rows, &$auditRows): void {
                    foreach ($chunk as $log) {
                        /** @var ActivityLog $log */
                        $rows[] = [
                            'sort_date' => optional($log->created_at)?->format('Y-m-d H:i:s') ?? '',
                            'date' => optional($log->created_at)?->format('d/m/Y H:i') ?? __('erp.common.none'),
                            'title' => $log->action,
                            'subtitle' => __('erp.reports.rows.audit_event'),
                            'amount' => __('erp.common.none'),
                            'badge' => class_basename((string) ($log->subject_type ?: ActivityLog::class)),
                        ];

                        $auditRows[] = [
                            'timestamp' => optional($log->created_at)?->format('d/m/Y H:i:s') ?? __('erp.common.none'),
                            'user' => (string) ($log->user_id ?? 'system'),
                            'action' => $log->action,
                            'subject' => class_basename((string) ($log->subject_type ?: ActivityLog::class)),
                            'subject_id' => (string) ($log->subject_id ?? ''),
                            'context' => json_encode($log->meta_json ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                        ];
                    }
                });
        }

        if ($includeWhatsapp) {
            $whatsappRows[] = [
                'label' => __('erp.reports.summary.whatsapp_conversations'),
                'value' => number_format($whatsappSummary['conversations']),
            ];
            $whatsappRows[] = [
                'label' => __('erp.reports.summary.whatsapp_messages'),
                'value' => number_format($whatsappSummary['messages']),
            ];
            $whatsappRows[] = [
                'label' => __('erp.reports.summary.whatsapp_read_rate'),
                'value' => number_format($whatsappSummary['readRate'], 1, ',', ' ').' %',
            ];
        }

        if ($includeEngagement) {
            $engagementRows[] = [
                'label' => __('erp.reports.summary.engaged_clients'),
                'value' => number_format($engagementSummary['engagedClients']),
            ];
            $engagementRows[] = [
                'label' => __('erp.reports.summary.total_clients'),
                'value' => number_format($engagementSummary['totalClients']),
            ];
            $engagementRows[] = [
                'label' => __('erp.reports.summary.engagement_rate'),
                'value' => number_format($engagementSummary['engagementRate'], 1, ',', ' ').' %',
            ];
        }

        usort($rows, static fn (array $a, array $b): int => strcmp((string) $b['sort_date'], (string) $a['sort_date']));
        usort($ledgerRows, static fn (array $a, array $b): int => strcmp((string) $a['date_key'], (string) $b['date_key']));

        $rows = array_map(static fn (array $row): array => Arr::except($row, ['sort_date']), $rows);
        $previewRows = array_slice($rows, 0, 8);
        $ledgerRows = array_map(static fn (array $row): array => Arr::except($row, ['date_key']), $ledgerRows);

        $summary = [
            ['label' => __('erp.reports.summary.revenue'), 'value' => $this->formatMoney($revenue), 'tone' => 'text-primary'],
            ['label' => __('erp.reports.summary.payments'), 'value' => $this->formatMoney($paymentTotal), 'tone' => 'text-emerald-600'],
            ['label' => __('erp.reports.summary.expenses'), 'value' => $this->formatMoney($expenseTotal), 'tone' => 'text-rose-600'],
            ['label' => __('erp.reports.summary.taxes'), 'value' => $this->formatMoney($taxTotal), 'tone' => 'text-amber-600'],
            ['label' => __('erp.reports.summary.net'), 'value' => $this->formatMoney($netResult), 'tone' => $netResult >= 0 ? 'text-emerald-600' : 'text-rose-600'],
            ['label' => __('erp.reports.summary.cash_flow'), 'value' => $this->formatMoney($cashFlow), 'tone' => $cashFlow >= 0 ? 'text-emerald-600' : 'text-rose-600'],
            ['label' => __('erp.reports.summary.profit_loss'), 'value' => $this->formatMoney($profitLoss), 'tone' => $profitLoss >= 0 ? 'text-emerald-600' : 'text-rose-600'],
            ['label' => __('erp.reports.summary.collection_rate'), 'value' => number_format($collectionRate, 1, ',', ' ').' %', 'tone' => 'text-cyan-700'],
            ['label' => __('erp.reports.summary.entries'), 'value' => number_format($entryCount), 'tone' => 'text-slate-600'],
            ['label' => __('erp.reports.summary.audit'), 'value' => number_format($auditCount), 'tone' => 'text-violet-600'],
            ['label' => __('erp.reports.summary.whatsapp_messages'), 'value' => number_format($whatsappSummary['messages']), 'tone' => 'text-indigo-700'],
            ['label' => __('erp.reports.summary.whatsapp_read_rate'), 'value' => number_format($whatsappSummary['readRate'], 1, ',', ' ').' %', 'tone' => 'text-indigo-700'],
            ['label' => __('erp.reports.summary.engaged_clients'), 'value' => number_format($engagementSummary['engagedClients']), 'tone' => 'text-fuchsia-700'],
            ['label' => __('erp.reports.summary.engagement_rate'), 'value' => number_format($engagementSummary['engagementRate'], 1, ',', ' ').' %', 'tone' => 'text-fuchsia-700'],
            ['label' => __('erp.reports.summary.format'), 'value' => strtoupper($format), 'tone' => 'text-sky-600'],
        ];

        return [
            'summary' => $summary,
            'previewRows' => $previewRows,
            'rows' => $rows,
            'ledgerRows' => $ledgerRows,
            'auditRows' => $auditRows,
            'whatsappRows' => $whatsappRows,
            'engagementRows' => $engagementRows,
            'meta' => [
                'startDate' => $start->format('d/m/Y'),
                'endDate' => $end->format('d/m/Y'),
                'generatedAt' => now()->format('d/m/Y H:i'),
                'format' => strtoupper($format),
                'includeCharts' => $includeCharts,
                'generatedBy' => $userId ? 'Utilisateur #'.$userId : 'Système',
            ],
        ];
    }

    protected function storeExport(array $report, string $format, ?int $userId = null): string
    {
        $extension = strtolower($format) === 'csv' ? 'csv' : 'pdf';
        $folder = 'reports/'.now()->format('Y/m');
        $fileName = 'rapport-financier-'.now()->format('Ymd-His').'-'.($userId ?? 'system').'.'.$extension;
        $path = $folder.'/'.$fileName;

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

    protected function storeCsvExportStreamed(
        Carbon $start,
        Carbon $end,
        array $selectedModules,
        array $report,
        ?int $userId = null,
    ): string {
        $folder = 'reports/'.now()->format('Y/m');
        $fileName = 'rapport-financier-'.now()->format('Ymd-His').'-'.($userId ?? 'system').'.csv';
        $path = $folder.'/'.$fileName;

        Storage::disk('local')->makeDirectory($folder);
        $absolutePath = Storage::disk('local')->path($path);

        $handle = fopen($absolutePath, 'wb');

        if ($handle === false) {
            throw new \RuntimeException('Unable to create CSV export file.');
        }

        try {
            fputcsv($handle, ['Journal comptable prêt pour audit', $report['meta']['generatedAt']], ';');
            fputcsv($handle, ['Période', $report['meta']['startDate'].' -> '.$report['meta']['endDate']], ';');
            fputcsv($handle, ['Généré par', $report['meta']['generatedBy'] ?? 'Système'], ';');
            fputcsv($handle, []);
            fputcsv($handle, ['Indicateur', 'Valeur'], ';');

            foreach ($report['summary'] as $item) {
                fputcsv($handle, [$item['label'], $item['value']], ';');
            }

            fputcsv($handle, []);
            fputcsv($handle, ['Journal comptable'], ';');
            fwrite($handle, "Date;Type de pièce;Référence;Tiers;Description;Débit;Crédit;Taxes;Solde dû;Statut\n");

            $selectedModules = array_merge([
                'revenue' => false,
                'expenses' => false,
                'payments' => false,
                'taxes' => false,
                'audit' => false,
            ], $selectedModules);

            if (! empty($selectedModules['revenue'])) {
                Invoice::query()
                    ->with('client')
                    ->select(['id', 'client_id', 'invoice_number', 'issue_date', 'status', 'total', 'tax_total', 'balance_due'])
                    ->whereBetween('issue_date', [$start->toDateString(), $end->toDateString()])
                    ->orderBy('id')
                    ->chunk(self::QUERY_CHUNK_SIZE, function ($chunk) use ($handle): void {
                        foreach ($chunk as $invoice) {
                            /** @var Invoice $invoice */
                            fputcsv($handle, [
                                optional($invoice->issue_date)?->format('d/m/Y') ?? __('erp.common.none'),
                                __('erp.common.invoice'),
                                $invoice->invoice_number,
                                $invoice->client?->company_name ?: $invoice->client?->contact_name ?: __('erp.common.account_client'),
                                __('erp.reports.rows.client_invoice'),
                                '',
                                $this->formatMoney((float) $invoice->total),
                                $this->formatMoney((float) $invoice->tax_total),
                                $this->formatMoney((float) $invoice->balance_due),
                                __('erp.resources.invoice.statuses.'.$invoice->status),
                            ], ';');
                        }
                    });
            }

            if (! empty($selectedModules['payments'])) {
                Payment::query()
                    ->with(['client', 'invoice'])
                    ->select(['id', 'client_id', 'invoice_id', 'payment_date', 'reference', 'amount', 'reconciled_at'])
                    ->whereBetween('payment_date', [$start->toDateString(), $end->toDateString()])
                    ->orderBy('id')
                    ->chunk(self::QUERY_CHUNK_SIZE, function ($chunk) use ($handle): void {
                        foreach ($chunk as $payment) {
                            /** @var Payment $payment */
                            fputcsv($handle, [
                                optional($payment->payment_date)?->format('d/m/Y') ?? __('erp.common.none'),
                                __('erp.common.transaction'),
                                $payment->reference ?: __('erp.reports.rows.unreferenced_payment'),
                                $payment->client?->company_name ?: $payment->client?->contact_name ?: __('erp.common.account_client'),
                                $payment->invoice?->invoice_number ?: __('erp.reports.rows.free_payment'),
                                '',
                                $this->formatMoney((float) $payment->amount),
                                '',
                                $this->formatMoney((float) ($payment->invoice?->balance_due ?? 0)),
                                __('erp.resources.payment.reconciliation.'.$payment->reconciliationState()),
                            ], ';');
                        }
                    });
            }

            if (! empty($selectedModules['expenses'])) {
                Expense::query()
                    ->select(['id', 'expense_date', 'reference', 'vendor', 'title', 'amount', 'category', 'approval_status'])
                    ->whereBetween('expense_date', [$start->toDateString(), $end->toDateString()])
                    ->orderBy('id')
                    ->chunk(self::QUERY_CHUNK_SIZE, function ($chunk) use ($handle): void {
                        foreach ($chunk as $expense) {
                            /** @var Expense $expense */
                            fputcsv($handle, [
                                optional($expense->expense_date)?->format('d/m/Y') ?? __('erp.common.none'),
                                __('erp.common.expense'),
                                $expense->reference ?: ('EXP-'.$expense->getKey()),
                                $expense->vendor ?: __('erp.common.not_provided'),
                                $expense->title,
                                $this->formatMoney((float) $expense->amount),
                                '',
                                '',
                                '',
                                __('erp.resources.expense.approval_statuses.'.($expense->approval_status ?? 'pending')),
                            ], ';');
                        }
                    });
            }

            if (! empty($selectedModules['audit'])) {
                fputcsv($handle, []);
                fputcsv($handle, ['Piste d\'audit'], ';');
                fwrite($handle, "Horodatage;Utilisateur;Action;Sujet;Identifiant;Contexte\n");

                ActivityLog::query()
                    ->select(['id', 'created_at', 'user_id', 'action', 'subject_type', 'subject_id', 'meta_json'])
                    ->whereBetween('created_at', [$start, $end])
                    ->orderBy('id')
                    ->chunk(self::QUERY_CHUNK_SIZE, function ($chunk) use ($handle): void {
                        foreach ($chunk as $log) {
                            /** @var ActivityLog $log */
                            fputcsv($handle, [
                                optional($log->created_at)?->format('d/m/Y H:i:s') ?? __('erp.common.none'),
                                (string) ($log->user_id ?? 'system'),
                                $log->action,
                                class_basename((string) ($log->subject_type ?: ActivityLog::class)),
                                (string) ($log->subject_id ?? ''),
                                json_encode($log->meta_json ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                            ], ';');
                        }
                    });
            }
        } finally {
            fclose($handle);
        }

        return $path;
    }

    protected function collectPreviewRows(Carbon $start, Carbon $end, array $selectedModules): array
    {
        $selectedModules = array_merge([
            'revenue' => false,
            'expenses' => false,
            'payments' => false,
            'taxes' => false,
            'audit' => false,
            'whatsapp' => false,
            'engagement' => false,
        ], $selectedModules);

        $limit = 8;
        $rows = [];

        if (! empty($selectedModules['revenue'])) {
            foreach (Invoice::query()
                ->select(['id', 'invoice_number', 'issue_date', 'status', 'total'])
                ->whereBetween('issue_date', [$start->toDateString(), $end->toDateString()])
                ->latest('issue_date')
                ->take($limit)
                ->get() as $invoice) {
                /** @var Invoice $invoice */
                $rows[] = [
                    'sort_date' => optional($invoice->issue_date)?->format('Y-m-d') ?? '',
                    'date' => optional($invoice->issue_date)?->format('d/m/Y') ?? __('erp.common.none'),
                    'title' => $invoice->invoice_number,
                    'subtitle' => __('erp.reports.rows.client_invoice'),
                    'amount' => $this->formatMoney((float) $invoice->total),
                    'badge' => __('erp.resources.invoice.statuses.'.$invoice->status),
                ];
            }
        }

        if (! empty($selectedModules['payments'])) {
            foreach (Payment::query()
                ->with(['invoice:id,invoice_number'])
                ->select(['id', 'invoice_id', 'payment_date', 'reference', 'amount'])
                ->whereBetween('payment_date', [$start->toDateString(), $end->toDateString()])
                ->latest('payment_date')
                ->take($limit)
                ->get() as $payment) {
                /** @var Payment $payment */
                $rows[] = [
                    'sort_date' => optional($payment->payment_date)?->format('Y-m-d') ?? '',
                    'date' => optional($payment->payment_date)?->format('d/m/Y') ?? __('erp.common.none'),
                    'title' => $payment->reference ?: __('erp.reports.rows.unreferenced_payment'),
                    'subtitle' => $payment->invoice?->invoice_number ?: __('erp.reports.rows.free_payment'),
                    'amount' => $this->formatMoney((float) $payment->amount),
                    'badge' => __('erp.common.transaction'),
                ];
            }
        }

        if (! empty($selectedModules['expenses'])) {
            foreach (Expense::query()
                ->select(['id', 'expense_date', 'title', 'category', 'amount'])
                ->whereBetween('expense_date', [$start->toDateString(), $end->toDateString()])
                ->latest('expense_date')
                ->take($limit)
                ->get() as $expense) {
                /** @var Expense $expense */
                $rows[] = [
                    'sort_date' => optional($expense->expense_date)?->format('Y-m-d') ?? '',
                    'date' => optional($expense->expense_date)?->format('d/m/Y') ?? __('erp.common.none'),
                    'title' => $expense->title,
                    'subtitle' => __('erp.reports.rows.expense_prefix', ['category' => __('erp.resources.expense.categories.'.$expense->category)]),
                    'amount' => $this->formatMoney((float) $expense->amount),
                    'badge' => __('erp.common.expense'),
                ];
            }
        }

        if (! empty($selectedModules['audit'])) {
            foreach (ActivityLog::query()
                ->select(['id', 'created_at', 'action', 'subject_type'])
                ->whereBetween('created_at', [$start, $end])
                ->latest('created_at')
                ->take($limit)
                ->get() as $log) {
                /** @var ActivityLog $log */
                $rows[] = [
                    'sort_date' => optional($log->created_at)?->format('Y-m-d H:i:s') ?? '',
                    'date' => optional($log->created_at)?->format('d/m/Y H:i') ?? __('erp.common.none'),
                    'title' => $log->action,
                    'subtitle' => __('erp.reports.rows.audit_event'),
                    'amount' => __('erp.common.none'),
                    'badge' => class_basename((string) ($log->subject_type ?: ActivityLog::class)),
                ];
            }
        }

        usort($rows, static fn (array $a, array $b): int => strcmp((string) $b['sort_date'], (string) $a['sort_date']));

        return array_map(
            static fn (array $row): array => Arr::except($row, ['sort_date']),
            array_slice($rows, 0, $limit),
        );
    }

    protected function buildCsvContent(array $report): string
    {
        $handle = fopen('php://temp', 'r+');

        fputcsv($handle, ['Journal comptable prêt pour audit', $report['meta']['generatedAt']], ';');
        fputcsv($handle, ['Période', $report['meta']['startDate'].' -> '.$report['meta']['endDate']], ';');
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

        if (! empty($report['auditRows'])) {
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

        if (! empty($report['whatsappRows'])) {
            fputcsv($handle, []);
            fputcsv($handle, ['WhatsApp Analytics'], ';');
            fwrite($handle, "Indicateur;Valeur\n");

            foreach ($report['whatsappRows'] as $row) {
                fputcsv($handle, [$row['label'], $row['value']], ';');
            }
        }

        if (! empty($report['engagementRows'])) {
            fputcsv($handle, []);
            fputcsv($handle, ['Client Engagement Analytics'], ';');
            fwrite($handle, "Indicateur;Valeur\n");

            foreach ($report['engagementRows'] as $row) {
                fputcsv($handle, [$row['label'], $row['value']], ';');
            }
        }

        rewind($handle);
        $content = stream_get_contents($handle) ?: '';
        fclose($handle);

        return $content;
    }

    protected function formatMoney(float $amount): string
    {
        return number_format($amount, 2, ',', ' ').' FCFA';
    }

    protected function storeExcelExport(array $report, ?int $userId = null): string
    {
        $folder = 'reports/'.now()->format('Y/m');
        $fileName = 'rapport-financier-'.now()->format('Ymd-His').'-'.($userId ?? 'system').'.xls';
        $path = $folder.'/'.$fileName;

        Storage::disk('local')->put($path, $this->buildExcelContent($report));

        return $path;
    }

    protected function buildExcelContent(array $report): string
    {
        $summaryRows = collect($report['summary'])->map(fn (array $item): string => '<tr><td>'.e($item['label']).'</td><td>'.e($item['value']).'</td></tr>')->implode('');
        $ledgerRows = collect($report['ledgerRows'])->map(fn (array $row): string => '<tr><td>'.e($row['date']).'</td><td>'.e($row['document_type']).'</td><td>'.e($row['reference']).'</td><td>'.e($row['counterparty']).'</td><td>'.e($row['description']).'</td><td>'.e($row['debit']).'</td><td>'.e($row['credit']).'</td><td>'.e($row['tax']).'</td><td>'.e($row['balance_due']).'</td><td>'.e($row['status']).'</td></tr>')->implode('');
        $auditRows = collect($report['auditRows'])->map(fn (array $row): string => '<tr><td>'.e($row['timestamp']).'</td><td>'.e($row['user']).'</td><td>'.e($row['action']).'</td><td>'.e($row['subject']).'</td><td>'.e($row['subject_id']).'</td><td>'.e($row['context']).'</td></tr>')->implode('');
        $whatsappRows = collect($report['whatsappRows'] ?? [])->map(fn (array $row): string => '<tr><td>'.e($row['label']).'</td><td>'.e($row['value']).'</td></tr>')->implode('');
        $engagementRows = collect($report['engagementRows'] ?? [])->map(fn (array $row): string => '<tr><td>'.e($row['label']).'</td><td>'.e($row['value']).'</td></tr>')->implode('');

        return '<html><head><meta charset="UTF-8"></head><body>'
            .'<table border="1"><tr><th colspan="2">Résumé</th></tr>'.$summaryRows.'</table><br/>'
            .'<table border="1"><tr><th>Date</th><th>Type de pièce</th><th>Référence</th><th>Tiers</th><th>Description</th><th>Débit</th><th>Crédit</th><th>Taxes</th><th>Solde dû</th><th>Statut</th></tr>'.$ledgerRows.'</table>'
            .(! empty($auditRows) ? '<br/><table border="1"><tr><th>Horodatage</th><th>Utilisateur</th><th>Action</th><th>Sujet</th><th>Identifiant</th><th>Contexte</th></tr>'.$auditRows.'</table>' : '')
            .(! empty($whatsappRows) ? '<br/><table border="1"><tr><th colspan="2">WhatsApp Analytics</th></tr>'.$whatsappRows.'</table>' : '')
            .(! empty($engagementRows) ? '<br/><table border="1"><tr><th colspan="2">Client Engagement Analytics</th></tr>'.$engagementRows.'</table>' : '')
            .'</body></html>';
    }

    protected function buildWhatsappAnalytics(Carbon $start, Carbon $end, bool $enabled): array
    {
        if (! $enabled || ! Schema::hasTable('whatsapp_conversations')) {
            return ['conversations' => 0, 'messages' => 0, 'readRate' => 0.0];
        }

        $conversationQuery = WhatsappConversation::query()->whereBetween('last_message_at', [$start, $end]);
        $conversations = (int) $conversationQuery->count();

        $messages = 0;
        if (Schema::hasTable('whatsapp_messages')) {
            $messages = (int) WhatsappMessage::query()->whereBetween('sent_at', [$start, $end])->count();
        } elseif (Schema::hasTable('whatsapp_message_logs')) {
            $messages = (int) WhatsappMessageLog::query()->whereBetween('sent_at', [$start, $end])->count();
        }

        $readMessages = 0;
        if (Schema::hasTable('whatsapp_messages')) {
            $readMessages = (int) WhatsappMessage::query()
                ->whereBetween('sent_at', [$start, $end])
                ->where(function ($query): void {
                    $query->whereNotNull('read_at')->orWhere('ack_status', 'read');
                })
                ->count();
        } elseif (Schema::hasTable('whatsapp_message_logs')) {
            $readMessages = (int) WhatsappMessageLog::query()
                ->whereBetween('sent_at', [$start, $end])
                ->where(function ($query): void {
                    $query->whereNotNull('read_at')->orWhere('ack_status', 'read');
                })
                ->count();
        }

        return [
            'conversations' => $conversations,
            'messages' => $messages,
            'readRate' => $messages > 0 ? round(($readMessages / $messages) * 100, 1) : 0.0,
        ];
    }

    protected function buildClientEngagementAnalytics(Carbon $start, Carbon $end, bool $enabled): array
    {
        if (! $enabled || ! Schema::hasTable('clients')) {
            return ['totalClients' => 0, 'engagedClients' => 0, 'engagementRate' => 0.0];
        }

        $totalClients = (int) Client::query()->count();

        $engagedClientIds = [];
        $engagedClientIds = array_merge(
            $engagedClientIds,
            Invoice::query()
                ->whereBetween('issue_date', [$start->toDateString(), $end->toDateString()])
                ->whereNotNull('client_id')
                ->pluck('client_id')
                ->all(),
            Payment::query()
                ->whereBetween('payment_date', [$start->toDateString(), $end->toDateString()])
                ->whereNotNull('client_id')
                ->pluck('client_id')
                ->all(),
        );

        if (Schema::hasTable('whatsapp_conversations')) {
            $engagedClientIds = array_merge(
                $engagedClientIds,
                WhatsappConversation::query()
                    ->whereBetween('last_message_at', [$start, $end])
                    ->whereNotNull('client_id')
                    ->pluck('client_id')
                    ->all(),
            );
        }

        $engagedClients = count(array_unique(array_filter($engagedClientIds, static fn (mixed $id): bool => filled($id))));

        return [
            'totalClients' => $totalClients,
            'engagedClients' => $engagedClients,
            'engagementRate' => $totalClients > 0 ? round(($engagedClients / $totalClients) * 100, 1) : 0.0,
        ];
    }
}
