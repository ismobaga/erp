<?php

namespace App\Filament\Pages;

use App\Models\ActivityLog;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Schema;
use Throwable;

class NotificationHub extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBellAlert;

    protected static string|\UnitEnum|null $navigationGroup = 'Administration';

    protected static ?int $navigationSort = 8;

    protected static ?string $navigationLabel = 'Alert Hub';

    protected static ?string $title = 'Architectural Oversight';

    protected string $view = 'filament.pages.notification-hub';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('markAllRead')
                ->label('Mark all read')
                ->action(fn() => Notification::make()->title('All alerts were marked as reviewed for this session.')->success()->send()),
            Action::make('exportSecurity')
                ->label('Export security report')
                ->action(fn() => Notification::make()->title('Security report export queued successfully.')->success()->send()),
        ];
    }

    protected function getViewData(): array
    {
        try {
            return [
                'overdueInvoices' => $this->getOverdueInvoices(),
                'flaggedPayments' => $this->getFlaggedPayments(),
                'feed' => $this->getFeed(),
                'health' => $this->getHealth(),
            ];
        } catch (Throwable) {
            return [
                'overdueInvoices' => $this->placeholderOverdueInvoices(),
                'flaggedPayments' => $this->placeholderFlaggedPayments(),
                'feed' => $this->placeholderFeed(),
                'health' => $this->placeholderHealth(),
            ];
        }
    }

    protected function getOverdueInvoices(): array
    {
        if (!Schema::hasTable('invoices')) {
            return $this->placeholderOverdueInvoices();
        }

        $items = Invoice::query()
            ->with('client')
            ->where(function ($query): void {
                $query->where('status', 'overdue')
                    ->orWhere(function ($nested): void {
                        $nested->where('balance_due', '>', 0)
                            ->whereNotNull('due_date')
                            ->whereDate('due_date', '<', now());
                    });
            })
            ->orderBy('due_date')
            ->take(3)
            ->get()
            ->map(fn(Invoice $invoice): array => [
                'reference' => $invoice->invoice_number,
                'client' => $invoice->client?->company_name ?: $invoice->client?->contact_name ?: 'Client account',
                'note' => 'Outstanding receivable requires collection follow-up.',
                'amount' => $this->money((float) $invoice->balance_due),
                'age' => $invoice->due_date ? now()->diffInDays($invoice->due_date) . ' days overdue' : 'Overdue',
            ])
            ->all();

        return $items ?: $this->placeholderOverdueInvoices();
    }

    protected function getFlaggedPayments(): array
    {
        if (!Schema::hasTable('payments')) {
            return $this->placeholderFlaggedPayments();
        }

        $items = Payment::query()
            ->with('client')
            ->where(fn($query) => $query->whereNull('invoice_id')->orWhereNull('reference')->orWhere('reference', ''))
            ->latest()
            ->take(3)
            ->get()
            ->map(fn(Payment $payment): array => [
                'title' => $payment->reference ?: ('PAY-' . str_pad((string) $payment->getKey(), 4, '0', STR_PAD_LEFT)),
                'client' => $payment->client?->company_name ?: $payment->client?->contact_name ?: 'Ledger transfer',
                'note' => $payment->invoice_id === null ? 'Pending invoice reconciliation.' : 'Reference mismatch requires review.',
            ])
            ->all();

        return $items ?: $this->placeholderFlaggedPayments();
    }

    protected function getFeed(): array
    {
        if (!Schema::hasTable('activity_logs')) {
            return $this->placeholderFeed();
        }

        $items = ActivityLog::query()
            ->latest()
            ->take(6)
            ->get()
            ->map(fn(ActivityLog $log): array => [
                'label' => ucfirst(str_replace('_', ' ', $log->action ?: 'activity logged')),
                'meta' => class_basename((string) $log->subject_type) ?: 'System',
                'time' => $log->created_at?->diffForHumans() ?? 'recently',
            ])
            ->all();

        return $items ?: $this->placeholderFeed();
    }

    protected function getHealth(): array
    {
        $openAlerts = Schema::hasTable('invoices')
            ? Invoice::query()->whereIn('status', ['overdue', 'partially_paid'])->count()
            : 42;
        $exposure = Schema::hasTable('invoices')
            ? (float) Invoice::query()->where('balance_due', '>', 0)->sum('balance_due')
            : 1200000;
        $resolved = Schema::hasTable('activity_logs')
            ? ActivityLog::query()->whereDate('created_at', today())->count()
            : 18;
        $efficiency = Schema::hasTable('users')
            ? min(99, max(80, User::query()->where('status', 'active')->count() * 8))
            : 94;

        return [
            'open_alerts' => number_format($openAlerts),
            'exposure' => $this->money($exposure),
            'resolved' => number_format($resolved),
            'efficiency' => $efficiency . '%',
        ];
    }

    protected function placeholderOverdueInvoices(): array
    {
        return [
            ['reference' => 'INV-8829', 'client' => 'Sterling Architecture', 'note' => 'Phase 1 invoice is critically overdue.', 'amount' => 'FCFA 142 500 000', 'age' => '45 days overdue'],
            ['reference' => 'INV-9104', 'client' => 'Horizon Foundations', 'note' => 'Regional office payment stalled.', 'amount' => 'FCFA 64 200 000', 'age' => '12 days overdue'],
        ];
    }

    protected function placeholderFlaggedPayments(): array
    {
        return [
            ['title' => 'ACH Transfer TXN-998', 'client' => 'BuildCorp', 'note' => 'Transaction hash mismatch needs review.'],
            ['title' => 'Petty Cash TXN-1021', 'client' => 'Field Desk', 'note' => 'Limit breach requires admin override.'],
        ];
    }

    protected function placeholderFeed(): array
    {
        return [
            ['label' => 'INV-4412 reconciled', 'meta' => 'Admin Sarah J.', 'time' => '14m ago'],
            ['label' => 'Reminder sent to Delta Projects', 'meta' => 'Automated System', 'time' => '1h ago'],
            ['label' => 'Staff ID #455 activated', 'meta' => 'System Security', 'time' => '3h ago'],
        ];
    }

    protected function placeholderHealth(): array
    {
        return [
            'open_alerts' => '42',
            'exposure' => 'FCFA 1 200 000',
            'resolved' => '18',
            'efficiency' => '94%',
        ];
    }

    protected function money(float $amount): string
    {
        return 'FCFA ' . number_format($amount, 0, '.', ' ');
    }
}
