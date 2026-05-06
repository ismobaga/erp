<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\PortalTicket;
use App\Models\Project;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\WhatsappConversation;
use App\Models\WhatsappMessage;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
            'name'      => 'Acme SARL',
            'is_active' => true,
        ]);

        $this->client = Client::create([
            'company_id'   => $this->company->id,
            'type'         => 'company',
            'company_name' => 'BamakoTech',
            'email'        => 'contact@bamakotech.ml',
            'status'       => 'active',
        ]);

        $this->token = $this->client->portal_token;
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

    public function test_portal_index_shows_invoice_list(): void
    {
        $invoice = Invoice::create([
            'company_id'   => $this->company->id,
            'client_id'    => $this->client->id,
            'issue_date'   => now()->toDateString(),
            'status'       => 'sent',
            'total'        => 150000,
            'balance_due'  => 150000,
            'subtotal'     => 150000,
        ]);

        $response = $this->get(route('portal.index', ['token' => $this->token]));

        $response->assertOk();
        $response->assertSeeText($invoice->invoice_number);
    }

    // ── Quotes ─────────────────────────────────────────────────────────────────

    public function test_portal_quotes_page_lists_client_quotes(): void
    {
        $quote = Quote::create([
            'company_id'   => $this->company->id,
            'client_id'    => $this->client->id,
            'quote_number' => 'QT-2026-0001',
            'issue_date'   => now()->toDateString(),
            'status'       => 'sent',
            'total'        => 200000,
            'subtotal'     => 200000,
        ]);

        $response = $this->get(route('portal.quotes', ['token' => $this->token]));

        $response->assertOk();
        $response->assertSeeText('QT-2026-0001');
    }

    public function test_portal_quote_detail_page_shows_line_items(): void
    {
        $quote = Quote::create([
            'company_id'   => $this->company->id,
            'client_id'    => $this->client->id,
            'quote_number' => 'QT-2026-0002',
            'issue_date'   => now()->toDateString(),
            'status'       => 'sent',
            'total'        => 50000,
            'subtotal'     => 50000,
        ]);

        QuoteItem::create([
            'company_id'  => $this->company->id,
            'quote_id'    => $quote->id,
            'description' => 'Développement logiciel',
            'quantity'    => 1,
            'unit_price'  => 50000,
            'line_total'  => 50000,
        ]);

        $response = $this->get(route('portal.quote', ['token' => $this->token, 'quote' => $quote]));

        $response->assertOk();
        $response->assertSeeText('Développement logiciel');
        $response->assertSeeText('QT-2026-0002');
    }

    public function test_portal_quote_approval_converts_to_invoice(): void
    {
        $quote = Quote::create([
            'company_id'   => $this->company->id,
            'client_id'    => $this->client->id,
            'quote_number' => 'QT-2026-0003',
            'issue_date'   => now()->toDateString(),
            'valid_until'  => now()->addDays(30)->toDateString(),
            'status'       => 'sent',
            'total'        => 75000,
            'subtotal'     => 75000,
        ]);

        $response = $this->post(route('portal.quote.approve', ['token' => $this->token, 'quote' => $quote]));

        $response->assertRedirectToRoute('portal.quotes', ['token' => $this->token]);
        $this->assertDatabaseHas('invoices', ['client_id' => $this->client->id]);
        $this->assertEquals('accepted', $quote->fresh()->status);
    }

    public function test_portal_quote_rejection_updates_status(): void
    {
        $quote = Quote::create([
            'company_id'   => $this->company->id,
            'client_id'    => $this->client->id,
            'quote_number' => 'QT-2026-0004',
            'issue_date'   => now()->toDateString(),
            'status'       => 'sent',
            'total'        => 30000,
            'subtotal'     => 30000,
        ]);

        $response = $this->post(route('portal.quote.reject', [
            'token' => $this->token,
            'quote' => $quote,
        ]), ['reason' => 'Budget insuffisant']);

        $response->assertRedirectToRoute('portal.quotes', ['token' => $this->token]);
        $this->assertEquals('rejected', $quote->fresh()->status);
    }

    public function test_portal_cannot_approve_quote_belonging_to_another_client(): void
    {
        $otherClient = Client::create([
            'company_id'   => $this->company->id,
            'type'         => 'individual',
            'contact_name' => 'Other Client',
            'status'       => 'active',
        ]);

        $quote = Quote::create([
            'company_id'   => $this->company->id,
            'client_id'    => $otherClient->id,
            'quote_number' => 'QT-2026-0099',
            'issue_date'   => now()->toDateString(),
            'status'       => 'sent',
            'total'        => 10000,
            'subtotal'     => 10000,
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
            'company_id'  => $this->company->id,
            'client_id'   => $this->client->id,
            'name'        => 'Refonte du site web',
            'status'      => 'in_progress',
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
            'subject'  => 'Problème de connexion',
            'body'     => 'Je ne peux pas me connecter au portail.',
            'priority' => 'urgent',
        ]);

        $response->assertRedirectToRoute('portal.tickets', ['token' => $this->token]);
        $this->assertDatabaseHas('portal_tickets', [
            'client_id' => $this->client->id,
            'subject'   => 'Problème de connexion',
            'priority'  => 'urgent',
            'status'    => 'open',
        ]);
    }

    public function test_portal_ticket_submission_validates_required_fields(): void
    {
        $response = $this->post(route('portal.tickets.submit', ['token' => $this->token]), [
            'subject' => '',
            'body'    => '',
        ]);

        $response->assertSessionHasErrors(['subject', 'body']);
    }

    public function test_portal_tickets_shows_existing_tickets(): void
    {
        PortalTicket::create([
            'company_id' => $this->company->id,
            'client_id'  => $this->client->id,
            'subject'    => 'Question sur ma facture',
            'body'       => 'Pouvez-vous m\'expliquer cette ligne ?',
            'status'     => 'open',
            'priority'   => 'normal',
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
            'company_id'   => $this->company->id,
            'client_id'    => $this->client->id,
            'chat_id'      => '+22370000001@c.us',
            'contact_name' => 'BamakoTech',
            'status'       => 'open',
        ]);

        WhatsappMessage::create([
            'conversation_id' => $conv->id,
            'direction'       => 'inbound',
            'type'            => 'text',
            'body'            => 'Bonjour, avez-vous reçu ma demande ?',
            'sent_at'         => now(),
        ]);

        $response = $this->get(route('portal.conversations', ['token' => $this->token]));

        $response->assertOk();
        $response->assertSeeText('Bonjour, avez-vous reçu ma demande ?');
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

    public function test_portal_renders_in_english_when_locale_is_en(): void
    {
        $this->withSession(['portal_locale' => 'en']);

        $response = $this->get(route('portal.index', ['token' => $this->token]));

        $response->assertOk();
        $response->assertSeeText('Secure client portal');
    }
}
