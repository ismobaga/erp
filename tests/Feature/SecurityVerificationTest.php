<?php

namespace Tests\Feature;

use App\Models\Attachment;
use App\Models\Client;
use App\Models\Company;
use App\Models\CreditNote;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Quote;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SecurityVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_quote_rejects_client_from_another_company(): void
    {
        $companyA = Company::create(['name' => 'Company A', 'currency' => 'FCFA', 'is_active' => true]);
        $companyB = Company::create(['name' => 'Company B', 'currency' => 'FCFA', 'is_active' => true]);

        $this->setUpCompany($companyA);
        Client::create(['type' => 'company', 'company_name' => 'Client A', 'status' => 'active']);

        $this->setUpCompany($companyB);
        $foreignClient = Client::create(['type' => 'company', 'company_name' => 'Client B', 'status' => 'active']);

        $this->setUpCompany($companyA);

        $this->expectException(ValidationException::class);

        Quote::create([
            'client_id' => $foreignClient->id,
            'quote_number' => 'QT-CROSS-001',
            'issue_date' => now()->toDateString(),
            'status' => 'draft',
            'total' => 100,
            'subtotal' => 100,
        ]);
    }

    public function test_credit_note_rejects_invoice_from_another_company(): void
    {
        $companyA = Company::create(['name' => 'Company A', 'currency' => 'FCFA', 'is_active' => true]);
        $companyB = Company::create(['name' => 'Company B', 'currency' => 'FCFA', 'is_active' => true]);

        $this->setUpCompany($companyB);

        $clientB = Client::create(['type' => 'company', 'company_name' => 'Client B', 'status' => 'active']);
        $invoiceB = Invoice::create([
            'client_id' => $clientB->id,
            'issue_date' => now()->toDateString(),
            'status' => 'sent',
            'total' => 200,
            'balance_due' => 200,
        ]);

        $this->setUpCompany($companyA);

        $this->expectException(ValidationException::class);

        CreditNote::create([
            'invoice_id' => $invoiceB->id,
            'credit_number' => 'CN-CROSS-001',
            'issue_date' => now()->toDateString(),
            'amount' => 50,
            'reason' => 'Cross-company validation check',
            'status' => 'issued',
        ]);
    }

    public function test_attachments_cannot_be_attached_to_records_from_another_company(): void
    {
        $companyA = Company::create(['name' => 'Company A', 'currency' => 'FCFA', 'is_active' => true]);
        $companyB = Company::create(['name' => 'Company B', 'currency' => 'FCFA', 'is_active' => true]);

        $this->setUpCompany($companyB);
        $foreignClient = Client::create(['type' => 'company', 'company_name' => 'Client B', 'status' => 'active']);

        $this->setUpCompany($companyA);

        $this->expectException(ValidationException::class);

        Attachment::create([
            'attachable_type' => Client::class,
            'attachable_id' => $foreignClient->id,
            'file_name' => 'cross-company-proof.pdf',
            'file_path' => 'attachments/security/cross-company-proof.pdf',
            'mime_type' => 'application/pdf',
        ]);
    }

    public function test_non_invoice_pdf_routes_are_not_accessible_across_companies(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $companyA = Company::create(['name' => 'Company A', 'currency' => 'FCFA', 'is_active' => true]);
        $companyB = Company::create(['name' => 'Company B', 'currency' => 'FCFA', 'is_active' => true]);

        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole('Finance');

        $this->setUpCompany($companyB);

        $clientB = Client::create(['type' => 'company', 'company_name' => 'Client B', 'status' => 'active']);
        $quoteB = Quote::create([
            'client_id' => $clientB->id,
            'quote_number' => 'QT-B-0001',
            'issue_date' => now()->toDateString(),
            'status' => 'sent',
            'total' => 100,
            'subtotal' => 100,
        ]);

        $invoiceB = Invoice::create([
            'client_id' => $clientB->id,
            'issue_date' => now()->toDateString(),
            'status' => 'sent',
            'total' => 250,
            'balance_due' => 250,
        ]);

        $paymentB = Payment::create([
            'invoice_id' => $invoiceB->id,
            'client_id' => $clientB->id,
            'payment_date' => now()->toDateString(),
            'amount' => 50,
            'payment_method' => 'cash',
        ]);

        $expenseB = Expense::create([
            'category' => 'operations',
            'title' => 'Cross-company expense',
            'amount' => 25,
            'expense_date' => now()->toDateString(),
        ]);

        $creditNoteB = CreditNote::create([
            'invoice_id' => $invoiceB->id,
            'credit_number' => 'CN-B-0001',
            'issue_date' => now()->toDateString(),
            'amount' => 10,
            'reason' => 'Adjustment',
            'status' => 'issued',
        ]);

        $this->setUpCompany($companyA);

        $this->actingAs($user)->get(route('quotes.pdf', $quoteB))->assertNotFound();
        $this->actingAs($user)->get(route('payments.pdf', $paymentB))->assertNotFound();
        $this->actingAs($user)->get(route('expenses.pdf', $expenseB))->assertNotFound();
        $this->actingAs($user)->get(route('credit-notes.pdf', $creditNoteB))->assertNotFound();
    }

    public function test_cumulative_overpayment_is_blocked_after_an_initial_payment(): void
    {
        $client = Client::create(['type' => 'company', 'company_name' => 'Overpay Client', 'status' => 'active']);

        $invoice = Invoice::create([
            'client_id' => $client->id,
            'issue_date' => now()->toDateString(),
            'status' => 'sent',
            'total' => 100,
            'balance_due' => 100,
        ]);

        Payment::create([
            'invoice_id' => $invoice->id,
            'client_id' => $client->id,
            'payment_date' => now()->toDateString(),
            'amount' => 80,
            'payment_method' => 'cash',
        ]);

        $this->expectException(ValidationException::class);

        Payment::create([
            'invoice_id' => $invoice->id,
            'client_id' => $client->id,
            'payment_date' => now()->toDateString(),
            'amount' => 30,
            'payment_method' => 'cash',
        ]);
    }

    public function test_required_indexes_and_unique_constraints_exist(): void
    {
        $this->assertTrue(Schema::hasIndex('invoices', ['company_id', 'invoice_number'], 'unique'));
        $this->assertTrue(Schema::hasIndex('sequences', ['company_id', 'key', 'period'], 'unique'));
        $this->assertTrue(Schema::hasIndex('journal_entries', ['source_type', 'source_id'], 'unique'));

        $this->assertTrue(Schema::hasIndex('invoices', ['company_id', 'client_id']));
        $this->assertTrue(Schema::hasIndex('invoices', ['company_id', 'status']));
        $this->assertTrue(Schema::hasIndex('clients', ['portal_token_hash']));
        $this->assertTrue(Schema::hasIndex('recurring_invoices', ['company_id', 'is_active', 'next_due_date']));
        $this->assertTrue(Schema::hasIndex('payments', ['invoice_id']));
    }
}
