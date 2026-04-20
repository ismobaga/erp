<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\HasPermissionAccess;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Payment;
use BackedEnum;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;

class ReportGeneration extends Page
{
    use HasPermissionAccess;

    protected static string $permissionScope = 'reports';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPresentationChartBar;

    protected static string|\UnitEnum|null $navigationGroup = 'Comptabilité';

    protected static ?int $navigationSort = 8;

    protected static ?string $navigationLabel = 'Rapports';

    protected static ?string $title = 'Exportation Analytique Financière';

    protected static ?string $slug = 'report-generation';

    protected string $view = 'filament.pages.report-generation';

    public string $startDate = '';

    public string $endDate = '';

    public string $exportFormat = 'pdf';

    public array $selectedModules = [
        'revenue' => true,
        'expenses' => false,
        'payments' => true,
        'taxes' => false,
    ];

    public bool $includeCharts = true;

    public bool $autoScheduleEnabled = false;

    public string $scheduleFrequency = 'Hebdomadaire';

    public string $nextExecutionAt = '';

    public string $scheduleEmail = '';

    public bool $reportReady = false;

    public array $reportSummary = [];

    public array $previewRows = [];

    public array $scheduledPlans = [];

    public function mount(): void
    {
        $this->usePreset('quarter');
        $this->nextExecutionAt = now()->addDay()->setTime(8, 0)->format('Y-m-d\TH:i');
        $this->scheduleEmail = auth()->user()?->email ?? '';
        $this->scheduledPlans = $this->defaultScheduledPlans();
    }

    public function usePreset(string $preset): void
    {
        [$start, $end] = match ($preset) {
            'year' => [now()->startOfYear(), now()->endOfDay()],
            default => [now()->subMonths(3)->startOfDay(), now()->endOfDay()],
        };

        $this->startDate = $start->toDateString();
        $this->endDate = $end->toDateString();
    }

