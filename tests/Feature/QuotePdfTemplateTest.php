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

    public function test_quote_pdf_uses_compact_mode_and_renders_watermark_for_accepted_quotes(): void
    {
        $this->setUpCompany(Company::create([
            'name' => 'CROMMIX MALI S.A.',
            'currency' => 'FCFA',
            'is_active' => true,
        ]));

        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole('Finance');

        $client = Client::create([
            'type' => 'company',
            'company_name' => 'Client devis',
            'status' => 'active',
        ]);

        $quote = Quote::create([
            'quote_number' => 'QT-COMPACT-001',
            'client_id' => $client->id,
            'issue_date' => now()->toDateString(),
            'valid_until' => now()->addDays(15)->toDateString(),
            'status' => 'accepted',
            'notes' => 'Note courte',
            'subtotal' => 100000,
            'tax_total' => 0,
            'total' => 100000,
        ]);

        QuoteItem::create([
            'quote_id' => $quote->id,
            'description' => 'Prestation devis',
            'quantity' => 1,
            'unit_price' => 100000,
            'line_total' => 100000,
        ]);

        $response = $this->actingAs($user)->get(route('quotes.pdf', $quote));

        $response->assertOk();
        $response->assertSee('body class="compact"', false);
        $response->assertSee('ACCEPTED');
        $response->assertDontSee('.doc-watermark { display: none; }');
    }
}
