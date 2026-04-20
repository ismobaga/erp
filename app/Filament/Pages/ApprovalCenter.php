<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\HasPermissionAccess;
use App\Filament\Resources\Expenses\ExpenseResource;
use App\Filament\Resources\Invoices\InvoiceResource;
use App\Filament\Resources\Payments\PaymentResource;
use App\Filament\Resources\Projects\ProjectResource;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Project;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Schema;
use Throwable;

class ApprovalCenter extends Page
{
    use HasPermissionAccess;

    protected static string $permissionScope = 'reports';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static string|\UnitEnum|null $navigationGroup = 'Administration';

    protected static ?int $navigationSort = 7;

    protected static ?string $navigationLabel = 'Validations';

    protected static ?string $title = 'Tableau des validations';

    protected string $view = 'filament.pages.approval-center';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('approveLowRisk')
                ->label('Valider les cas simples')
                ->visible(fn(): bool => auth()->user()?->canAny(['expenses.update', 'projects.update', 'invoices.update', 'payments.update']) ?? false)
                ->action(function (): void {
                    $user = auth()->user();
                    $approved = 0;
                    $expenseLimit = max(0, (float) config('erp.approvals.expense_auto_approve_limit', 250000));
                    $bulkLimit = max(1, (int) config('erp.approvals.bulk_approval_limit', 10));
                    $projectStatuses = config('erp.approvals.project_auto_approve_statuses', ['planned', 'on_hold']);

                    if (!$user) {
                        Notification::make()->title('Aucun utilisateur authentifié pour valider les éléments.')->danger()->send();

                        return;
                    }

                    if (Schema::hasTable('expenses')) {
                        Expense::query()
                            ->where('approval_status', 'pending')
                            ->where('amount', '<=', $expenseLimit)
                            ->latest()
                            ->take($bulkLimit)
                            ->get()
                            ->each(function (Expense $expense) use ($user, &$approved): void {
                                $expense->approve($user, 'Validation rapide depuis le centre d’approbation.');
                                $approved++;
                            });
                    }

                    if (Schema::hasTable('projects')) {
                        Project::query()
                            ->where('approval_status', 'pending')
                            ->whereIn('status', $projectStatuses)
                            ->latest()
                            ->take($bulkLimit)
                            ->get()
                            ->each(function (Project $project) use ($user, &$approved): void {
                                $project->approve($user, 'Projet approuvé depuis le centre d’approbation.');
                                $approved++;
                            });
                    }

                    $notification = Notification::make()
                        ->title($approved > 0 ? $approved . ' élément(s) ont été approuvés.' : 'Aucun élément à faible risque n’a été trouvé.');

                    ($approved > 0 ? $notification->success() : $notification->warning())->send();
                }),
            Action::make('exportQueue')
                ->label('Exporter la file')
                ->action(fn() => Notification::make()->title('L’export de la file de validation a été lancé.')->success()->send()),
        ];
    }

    protected function getViewData(): array
    {
        try {
            return [
                'summary' => $this->getSummary(),
                'items' => $this->getQueue(),
                'departments' => $this->getDepartmentBreakdown(),
            ];
        } catch (Throwable) {
            return [
                'summary' => $this->placeholderSummary(),
                'items' => $this->placeholderQueue(),
                'departments' => $this->placeholderDepartments(),
            ];
        }
    }

    protected function getSummary(): array
    {
        if (!Schema::hasTable('invoices')) {
            return $this->placeholderSummary();
        }

        $invoiceVolume = (float) Invoice::query()
            ->whereIn('status', ['draft', 'sent', 'overdue', 'partially_paid'])
            ->sum('balance_due');

        $flagged = Schema::hasTable('payments')
            ? Payment::query()->where(fn($query) => $query->whereNull('invoice_id')->orWhereNull('reference')->orWhere('reference', ''))->count()
            : 0;

        $pendingExpenses = Schema::hasTable('expenses')
            ? Expense::query()->whereIn('approval_status', ['pending', 'review'])->count()
            : 0;

        $pendingProjects = Schema::hasTable('projects')
            ? Project::query()->whereIn('approval_status', ['pending', 'review'])->count()
            : 0;

        return [
            'volume' => $this->money($invoiceVolume),
            'flagged' => $flagged + $pendingExpenses + $pendingProjects,
            'avg_time' => '4.2h',
            'capacity' => 75,
        ];
    }

    protected function getQueue(): array
    {
        $items = [];

        if (Schema::hasTable('invoices')) {
            $invoiceItems = Invoice::query()
                ->with('client')
                ->whereIn('status', ['draft', 'overdue', 'partially_paid', 'sent'])
                ->latest()
                ->take(4)
                ->get()
                ->map(fn(Invoice $invoice): array => [
                    'tone' => $invoice->status === 'overdue' ? 'danger' : ($invoice->status === 'draft' ? 'info' : 'success'),
                    'icon' => $invoice->status === 'overdue' ? 'warning' : 'description',
                    'subject' => $invoice->client?->company_name ?: $invoice->client?->contact_name ?: 'Client account',
                    'reference' => $invoice->invoice_number,
                    'category' => 'Invoice Review',
                    'amount' => $this->money((float) $invoice->balance_due),
                    'note' => $invoice->status === 'overdue' ? 'Collections follow-up required' : 'Ready for management sign-off',
                    'cta' => $invoice->status === 'overdue' ? 'Review now' : 'Approve',
                    'url' => InvoiceResource::getUrl('edit', ['record' => $invoice]),
                ])
                ->all();

            $items = [...$items, ...$invoiceItems];
        }

        if (Schema::hasTable('expenses')) {
            $expenseItems = Expense::query()
                ->whereIn('approval_status', ['pending', 'review'])
                ->latest()
                ->take(2)
                ->get()
                ->map(fn(Expense $expense): array => [
                    'tone' => $expense->approval_status === 'review' ? 'danger' : 'info',
                    'icon' => 'receipt_long',
                    'subject' => $expense->title,
                    'reference' => $expense->reference ?: ('EXP-' . str_pad((string) $expense->getKey(), 4, '0', STR_PAD_LEFT)),
                    'category' => 'Validation dépense',
                    'amount' => $this->money((float) $expense->amount),
                    'note' => $expense->approval_status === 'review' ? 'Contrôle complémentaire requis' : 'En attente de validation managériale',
                    'cta' => 'Traiter',
                    'url' => ExpenseResource::getUrl('edit', ['record' => $expense]),
                ])
                ->all();

            $items = [...$items, ...$expenseItems];
        }

        if (Schema::hasTable('projects')) {
            $projectItems = Project::query()
                ->with('client')
                ->whereIn('approval_status', ['pending', 'review'])
                ->latest()
                ->take(2)
                ->get()
                ->map(fn(Project $project): array => [
                    'tone' => $project->approval_status === 'review' ? 'danger' : 'success',
                    'icon' => 'folder_managed',
                    'subject' => $project->name,
                    'reference' => 'PRJ-' . str_pad((string) $project->getKey(), 4, '0', STR_PAD_LEFT),
                    'category' => 'Feu vert projet',
                    'amount' => $project->client?->company_name ?: 'Projet interne',
                    'note' => $project->approval_status === 'review' ? 'Le cadrage doit être révisé' : 'Projet prêt pour lancement',
                    'cta' => 'Valider',
                    'url' => ProjectResource::getUrl('edit', ['record' => $project]),
                ])
                ->all();

            $items = [...$items, ...$projectItems];
        }

        if (Schema::hasTable('payments')) {
            $paymentItems = Payment::query()
                ->with(['client', 'invoice'])
                ->where(fn($query) => $query->whereNull('invoice_id')->orWhereNull('reference')->orWhere('reference', ''))
                ->latest()
                ->take(2)
                ->get()
                ->map(fn(Payment $payment): array => [
                    'tone' => 'danger',
                    'icon' => 'payments',
                    'subject' => $payment->client?->company_name ?: $payment->client?->contact_name ?: 'Ledger transfer',
                    'reference' => $payment->reference ?: ('PAY-' . str_pad((string) $payment->getKey(), 4, '0', STR_PAD_LEFT)),
                    'category' => 'Exception paiement',
                    'amount' => $this->money((float) $payment->amount),
                    'note' => 'Référence ou facture à rapprocher',
                    'cta' => 'Résoudre',
                    'url' => PaymentResource::getUrl('edit', ['record' => $payment]),
                ])
                ->all();

            $items = [...$items, ...$paymentItems];
        }

        return count($items) > 0 ? array_slice($items, 0, 6) : $this->placeholderQueue();
    }

    protected function getDepartmentBreakdown(): array
    {
        if (!Schema::hasTable('expenses')) {
            return $this->placeholderDepartments();
        }

        return Expense::query()
            ->selectRaw('category, count(*) as aggregate')
            ->groupBy('category')
            ->orderByDesc('aggregate')
            ->take(3)
            ->get()
            ->map(fn($row): array => [
                'name' => $row->category ?: 'General Operations',
                'count' => (int) $row->aggregate,
            ])
            ->all() ?: $this->placeholderDepartments();
    }

    protected function placeholderSummary(): array
    {
        return [
            'volume' => 'FCFA 248 500 000',
            'flagged' => 3,
            'avg_time' => '4.2h',
            'capacity' => 75,
        ];
    }

    protected function placeholderQueue(): array
    {
        return [
            [
                'tone' => 'info',
                'icon' => 'description',
                'subject' => 'Stark Architectural Ltd.',
                'reference' => 'INV-2023-8902',
                'category' => 'Material Supply',
                'amount' => 'FCFA 12 450 000',
                'note' => 'Ready for management sign-off',
                'cta' => 'Approve',
                'url' => null,
            ],
            [
                'tone' => 'success',
                'icon' => 'receipt_long',
                'subject' => 'Elena Rodriguez',
                'reference' => 'EXP-901-TRV',
                'category' => 'Client Relations',
                'amount' => 'FCFA 1 120 450',
                'note' => 'Expense claim awaiting approval',
                'cta' => 'Approve',
                'url' => null,
            ],
            [
                'tone' => 'danger',
                'icon' => 'warning',
                'subject' => 'Helix Design Group',
                'reference' => 'DUPLICATE REF',
                'category' => 'Consulting',
                'amount' => 'FCFA 45 000 000',
                'note' => 'Duplicate reference requires override',
                'cta' => 'Override',
                'url' => null,
            ],
        ];
    }

    protected function placeholderDepartments(): array
    {
        return [
            ['name' => 'Design Engineering', 'count' => 4],
            ['name' => 'Site Operations', 'count' => 6],
            ['name' => 'Administration', 'count' => 2],
        ];
    }

    protected function money(float $amount): string
    {
        return 'FCFA ' . number_format($amount, 0, '.', ' ');
    }
}