    public function generateReport(): void
    {
        $validated = $this->validate([
            'startDate' => ['required', 'date'],
            'endDate' => ['required', 'date', 'after_or_equal:startDate'],
            'exportFormat' => ['required', 'in:pdf,csv'],
            'selectedModules.revenue' => ['boolean'],
            'selectedModules.expenses' => ['boolean'],
            'selectedModules.payments' => ['boolean'],
            'selectedModules.taxes' => ['boolean'],
            'includeCharts' => ['boolean'],
            'autoScheduleEnabled' => ['boolean'],
            'scheduleFrequency' => ['nullable', 'in:Quotidienne,Hebdomadaire,Mensuelle'],
            'nextExecutionAt' => ['nullable', 'date'],
            'scheduleEmail' => ['nullable', 'email'],
        ]);

        if ($this->autoScheduleEnabled) {
            $this->validate([
                'scheduleFrequency' => ['required', 'in:Quotidienne,Hebdomadaire,Mensuelle'],
                'nextExecutionAt' => ['required', 'date'],
                'scheduleEmail' => ['required', 'email'],
            ]);
        }

        $start = Carbon::parse($validated['startDate'])->startOfDay();
        $end = Carbon::parse($validated['endDate'])->endOfDay();

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

        $revenue = $this->selectedModules['revenue'] ? (float) $invoices->sum('total') : 0.0;
        $expenseTotal = $this->selectedModules['expenses'] ? (float) $expenses->sum('amount') : 0.0;
        $paymentTotal = $this->selectedModules['payments'] ? (float) $payments->sum('amount') : 0.0;
        $taxTotal = $this->selectedModules['taxes'] ? (float) $invoices->sum('tax_total') : 0.0;
        $netResult = $paymentTotal - $expenseTotal;

        $this->reportSummary = [
            ['label' => 'Chiffre d’affaires', 'value' => $this->formatMoney($revenue), 'tone' => 'text-primary'],
            ['label' => 'Encaissements', 'value' => $this->formatMoney($paymentTotal), 'tone' => 'text-emerald-600'],
            ['label' => 'Dépenses', 'value' => $this->formatMoney($expenseTotal), 'tone' => 'text-rose-600'],
            ['label' => 'Taxes & TVA', 'value' => $this->formatMoney($taxTotal), 'tone' => 'text-amber-600'],
            ['label' => 'Résultat net', 'value' => $this->formatMoney($netResult), 'tone' => $netResult >= 0 ? 'text-emerald-600' : 'text-rose-600'],
            ['label' => 'Format', 'value' => strtoupper($this->exportFormat), 'tone' => 'text-sky-600'],
        ];

        $rows = collect();

        if ($this->selectedModules['revenue']) {
            $rows = $rows->merge($invoices->map(fn(Invoice $invoice): array => [
                'date' => optional($invoice->issue_date)->format('d/m/Y') ?? '—',
                'title' => $invoice->invoice_number,
                'subtitle' => 'Facture client',
                'amount' => $this->formatMoney((float) $invoice->total),
                'badge' => strtoupper(str_replace('_', ' ', $invoice->status)),
            ]));
        }

        if ($this->selectedModules['payments']) {
            $rows = $rows->merge($payments->map(fn(Payment $payment): array => [
                'date' => optional($payment->payment_date)->format('d/m/Y') ?? '—',
                'title' => $payment->reference ?: 'Paiement sans référence',
                'subtitle' => $payment->invoice?->invoice_number ?: 'Paiement libre',
                'amount' => $this->formatMoney((float) $payment->amount),
                'badge' => 'PAIEMENT',
            ]));
        }

        if ($this->selectedModules['expenses']) {
            $rows = $rows->merge($expenses->map(fn(Expense $expense): array => [
                'date' => optional($expense->expense_date)->format('d/m/Y') ?? '—',
                'title' => $expense->title,
                'subtitle' => 'Dépense ' . $expense->category,
                'amount' => $this->formatMoney((float) $expense->amount),
                'badge' => 'DÉPENSE',
            ]));
        }

        $this->previewRows = $rows
            ->sortByDesc('date')
            ->take(8)
            ->values()
            ->all();

        $this->reportReady = true;

        if ($this->autoScheduleEnabled) {
            $this->prependScheduledPlan();
        }

        Notification::make()
            ->title('Rapport généré avec succès.')
            ->body(
                'Votre export ' . strtoupper($this->exportFormat) . ' est prêt à être exploité.'
                . ($this->autoScheduleEnabled ? ' La planification automatique est activée.' : '')
            )
            ->success()
            ->send();
    }

    protected function prependScheduledPlan(): void
    {
        $nextRun = Carbon::parse($this->nextExecutionAt);

        array_unshift($this->scheduledPlans, [
            'description' => 'Export ' . strtoupper($this->exportFormat) . ' financier',
            'frequency' => $this->scheduleFrequency,
            'nextExecution' => $nextRun->format('d M Y - H:i'),
            'status' => 'Actif',
            'statusClasses' => 'bg-green-100 text-green-800',
            'email' => $this->scheduleEmail,
        ]);

        $this->scheduledPlans = array_slice($this->scheduledPlans, 0, 6);
    }

    protected function defaultScheduledPlans(): array
    {
        return [
            [
                'description' => 'Rapport CA Trimestriel',
                'frequency' => 'Hebdomadaire',
                'nextExecution' => now()->addDays(5)->setTime(8, 0)->format('d M Y - H:i'),
                'status' => 'Actif',
                'statusClasses' => 'bg-green-100 text-green-800',
                'email' => 'direction@entreprise.com',
            ],
            [
                'description' => 'Audit Fiscal Hebdo',
                'frequency' => 'Quotidienne',
                'nextExecution' => now()->addDay()->setTime(17, 30)->format('d M Y - H:i'),
                'status' => 'En attente',
                'statusClasses' => 'bg-yellow-100 text-yellow-800',
                'email' => 'comptabilite@entreprise.com',
            ],
        ];
    }

    protected function formatMoney(float $amount): string
    {
        return number_format($amount, 2, ',', ' ') . ' FCFA';
    }
}
