<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\RecurringInvoice;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GenerateRecurringInvoicesCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_does_not_create_duplicate_invoice_for_same_recurring_due_date(): void
    {
        $issueDate = '2026-01-15';
        app('currentCompany')->update([
            'advanced_options' => ['recurring_invoices' => true],
        ]);

        $client = Client::create([
            'type' => 'company',
            'company_name' => 'Recurring Client',
            'status' => 'active',
        ]);

        $template = RecurringInvoice::create([
            'client_id' => $client->id,
            'frequency' => 'monthly',
            'start_date' => $issueDate,
            'next_due_date' => $issueDate,
            'net_days' => 30,
            'description' => 'Monthly support',
            'amount' => 100000,
            'is_active' => true,
        ]);

        $this->artisan('invoices:generate-recurring', ['--date' => $issueDate])->assertExitCode(0);
        $this->artisan('invoices:generate-recurring', ['--date' => $issueDate])->assertExitCode(0);

        $this->assertDatabaseCount('invoices', 1);
        $invoice = Invoice::query()->firstOrFail();

        $this->assertSame($client->id, $invoice->client_id);
        $this->assertSame($template->id, $invoice->recurring_invoice_id);
        $this->assertSame($issueDate, $invoice->issue_date?->toDateString());

        $template->refresh();
        $this->assertSame('2026-02-15', $template->next_due_date?->toDateString());
    }

    public function test_unique_constraint_prevents_duplicate_recurring_invoice_issue_date_per_company(): void
    {
        $issueDate = '2026-01-15';
        app('currentCompany')->update([
            'advanced_options' => ['recurring_invoices' => true],
        ]);

        $client = Client::create([
            'type' => 'company',
            'company_name' => 'Unique Constraint Client',
            'status' => 'active',
        ]);

        $template = RecurringInvoice::create([
            'client_id' => $client->id,
            'frequency' => 'monthly',
            'start_date' => $issueDate,
            'next_due_date' => $issueDate,
            'net_days' => 15,
            'description' => 'Retainer',
            'amount' => 250000,
            'is_active' => true,
        ]);

        Invoice::create([
            'client_id' => $client->id,
            'recurring_invoice_id' => $template->id,
            'issue_date' => $issueDate,
            'due_date' => '2026-01-30',
            'status' => 'draft',
        ]);

        $this->expectException(QueryException::class);

        Invoice::create([
            'client_id' => $client->id,
            'recurring_invoice_id' => $template->id,
            'issue_date' => $issueDate,
            'due_date' => '2026-01-30',
            'status' => 'draft',
        ]);
    }

    public function test_command_skips_companies_without_recurring_invoice_feature_enabled(): void
    {
        $issueDate = '2026-01-15';

        $company = Company::create([
            'name' => 'Recurring Feature Company',
            'currency' => 'FCFA',
            'is_active' => true,
        ]);
        $this->setUpCompany($company);

        $client = Client::create([
            'type' => 'company',
            'company_name' => 'Recurring Feature Client',
            'status' => 'active',
        ]);

        RecurringInvoice::create([
            'client_id' => $client->id,
            'frequency' => 'monthly',
            'start_date' => $issueDate,
            'next_due_date' => $issueDate,
            'net_days' => 30,
            'description' => 'Monthly support',
            'amount' => 100000,
            'is_active' => true,
        ]);

        $this->artisan('invoices:generate-recurring', ['--date' => $issueDate])->assertExitCode(0);

        $this->assertDatabaseCount('invoices', 0);

        $company->update([
            'advanced_options' => ['recurring_invoices' => true],
        ]);

        $this->artisan('invoices:generate-recurring', ['--date' => $issueDate])->assertExitCode(0);

        $this->assertDatabaseCount('invoices', 1);
    }
}
