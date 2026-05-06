<?php

namespace App\Console\Commands;

use App\Models\Client;
use App\Models\Company;
use App\Models\Invoice;
use App\Services\Whatsapp\WhatsappSendService;
use Illuminate\Console\Command;

class SendWhatsappReminders extends Command
{
    protected $signature = 'whatsapp:send-reminders
                            {--days=1 : Number of days before due date to send reminders}';

    protected $description = 'Send WhatsApp payment reminders for invoices due within the specified number of days';

    public function handle(WhatsappSendService $whatsapp): int
    {
        $days  = max(0, (int) $this->option('days'));
        $total = 0;
        $skipped = 0;

        Company::query()->where('is_active', true)->where('whatsapp_enabled', true)->each(
            function (Company $company) use ($whatsapp, $days, &$total, &$skipped): void {
                app()->instance('currentCompany', $company);

                $dueDate = now()->addDays($days)->toDateString();

                Invoice::query()
                    ->where('status', 'sent')
                    ->where('due_date', $dueDate)
                    ->whereHas('client', fn($q) => $q->whereNotNull('phone'))
                    ->with('client')
                    ->each(function (Invoice $invoice) use ($whatsapp, &$total, &$skipped): void {
                        /** @var Client $client */
                        $client = $invoice->client;

                        if (blank($client?->phone)) {
                            $skipped++;
                            $this->line('  Skipped invoice #' . $invoice->invoice_number . ' (no phone)');

                            return;
                        }

                        try {
                            $whatsapp->sendPaymentReminder($invoice);
                            $total++;
                            $this->line('  Sent reminder for invoice #' . $invoice->invoice_number . ' → ' . $client->phone);
                        } catch (\Throwable $e) {
                            $skipped++;
                            $this->error('  Failed for invoice #' . $invoice->invoice_number . ': ' . $e->getMessage());
                        }
                    });
            }
        );

        $this->info(sprintf(
            '%d WhatsApp reminder(s) sent, %d skipped.',
            $total,
            $skipped,
        ));

        return self::SUCCESS;
    }
}
