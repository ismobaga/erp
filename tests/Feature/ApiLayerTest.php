<?php

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\Client;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class ApiLayerTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    private User $user;

    private string $publicToken;

    private string $privateToken;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create([
            'name' => 'API Company',
            'currency' => 'FCFA',
            'is_active' => true,
        ]);
        $this->setUpCompany($this->company);

        $this->user = User::factory()->create(['status' => 'active']);
        $this->user->companies()->attach($this->company->id, ['role' => 'admin']);

        $this->publicToken = ApiToken::issue(
            user: $this->user,
            company: $this->company,
            name: 'Public API token',
            scope: 'public',
        )['plainTextToken'];

        $this->privateToken = ApiToken::issue(
            user: $this->user,
            company: $this->company,
            name: 'Private API token',
            scope: 'private',
        )['plainTextToken'];
    }

    public function test_openapi_documentation_endpoint_is_available(): void
    {
        $response = $this->getJson('/api/docs/openapi.json');

        $response->assertOk();
        $response->assertJsonPath('openapi', '3.0.3');
        $response->assertJsonPath('info.title', 'ERP Integration API');
    }

    public function test_private_endpoints_require_api_token_authentication(): void
    {
        $this->getJson('/api/v1/private/clients')
            ->assertStatus(401)
            ->assertJsonPath('message', 'API token is required.');
    }

    public function test_public_token_can_access_public_endpoint_but_not_private_scope(): void
    {
        $this->withToken($this->publicToken)
            ->getJson('/api/v1/public/company')
            ->assertOk()
            ->assertJsonPath('data.name', 'API Company');

        $this->withToken($this->publicToken)
            ->getJson('/api/v1/private/clients')
            ->assertStatus(403);
    }

    public function test_private_token_can_access_private_endpoints_and_writes_audit_log(): void
    {
        Client::create([
            'type' => 'company',
            'company_name' => 'Private Client',
            'status' => 'active',
        ]);

        $this->withToken($this->privateToken)
            ->getJson('/api/v1/private/clients')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'api_request',
        ]);
    }

    public function test_api_webhook_endpoint_stores_event_and_audits_it(): void
    {
        $response = $this->withToken($this->publicToken)->postJson('/api/v1/public/webhooks/shopify', [
            'event' => 'order.created',
            'payload' => [
                'order_id' => 'ORD-001',
                'total' => 10000,
            ],
        ]);

        $response->assertStatus(202);
        $response->assertJsonPath('status', 'accepted');

        $this->assertDatabaseHas('api_webhook_events', [
            'source' => 'shopify',
            'event' => 'order.created',
            'company_id' => $this->company->id,
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'api_webhook_received',
        ]);
    }

    public function test_api_public_rate_limiter_applies_limits(): void
    {
        RateLimiter::for('api-public', function (Request $request): Limit {
            $identifier = (string) optional($request->attributes->get('apiToken'))->id;

            return Limit::perMinute(2)->by('test|'.$identifier);
        });

        $this->withToken($this->publicToken)->getJson('/api/v1/public/company')->assertOk();
        $this->withToken($this->publicToken)->getJson('/api/v1/public/company')->assertOk();
        $this->withToken($this->publicToken)->getJson('/api/v1/public/company')->assertStatus(429);
    }

    public function test_private_invoice_endpoint_exposes_rest_data(): void
    {
        $client = Client::create([
            'type' => 'company',
            'company_name' => 'Invoice Client',
            'status' => 'active',
        ]);

        Invoice::create([
            'client_id' => $client->id,
            'issue_date' => now()->toDateString(),
            'status' => 'sent',
            'total' => 25000,
            'balance_due' => 25000,
            'created_by' => $this->user->id,
        ]);

        $this->withToken($this->privateToken)
            ->getJson('/api/v1/private/invoices')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }
}
