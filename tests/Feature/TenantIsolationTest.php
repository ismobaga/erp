<?php

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\Client;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Verifies that HasCompanyScope correctly isolates tenant data.
 *
 * Critical scenarios:
 *  1. Two tenants each see only their own records.
 *  2. ApiToken uses HasCompanyScope – tenant A cannot see tenant B's tokens.
 *  3. AuthenticateApiToken middleware finds a token cross-tenant (withoutCompanyScope).
 *  4. HTTP API requests are fully isolated: tenant A's token cannot see tenant B's records.
 */
class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    // ── 1. Two tenants see only their own records ──────────────────────────────

    public function test_two_tenants_are_isolated_from_each_other(): void
    {
        $companyA = $this->setUpCompany(Company::create(['name' => 'Tenant A', 'currency' => 'FCFA', 'is_active' => true]));
        Client::create(['type' => 'company', 'company_name' => 'Client of A', 'status' => 'active']);

        $companyB = Company::create(['name' => 'Tenant B', 'currency' => 'FCFA', 'is_active' => true]);

        // Insert directly for company B, bypassing the global scope.
        DB::table('clients')->insert([
            'company_id' => $companyB->id,
            'type' => 'company',
            'company_name' => 'Client of B',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Company A sees only its own client.
        $this->setUpCompany($companyA);
        $this->assertSame(1, Client::count());
        $this->assertSame('Client of A', Client::first()->company_name);

        // Switch to Company B.
        $this->setUpCompany($companyB);
        $this->assertSame(1, Client::count());
        $this->assertSame('Client of B', Client::first()->company_name);
    }

    // ── 2. ApiToken: HasCompanyScope applied ───────────────────────────────────

    public function test_api_token_has_company_scope(): void
    {
        $companyA = Company::create(['name' => 'Token Co A', 'currency' => 'FCFA', 'is_active' => true]);
        $companyB = Company::create(['name' => 'Token Co B', 'currency' => 'FCFA', 'is_active' => true]);

        $user = User::factory()->create(['status' => 'active']);
        $user->companies()->attach($companyA->id, ['role' => 'admin']);

        $plainToken = ApiToken::issue(user: $user, company: $companyA)['plainTextToken'];

        // With company A context, only one token is visible.
        $this->setUpCompany($companyA);
        $this->assertSame(1, ApiToken::count());

        // With company B context, the token is not visible.
        $this->setUpCompany($companyB);
        $this->assertSame(0, ApiToken::count());

        // withoutCompanyScope finds the token regardless of context (as AuthenticateApiToken does).
        $found = ApiToken::withoutCompanyScope()
            ->where('token_hash', hash('sha256', $plainToken))
            ->whereNull('revoked_at')
            ->first();

        $this->assertNotNull($found);
        $this->assertSame($companyA->id, (int) $found->company_id);
    }

    // ── 3. forCompany() bypasses global scope for a specific company ───────────

    public function test_for_company_bypasses_scope_correctly(): void
    {
        $companyA = Company::create(['name' => 'For Co A', 'currency' => 'FCFA', 'is_active' => true]);
        $companyB = Company::create(['name' => 'For Co B', 'currency' => 'FCFA', 'is_active' => true]);

        // Create a client under company A first (needed as FK for Invoice).
        $clientA = DB::table('clients')->insertGetId([
            'company_id' => $companyA->id,
            'type' => 'company',
            'company_name' => 'Client A',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Insert invoice for company A.
        DB::table('invoices')->insert([
            'company_id' => $companyA->id,
            'client_id' => $clientA,
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'status' => 'draft',
            'invoice_number' => 'INV-A-001',
            'total' => 1000,
            'balance_due' => 1000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Bind company B — direct query should return 0.
        $this->setUpCompany($companyB);
        $this->assertSame(0, Invoice::count());

        // forCompany(A) should return the invoice.
        $this->assertSame(1, Invoice::forCompany($companyA->id)->count());
    }

    // ── 4. HTTP API: tenant A's token cannot see tenant B's data ──────────────

    public function test_api_cross_tenant_isolation_via_http(): void
    {
        // ── Tenant A setup ─────────────────────────────────────────────────
        $companyA = Company::create(['name' => 'API Co A', 'currency' => 'FCFA', 'is_active' => true]);
        $this->setUpCompany($companyA);

        $userA = User::factory()->create(['status' => 'active']);
        $userA->companies()->attach($companyA->id, ['role' => 'admin']);

        $tokenA = ApiToken::issue(user: $userA, company: $companyA, scope: 'private')['plainTextToken'];

        $clientA = Client::create(['type' => 'company', 'company_name' => 'Client of A', 'status' => 'active']);

        // ── Tenant B setup ─────────────────────────────────────────────────
        $companyB = Company::create(['name' => 'API Co B', 'currency' => 'FCFA', 'is_active' => true]);
        $this->setUpCompany($companyB);

        $userB = User::factory()->create(['status' => 'active']);
        $userB->companies()->attach($companyB->id, ['role' => 'admin']);

        $tokenB = ApiToken::issue(user: $userB, company: $companyB, scope: 'private')['plainTextToken'];

        $clientB = Client::create(['type' => 'company', 'company_name' => 'Client of B', 'status' => 'active']);

        // ── Isolation assertions ───────────────────────────────────────────
        // Tenant A's token sees only Tenant A's client.
        $this->withToken($tokenA)
            ->getJson('/api/v1/private/clients')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.company_name', 'Client of A');

        // Tenant B's token sees only Tenant B's client.
        $this->withToken($tokenB)
            ->getJson('/api/v1/private/clients')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.company_name', 'Client of B');
    }
}
