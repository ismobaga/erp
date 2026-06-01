<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Attachment;
use App\Models\Client;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\PortalTicket;
use App\Models\Project;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\WhatsappConversation;
use App\Models\WhatsappMessage;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ClientPortalExpansionTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;

    protected Client $client;

    protected string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->company = Company::create([
            'name' => 'Acme SARL',
            'is_active' => true,
        ]);
        $this->setUpCompany($this->company);

        $this->client = Client::create([
            'company_id' => $this->company->id,
            'type' => 'company',
            'company_name' => 'BamakoTech',
            'email' => 'contact@bamakotech.ml',
            'status' => 'active',
        ]);

        $this->token = $this->client->portal_token;
    }

    public function test_portal_token_is_hashed_and_encrypted_at_rest(): void
    {
        $fresh = Client::withoutCompanyScope()->findOrFail($this->client->id);

        $this->assertNotSame($this->token, (string) $fresh->getRawOriginal('portal_token'));
        $this->assertSame(hash('sha256', $this->token), $fresh->portal_token_hash);
    }

    // ── Dashboard / index ──────────────────────────────────────────────────────

    public function test_portal_index_returns_200_for_valid_token(): void
    {
        $response = $this->get(route('portal.index', ['token' => $this->token]));

        $response->assertOk();
        $response->assertSeeText('BamakoTech');
    }

    public function test_portal_index_returns_404_for_invalid_token(): void
    {
        $response = $this->get(route('portal.index', ['token' => 'not-a-real-token']));

        $response->assertNotFound();
    }

    public function test_portal_index_rejects_revoked_tokens(): void
    {
        $this->client->revokePortalToken();

        $this->get(route('portal.index', ['token' => $this->token]))
            ->assertNotFound();
    }

    public function test_portal_index_rejects_expired_tokens(): void
    {
        $this->client->forceFill([
            'portal_token_expires_at' => now()->subMinute(),
        ])->save();

        $this->get(route('portal.index', ['token' => $this->token]))
            ->assertNotFound();
    }

    public function test_portal_updates_last_used_timestamp_when_token_is_valid(): void
    {
        $this->assertNull($this->client->fresh()->portal_token_last_used_at);

        $this->get(route('portal.index', ['token' => $this->token]))
            ->assertOk();

        $this->assertNotNull($this->client->fresh()->portal_token_last_used_at);
    }

    public function test_portal_index_shows_invoice_list(): void
    {
        $invoice = Invoice::create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'issue_date' => now()->toDateString(),
            'status' => 'sent',
            'total' => 150000,
            'balance_due' => 150000,
            'subtotal' => 150000,
        ]);

        $response = $this->get(route('portal.index', ['token' => $this->token]));

        $response->assertOk();
        $response->assertSeeText($invoice->invoice_number);
    }

    public function test_portal_index_paginates_invoices_to_twenty_five_records(): void
    {
        for ($i = 1; $i <= 30; $i++) {
            Invoice::create([
                'company_id' => $this->company->id,
                'client_id' => $this->client->id,
                'issue_date' => now()->subDays($i)->toDateString(),
                'status' => 'sent',
                'total' => 1000 + $i,
                'balance_due' => 1000 + $i,
                'subtotal' => 1000 + $i,
            ]);
        }

        $response = $this->get(route('portal.index', ['token' => $this->token]));

        $response->assertOk();
        $invoices = $response->viewData('invoices');
        $this->assertInstanceOf(Paginator::class, $invoices);
        $this->assertCount(25, $invoices->items());
        $this->assertTrue($invoices->hasMorePages());
        $this->assertSame(now()->subDays(1)->toDateString(), $invoices->items()[0]->issue_date?->toDateString());
        $this->assertSame(now()->subDays(25)->toDateString(), $invoices->items()[24]->issue_date?->toDateString());
    }

    public function test_portal_ignores_records_with_mismatched_company_even_if_client_id_matches(): void
    {
        $otherCompany = Company::create([
            'name' => 'Other Tenant',
            'is_active' => true,
        ]);

        $foreignInvoiceId = DB::table('invoices')->insertGetId([
            'invoice_number' => 'INV-FOREIGN-001',
            'company_id' => $otherCompany->id,
            'client_id' => $this->client->id,
            'issue_date' => now()->toDateString(),
            'status' => 'sent',
            'subtotal' => 100,
            'total' => 100,
            'balance_due' => 100,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->get(route('portal.index', ['token' => $this->token]))
            ->assertOk()
            ->assertDontSeeText('INV-FOREIGN-001');

        $this->get(route('portal.invoice', ['token' => $this->token, 'invoice' => $foreignInvoiceId]))
            ->assertNotFound();
    }

    public function test_portal_enforces_tenant_boundaries_on_cross_tenant_records(): void
    {
        $otherCompany = Company::create([
            'name' => 'Foreign Tenant',
            'is_active' => true,
        ]);

        $foreignQuoteId = DB::table('quotes')->insertGetId([
            'company_id' => $otherCompany->id,
            'client_id' => $this->client->id,
            'quote_number' => 'QT-FOREIGN-001',
            'issue_date' => now()->toDateString(),
            'status' => 'sent',
            'subtotal' => 1200,
            'total' => 1200,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('projects')->insert([
            'company_id' => $otherCompany->id,
            'client_id' => $this->client->id,
            'name' => 'Foreign Project',
            'status' => 'in_progress',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('portal_tickets')->insert([
            'company_id' => $otherCompany->id,
            'client_id' => $this->client->id,
            'subject' => 'Foreign Ticket',
            'body' => 'Foreign body',
            'status' => 'open',
            'priority' => 'normal',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $foreignConversationId = DB::table('whatsapp_conversations')->insertGetId([
            'company_id' => $otherCompany->id,
            'client_id' => $this->client->id,
            'chat_id' => 'foreign-chat@c.us',
            'contact_name' => 'Foreign Contact',
            'status' => 'open',
            'last_message_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('whatsapp_messages')->insert([
            'conversation_id' => $foreignConversationId,
            'direction' => 'inbound',
            'type' => 'text',
            'body' => 'Foreign message',
            'sent_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->get(route('portal.quotes', ['token' => $this->token]))
            ->assertOk()
            ->assertDontSeeText('QT-FOREIGN-001');

        $this->get(route('portal.quote', ['token' => $this->token, 'quote' => $foreignQuoteId]))
            ->assertNotFound();

        $this->get(route('portal.projects', ['token' => $this->token]))
            ->assertOk()
            ->assertDontSeeText('Foreign Project');

        $this->get(route('portal.tickets', ['token' => $this->token]))
            ->assertOk()
            ->assertDontSeeText('Foreign Ticket');

        $this->get(route('portal.conversations', ['token' => $this->token]))
            ->assertOk()
            ->assertDontSeeText('Foreign message');
    }

    // ── Quotes ─────────────────────────────────────────────────────────────────

    public function test_portal_quotes_page_lists_client_quotes(): void
    {
        $quote = Quote::create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'quote_number' => 'QT-2026-0001',
            'issue_date' => now()->toDateString(),
            'status' => 'sent',
            'total' => 200000,
            'subtotal' => 200000,
        ]);

        $response = $this->get(route('portal.quotes', ['token' => $this->token]));

        $response->assertOk();
        $response->assertSeeText('QT-2026-0001');
    }

    public function test_portal_quote_detail_page_shows_line_items(): void
    {
        $quote = Quote::create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'quote_number' => 'QT-2026-0002',
            'issue_date' => now()->toDateString(),
            'status' => 'sent',
            'total' => 50000,
            'subtotal' => 50000,
        ]);

        QuoteItem::create([
            'company_id' => $this->company->id,
            'quote_id' => $quote->id,
            'description' => 'Développement logiciel',
            'quantity' => 1,
            'unit_price' => 50000,
            'line_total' => 50000,
        ]);

        $response = $this->get(route('portal.quote', ['token' => $this->token, 'quote' => $quote]));

        $response->assertOk();
        $response->assertSeeText('Développement logiciel');
        $response->assertSeeText('QT-2026-0002');
    }

    public function test_portal_quote_approval_converts_to_invoice(): void
    {
        $quote = Quote::create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'quote_number' => 'QT-2026-0003',
            'issue_date' => now()->toDateString(),
            'valid_until' => now()->addDays(30)->toDateString(),
            'status' => 'sent',
            'total' => 75000,
            'subtotal' => 75000,
        ]);

        $response = $this->post(route('portal.quote.approve', ['token' => $this->token, 'quote' => $quote]));

        $response->assertRedirectToRoute('portal.quotes', ['token' => $this->token]);
        $this->assertDatabaseHas('invoices', ['client_id' => $this->client->id]);
        $this->assertEquals('accepted', $quote->fresh()->status);
    }

    public function test_portal_quote_rejection_updates_status(): void
    {
        $quote = Quote::create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'quote_number' => 'QT-2026-0004',
            'issue_date' => now()->toDateString(),
            'status' => 'sent',
            'total' => 30000,
            'subtotal' => 30000,
        ]);
        $reason = 'Budget insuffisant';

        $response = $this->post(route('portal.quote.reject', [
            'token' => $this->token,
            'quote' => $quote,
        ]), ['reason' => $reason]);

        $response->assertRedirectToRoute('portal.quotes', ['token' => $this->token]);
        $this->assertEquals('rejected', $quote->fresh()->status);
        $log = ActivityLog::query()->where('action', 'portal_quote_rejected')->first();
        $this->assertNotNull($log);
        $this->assertSame($reason, data_get($log->meta_json, 'reason'));
    }

    public function test_portal_quote_rejection_validates_reason_max_length(): void
    {
        $quote = Quote::create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'quote_number' => 'QT-2026-0005',
            'issue_date' => now()->toDateString(),
            'status' => 'sent',
            'total' => 12000,
            'subtotal' => 12000,
        ]);

        $response = $this->post(route('portal.quote.reject', [
            'token' => $this->token,
            'quote' => $quote,
        ]), ['reason' => str_repeat('a', 1001)]);

        $response->assertSessionHasErrors(['reason']);
        $this->assertEquals('sent', $quote->fresh()->status);
        $this->assertDatabaseMissing('activity_logs', ['action' => 'portal_quote_rejected']);
    }

    public function test_portal_cannot_approve_quote_belonging_to_another_client(): void
    {
        $otherClient = Client::create([
            'company_id' => $this->company->id,
            'type' => 'individual',
            'contact_name' => 'Other Client',
            'status' => 'active',
        ]);

        $quote = Quote::create([
            'company_id' => $this->company->id,
            'client_id' => $otherClient->id,
            'quote_number' => 'QT-2026-0099',
            'issue_date' => now()->toDateString(),
            'status' => 'sent',
            'total' => 10000,
            'subtotal' => 10000,
        ]);

        $response = $this->post(route('portal.quote.approve', ['token' => $this->token, 'quote' => $quote]));

        $response->assertNotFound();
    }

    // ── Documents ──────────────────────────────────────────────────────────────

    public function test_portal_documents_page_loads_successfully(): void
    {
        $response = $this->get(route('portal.documents', ['token' => $this->token]));

        $response->assertOk();
    }

    // ── Projects ───────────────────────────────────────────────────────────────

    public function test_portal_projects_page_lists_client_projects(): void
    {
        $project = Project::create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'name' => 'Refonte du site web',
            'status' => 'in_progress',
            'description' => 'Refonte complète de l\'infrastructure.',
        ]);

        $response = $this->get(route('portal.projects', ['token' => $this->token]));

        $response->assertOk();
        $response->assertSeeText('Refonte du site web');
    }

    // ── Support Tickets ────────────────────────────────────────────────────────

    public function test_portal_tickets_page_loads_successfully(): void
    {
        $response = $this->get(route('portal.tickets', ['token' => $this->token]));

        $response->assertOk();
    }

    public function test_portal_ticket_submission_creates_ticket(): void
    {
        $response = $this->post(route('portal.tickets.submit', ['token' => $this->token]), [
            'subject' => 'Problème de connexion',
            'body' => 'Je ne peux pas me connecter au portail.',
            'priority' => 'urgent',
        ]);

        $response->assertRedirectToRoute('portal.tickets', ['token' => $this->token]);
        $this->assertDatabaseHas('portal_tickets', [
            'client_id' => $this->client->id,
            'subject' => 'Problème de connexion',
            'priority' => 'urgent',
            'status' => 'open',
        ]);
    }

    public function test_portal_ticket_submission_validates_required_fields(): void
    {
        $response = $this->post(route('portal.tickets.submit', ['token' => $this->token]), [
            'subject' => '',
            'body' => '',
        ]);

        $response->assertSessionHasErrors(['subject', 'body']);
    }

    public function test_portal_tickets_shows_existing_tickets(): void
    {
        PortalTicket::create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'subject' => 'Question sur ma facture',
            'body' => 'Pouvez-vous m\'expliquer cette ligne ?',
            'status' => 'open',
            'priority' => 'normal',
        ]);

        $response = $this->get(route('portal.tickets', ['token' => $this->token]));

        $response->assertOk();
        $response->assertSeeText('Question sur ma facture');
    }

    // ── Activity History ───────────────────────────────────────────────────────

    public function test_portal_activity_page_loads_successfully(): void
    {
        $response = $this->get(route('portal.activity', ['token' => $this->token]));

        $response->assertOk();
    }

    // ── WhatsApp Conversations ─────────────────────────────────────────────────

    public function test_portal_conversations_page_shows_linked_conversations(): void
    {
        $conv = WhatsappConversation::create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'chat_id' => '+22370000001@c.us',
            'contact_name' => 'BamakoTech',
            'status' => 'open',
        ]);

        WhatsappMessage::create([
            'conversation_id' => $conv->id,
            'direction' => 'inbound',
            'type' => 'text',
            'body' => 'Bonjour, avez-vous reçu ma demande ?',
            'sent_at' => now(),
        ]);

        $response = $this->get(route('portal.conversations', ['token' => $this->token]));

        $response->assertOk();
        $response->assertSeeText('Bonjour, avez-vous reçu ma demande ?');
    }

    public function test_portal_lists_use_pagination_on_quotes_projects_tickets_and_conversations(): void
    {
        for ($i = 1; $i <= 30; $i++) {
            Quote::create([
                'company_id' => $this->company->id,
                'client_id' => $this->client->id,
                'quote_number' => 'QT-PAG-'.$i,
                'issue_date' => now()->subDays($i)->toDateString(),
                'status' => 'sent',
                'total' => 1000 + $i,
                'subtotal' => 1000 + $i,
            ]);

            Project::create([
                'company_id' => $this->company->id,
                'client_id' => $this->client->id,
                'name' => 'Projet pagination '.$i,
                'status' => 'in_progress',
            ]);

            PortalTicket::create([
                'company_id' => $this->company->id,
                'client_id' => $this->client->id,
                'subject' => 'Ticket pagination '.$i,
                'body' => 'Message '.$i,
                'status' => 'open',
                'priority' => 'normal',
            ]);

            $conversation = WhatsappConversation::create([
                'company_id' => $this->company->id,
                'client_id' => $this->client->id,
                'chat_id' => sprintf('+2237%04d@c.us', $i),
                'contact_name' => 'Client '.$i,
                'status' => 'open',
                'last_message_at' => now()->subMinutes($i),
            ]);

            // Create one more message than the cap to verify truncation at 25.
            for ($j = 1; $j <= 26; $j++) {
                WhatsappMessage::create([
                    'conversation_id' => $conversation->id,
                    'direction' => 'inbound',
                    'type' => 'text',
                    'body' => "Conv {$i} message {$j}",
                    'sent_at' => now()->subSeconds($j),
                ]);
            }
        }

        $quotesResponse = $this->get(route('portal.quotes', ['token' => $this->token]));
        $quotes = $quotesResponse->viewData('quotes');
        $this->assertInstanceOf(Paginator::class, $quotes);
        $this->assertCount(25, $quotes->items());
        $this->assertTrue($quotes->hasMorePages());

        $projectsResponse = $this->get(route('portal.projects', ['token' => $this->token]));
        $projects = $projectsResponse->viewData('projects');
        $this->assertInstanceOf(Paginator::class, $projects);
        $this->assertCount(25, $projects->items());
        $this->assertTrue($projects->hasMorePages());

        $ticketsResponse = $this->get(route('portal.tickets', ['token' => $this->token]));
        $tickets = $ticketsResponse->viewData('tickets');
        $this->assertInstanceOf(Paginator::class, $tickets);
        $this->assertCount(25, $tickets->items());
        $this->assertTrue($tickets->hasMorePages());

        $conversationsResponse = $this->get(route('portal.conversations', ['token' => $this->token]));
        $conversations = $conversationsResponse->viewData('conversations');
        $this->assertInstanceOf(Paginator::class, $conversations);
        $this->assertCount(25, $conversations->items());
        $this->assertTrue($conversations->hasMorePages());
        $this->assertSame(25, $conversations->items()[0]->messages->count());
        $this->assertSame(25, $conversations->items()[24]->messages->count());
    }

    public function test_portal_documents_lists_are_paginated_per_document_category(): void
    {
        $project = Project::create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'name' => 'Projet docs',
            'status' => 'in_progress',
        ]);

        $invoice = Invoice::create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'issue_date' => now()->toDateString(),
            'status' => 'sent',
            'total' => 10000,
            'balance_due' => 10000,
            'subtotal' => 10000,
        ]);

        for ($i = 1; $i <= 30; $i++) {
            Attachment::create([
                'company_id' => $this->company->id,
                'attachable_type' => Client::class,
                'attachable_id' => $this->client->id,
                'file_name' => "client-doc-{$i}.pdf",
                'file_path' => "docs/client-doc-{$i}.pdf",
            ]);

            Attachment::create([
                'company_id' => $this->company->id,
                'attachable_type' => Project::class,
                'attachable_id' => $project->id,
                'file_name' => "project-doc-{$i}.pdf",
                'file_path' => "docs/project-doc-{$i}.pdf",
            ]);

            Attachment::create([
                'company_id' => $this->company->id,
                'attachable_type' => Invoice::class,
                'attachable_id' => $invoice->id,
                'file_name' => "invoice-doc-{$i}.pdf",
                'file_path' => "docs/invoice-doc-{$i}.pdf",
            ]);
        }

        $response = $this->get(route('portal.documents', ['token' => $this->token]));

        $response->assertOk();

        $clientDocs = $response->viewData('clientDocs');
        $this->assertInstanceOf(Paginator::class, $clientDocs);
        $this->assertCount(25, $clientDocs->items());
        $this->assertTrue($clientDocs->hasMorePages());

        $projectDocs = $response->viewData('projectDocs');
        $this->assertInstanceOf(Paginator::class, $projectDocs);
        $this->assertCount(25, $projectDocs->items());
        $this->assertTrue($projectDocs->hasMorePages());

        $invoiceDocs = $response->viewData('invoiceDocs');
        $this->assertInstanceOf(Paginator::class, $invoiceDocs);
        $this->assertCount(25, $invoiceDocs->items());
        $this->assertTrue($invoiceDocs->hasMorePages());
    }

    // ── Language Switcher ──────────────────────────────────────────────────────

    public function test_language_switcher_stores_locale_in_session(): void
    {
        $response = $this->post(route('portal.language', ['token' => $this->token]), [
            'locale' => 'en',
        ]);

        $response->assertRedirect();
        $this->assertEquals('en', session('portal_locale'));
    }

    public function test_language_switcher_does_not_redirect_to_external_referer(): void
    {
        $response = $this->withHeaders(['Referer' => 'https://evil.com/phishing'])
            ->post(route('portal.language', ['token' => $this->token]), ['locale' => 'en']);

        // Must redirect to the portal index, not to the external URL.
        $response->assertRedirect(route('portal.index', ['token' => $this->token]));
        $this->assertStringNotContainsString('evil.com', $response->headers->get('Location', ''));
    }

    public function test_language_switcher_follows_same_origin_referer(): void
    {
        $portalUrl = route('portal.quotes', ['token' => $this->token]);

        $response = $this->withHeaders(['Referer' => $portalUrl])
            ->post(route('portal.language', ['token' => $this->token]), ['locale' => 'fr']);

        $response->assertRedirect($portalUrl);
    }

    public function test_portal_renders_in_english_when_locale_is_en(): void
    {
        $this->withSession(['portal_locale' => 'en']);

        $response = $this->get(route('portal.index', ['token' => $this->token]));

        $response->assertOk();
        $response->assertSeeText('Secure client portal');
    }

    // ── resolveCompany hardening ───────────────────────────────────────────────

    public function test_portal_returns_404_when_client_has_no_company_id(): void
    {
        // Force company_id to null, bypassing the FK constraint (e.g. migration gap).
        DB::table('clients')
            ->where('id', $this->client->id)
            ->update(['company_id' => null]);

        $this->get(route('portal.index', ['token' => $this->token]))
            ->assertNotFound();
    }
}
