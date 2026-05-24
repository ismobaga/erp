<?php

namespace Tests\Feature;

use App\Actions\SendInvoiceReminderAction;
use App\Actions\SendInvoiceWhatsappAction;
use App\Actions\SendInvoiceWhatsappReminderAction;
use App\Mail\InvoiceReminderMail;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\User;
use App\Models\WhatsappMessageLog;
use App\Services\Whatsapp\WhatsappSendService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SendInvoiceActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_send_invoice_reminder_action_queues_mail_and_logs_audit_entry(): void
    {
        Mail::fake();

        $user = User::factory()->create(['status' => 'active']);
        $this->actingAs($user);

        $client = Client::create([
            'type' => 'company',
            'company_name' => 'Reminder Client',
            'email' => 'billing@example.test',
            'phone' => '0700000000',
            'status' => 'active',
        ]);

        $invoice = Invoice::create([
            'client_id' => $client->id,
            'issue_date' => now()->subDays(10)->toDateString(),
            'due_date' => now()->addDay()->toDateString(),
            'status' => 'sent',
            'balance_due' => 500.00,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        app(SendInvoiceReminderAction::class)->execute($invoice);

        Mail::assertQueued(InvoiceReminderMail::class, fn (InvoiceReminderMail $mail): bool => $mail->hasTo($client->email));

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'invoice_reminder_sent',
            'subject_type' => Invoice::class,
            'subject_id' => $invoice->id,
        ]);
    }

    public function test_send_invoice_reminder_action_skips_when_client_has_no_email(): void
    {
        Mail::fake();

        $user = User::factory()->create(['status' => 'active']);
        $this->actingAs($user);

        $client = Client::create([
            'type' => 'individual',
            'contact_name' => 'No Email',
            'phone' => '0700000001',
            'status' => 'active',
        ]);

        $invoice = Invoice::create([
            'client_id' => $client->id,
            'issue_date' => now()->subDays(10)->toDateString(),
            'due_date' => now()->addDay()->toDateString(),
            'status' => 'sent',
            'balance_due' => 120.00,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        app(SendInvoiceReminderAction::class)->execute($invoice);

        Mail::assertNothingQueued();

        $this->assertDatabaseMissing('activity_logs', [
            'action' => 'invoice_reminder_sent',
            'subject_type' => Invoice::class,
            'subject_id' => $invoice->id,
        ]);
    }

    public function test_send_invoice_whatsapp_action_uses_service(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $this->actingAs($user);

        $client = Client::create([
            'type' => 'company',
            'company_name' => 'WhatsApp Client',
            'phone' => '0700000002',
            'status' => 'active',
        ]);

        $invoice = Invoice::create([
            'client_id' => $client->id,
            'issue_date' => now()->subDays(10)->toDateString(),
            'due_date' => now()->addDay()->toDateString(),
            'status' => 'sent',
            'balance_due' => 250.00,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $log = new WhatsappMessageLog();
        $log->status = 'sent';

        $service = $this->createMock(WhatsappSendService::class);
        $service->expects($this->once())
            ->method('sendInvoice')
            ->with($invoice)
            ->willReturn($log);

        app()->instance(WhatsappSendService::class, $service);

        app(SendInvoiceWhatsappAction::class)->execute($invoice);
    }

    public function test_send_invoice_whatsapp_reminder_action_uses_service(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $this->actingAs($user);

        $client = Client::create([
            'type' => 'company',
            'company_name' => 'WhatsApp Reminder Client',
            'phone' => '0700000003',
            'status' => 'active',
        ]);

        $invoice = Invoice::create([
            'client_id' => $client->id,
            'issue_date' => now()->subDays(10)->toDateString(),
            'due_date' => now()->addDay()->toDateString(),
            'status' => 'overdue',
            'balance_due' => 450.00,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $log = new WhatsappMessageLog();
        $log->status = 'failed';
        $log->error_message = 'mocked failure';

        $service = $this->createMock(WhatsappSendService::class);
        $service->expects($this->once())
            ->method('sendPaymentReminder')
            ->with($invoice)
            ->willReturn($log);

        app()->instance(WhatsappSendService::class, $service);

        app(SendInvoiceWhatsappReminderAction::class)->execute($invoice);
    }
}
