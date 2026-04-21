<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\HasPermissionAccess;
use App\Mail\InvoiceReminderMail;
use App\Models\ActivityLog;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use App\Services\AuditTrailService;
use App\Services\ReportExportService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Schema;
use Throwable;

class NotificationHub extends Page
{
    use HasPermissionAccess;

    protected static string $permissionScope = 'reports';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBellAlert;

    protected static string|\UnitEnum|null $navigationGroup = 'Administration';

    protected static ?int $navigationSort = 8;

    protected static ?string $navigationLabel = 'Centre d’alertes';

    protected static ?string $title = 'Supervision ERP';

    protected string $view = 'filament.pages.notification-hub';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('markAllRead')
                ->label('Tout marquer comme lu')
                ->action(function (): void {
                    /** @var \App\Models\User|null $user */
                    $user = auth()->user();

                    if (!$user) {
                        return;
                    }

                    // Mark all unread Laravel database notifications as read
                    $count = $user->unreadNotifications()->count();
                    $user->unreadNotifications()->update(['read_at' => now()]);

                    app(AuditTrailService::class)->log('notifications_marked_read', null, [
                        'count' => $count,
                    ]);

                    Notification::make()
                        ->title('Alertes marquées comme lues')
                        ->body($count > 0
                            ? $count . ' notification(s) ont été marquées comme lues.'
                            : 'Aucune notification non lue à marquer.')
                        ->success()
                        ->send();
                }),

            Action::make('exportSecurity')
                ->label('Exporter le rapport de sécurité')
                ->action(function (): void {
                    $userId = auth()->id();

                    $report = app(ReportExportService::class)->generate(
                        now()->subDays(30)->startOfDay(),
                        now()->endOfDay(),
                        ['audit' => true, 'revenue' => false, 'expenses' => false, 'payments' => false, 'taxes' => false],
                        'csv',
                        false,
                        $userId,
                    );

                    app(AuditTrailService::class)->log('security_report_exported', null, [
                        'path' => $report['path'],
                        'generated_at' => $report['generatedAt'],
                    ]);

                    $this->redirect($report['downloadUrl'], navigate: false);
                }),
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

    public function sendReminder(string $reference): void
    {
        $invoice = Invoice::query()
            ->with('client')
            ->where('invoice_number', $reference)
            ->first();

        if (!$invoice) {
            Notification::make()->title('Facture introuvable.')->danger()->send();

            return;
        }

        $client = $invoice->client;

        if (!$client || blank($client->email)) {
            Notification::make()
                ->title('Aucun e-mail client')
                ->body('Ce client n\'a pas d\'adresse e-mail enregistrée. Vérifiez sa fiche.')
                ->warning()
                ->send();

            return;
        }

        Mail::to($client->email)->queue(new InvoiceReminderMail($invoice));

        app(AuditTrailService::class)->log('invoice_reminder_sent', $invoice, [
            'reference' => $reference,
            'client_email' => $client->email,
            'balance_due' => (float) $invoice->balance_due,
            'due_date' => $invoice->due_date?->toDateString(),
            'sent_by' => auth()->id(),
        ]);

        Notification::make()
            ->title('Rappel de paiement envoyé')
            ->body('Un rappel a été envoyé à ' . $client->email . ' pour la facture ' . $reference . '.')
            ->success()
            ->send();
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
            : 0;
        $exposure = Schema::hasTable('invoices')
            ? (float) Invoice::query()->where('balance_due', '>', 0)->sum('balance_due')
            : 0;
        $resolved = Schema::hasTable('activity_logs')
            ? ActivityLog::query()->whereDate('created_at', today())->count()
            : 0;
        $efficiency = Schema::hasTable('users')
            ? min(99, max(0, User::query()->where('status', 'active')->count() * 8))
            : 0;

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
            ['reference' => '—', 'client' => 'Aucune facture en retard', 'note' => 'Toutes les échéances sont à jour pour le moment.', 'amount' => 'FCFA 0', 'age' => 'À jour'],
        ];
    }

    protected function placeholderFlaggedPayments(): array
    {
        return [
            ['title' => 'Aucun paiement signalé', 'client' => 'Système', 'note' => 'Les paiements à vérifier apparaîtront ici automatiquement.'],
        ];
    }

    protected function placeholderFeed(): array
    {
        return [
            ['label' => 'Aucune alerte critique récente', 'meta' => 'Système', 'time' => 'En attente d’activité'],
        ];
    }

    protected function placeholderHealth(): array
    {
        return [
            'open_alerts' => '0',
            'exposure' => 'FCFA 0',
            'resolved' => '0',
            'efficiency' => '0%',
        ];
    }

    protected function money(float $amount): string
    {
        return 'FCFA ' . number_format($amount, 0, '.', ' ');
    }
}
