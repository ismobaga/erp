<?php

namespace Tests\Feature;

use App\Mail\InvoiceReminderMail;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class InvoiceDueReminderCommandTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Client $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->user = User::factory()->create(['status' => 'active']);

        $this->client = Client::create([
            'type' => 'company',
            'company_name' => 'Reminder Target SARL',
            'email' => 'finance@target.ci',
            'status' => 'active',
        ]);
    }

    public function test_command_queues_reminder_for_invoice_due_tomorrow(): void
    {
        Mail::fake();

        $invoice = Invoice::create([
            'client_id' => $this->client->id,
            'issue_date' => now()->subDays(10)->toDateString(),
            'due_date' => now()->addDay()->toDateString(),
            'status' => 'sent',
            'balance_due' => 500.00,
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ]);

        $this->artisan('invoices:send-due-reminders')
            ->expectsOutputToContain('1 reminder(s) queued')
            ->assertExitCode(0);

        Mail::assertQueued(InvoiceReminderMail::class, fn($mail) => $mail->hasTo($this->client->email));
    }

    public function test_command_skips_invoices_not_due_tomorrow(): void
    {
        Mail::fake();

        Invoice::create([
            'client_id' => $this->client->id,
            'issue_date' => now()->subDays(20)->toDateString(),
            'due_date' => now()->addDays(5)->toDateString(),
            'status' => 'sent',
            'balance_due' => 200.00,
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ]);

        $this->artisan('invoices:send-due-reminders')
            ->expectsOutputToContain('0 reminder(s)')
            ->assertExitCode(0);

        Mail::assertNothingQueued();
    }

    public function test_command_skips_paid_invoices(): void
    {
        Mail::fake();

        Invoice::create([
            'client_id' => $this->client->id,
            'issue_date' => now()->subDays(10)->toDateString(),
            'due_date' => now()->addDay()->toDateString(),
            'status' => 'paid',
            'balance_due' => 0.00,
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ]);

        $this->artisan('invoices:send-due-reminders')
            ->expectsOutputToContain('0 reminder(s)')
            ->assertExitCode(0);

        Mail::assertNothingQueued();
    }

    public function test_command_skips_clients_with_no_email(): void
    {
        Mail::fake();

        $noEmailClient = Client::create([
            'type' => 'individual',
            'contact_name' => 'Sans Email',
            'status' => 'active',
        ]);

        Invoice::create([
            'client_id' => $noEmailClient->id,
            'issue_date' => now()->subDays(10)->toDateString(),
            'due_date' => now()->addDay()->toDateString(),
            'status' => 'sent',
            'balance_due' => 150.00,
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ]);

        $this->artisan('invoices:send-due-reminders')
            ->expectsOutputToContain('1 skipped')
            ->assertExitCode(0);

        Mail::assertNothingQueued();
    }

    public function test_command_logs_audit_for_each_reminder_sent(): void
    {
        Mail::fake();

        $invoice = Invoice::create([
            'client_id' => $this->client->id,
            'issue_date' => now()->subDays(10)->toDateString(),
            'due_date' => now()->addDay()->toDateString(),
            'status' => 'sent',
            'balance_due' => 750.00,
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ]);

        $this->artisan('invoices:send-due-reminders')->assertExitCode(0);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'invoice_reminder_sent',
            'subject_type' => Invoice::class,
            'subject_id' => $invoice->id,
        ]);
    }
}
