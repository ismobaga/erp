<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Company;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuotePdfTemplateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_quote_pdf_uses_compact_mode_for_short_documents(): void
    {
        [$user, $client] = $this->setUpFinanceContext();

        $quote = $this->createQuote($client, [
            'quote_number' => 'QT-COMPACT-001',
            'status' => 'sent',
            'notes' => 'Short note',
        ]);

        $response = $this->actingAs($user)->get(route('quotes.pdf', $quote));

        $response->assertOk();
        $response->assertSee('<body class="compact">', false);
    }

    public function test_quote_pdf_renders_watermark_for_accepted_quotes(): void
    {
        [$user, $client] = $this->setUpFinanceContext();

        $quote = $this->createQuote($client, [
            'quote_number' => 'QT-WATERMARK-001',
            'status' => 'accepted',
        ]);

        $response = $this->actingAs($user)->get(route('quotes.pdf', $quote));

        $response->assertOk();
        $response->assertSee('ACCEPTED');
        $response->assertSee('<div class="doc-watermark" aria-hidden="true">', false);
    }

    private function setUpFinanceContext(): array
    {
        $this->setUpCompany(Company::create([
            'name' => 'CROMMIX MALI S.A.',
            'currency' => 'FCFA',
            'is_active' => true,
            'advanced_options' => ['quotes' => true],
        ]));

        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole('Finance');

        $client = Client::create([
            'type' => 'company',
            'company_name' => 'Quote Client',
            'status' => 'active',
        ]);

        return [$user, $client];
    }

    private function createQuote(Client $client, array $attributes = []): Quote
    {
        $quote = Quote::create(array_merge([
            'quote_number' => 'QT-DEFAULT-001',
            'client_id' => $client->id,
            'issue_date' => now()->toDateString(),
            'valid_until' => now()->addDays(15)->toDateString(),
            'status' => 'sent',
            'notes' => 'Short note',
            'subtotal' => 100000,
            'tax_total' => 0,
            'total' => 100000,
        ], $attributes));

        QuoteItem::create([
            'quote_id' => $quote->id,
            'description' => 'Quote service',
            'quantity' => 1,
            'unit_price' => 100000,
            'line_total' => 100000,
        ]);

        return $quote;
    }
}
