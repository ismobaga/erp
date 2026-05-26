<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Company;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Project;
use App\Models\Service;
use App\Models\User;
use Database\Seeders\DemoCompanySeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class DemoEnvironmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('demo.enabled', true);
        config()->set('demo.read_only', false);
        config()->set('demo.allowed_environments', ['testing']);
        config()->set('demo.password', 'DemoPass!123');

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_demo_company_seeder_creates_scoped_demo_dataset(): void
    {
        $this->seed(DemoCompanySeeder::class);

        $demoCompany = Company::query()->where('slug', 'demo')->first();

        $this->assertNotNull($demoCompany);
        $this->assertTrue((bool) $demoCompany->is_demo);

        $this->assertDatabaseHas('users', ['email' => 'admin@demo.erp']);
        $this->assertDatabaseHas('users', ['email' => 'accountant@demo.erp']);
        $this->assertDatabaseHas('users', ['email' => 'sales@demo.erp']);
        $this->assertDatabaseHas('users', ['email' => 'manager@demo.erp']);

        $this->assertDatabaseCount('clients', 24);
        $this->assertDatabaseCount('services', 12);
        $this->assertDatabaseCount('invoices', 120);
        $this->assertDatabaseCount('expenses', 60);
        $this->assertDatabaseCount('projects', 6);

        $this->assertGreaterThanOrEqual(20, (int) Payment::withoutCompanyScope()->count());
    }

    public function test_demo_reset_command_recreates_demo_environment(): void
    {
        $this->seed(DemoCompanySeeder::class);

        $initialCompanyId = (int) Company::query()->where('slug', 'demo')->value('id');

        $this->artisan('demo:reset')->assertExitCode(0);

        $resetCompany = Company::query()->where('slug', 'demo')->first();

        $this->assertNotNull($resetCompany);
        $this->assertNotSame($initialCompanyId, (int) $resetCompany->id);
        $this->assertTrue((bool) $resetCompany->is_demo);

        $this->assertDatabaseCount('companies', 2);
        $this->assertDatabaseHas('users', ['email' => 'admin@demo.erp']);
        $this->assertGreaterThan(0, Client::withoutCompanyScope()->where('company_id', $resetCompany->id)->count());
        $this->assertGreaterThan(0, Service::withoutCompanyScope()->where('company_id', $resetCompany->id)->count());
        $this->assertGreaterThan(0, Project::withoutCompanyScope()->where('company_id', $resetCompany->id)->count());
        $this->assertGreaterThan(0, Expense::withoutCompanyScope()->where('company_id', $resetCompany->id)->count());
        $this->assertGreaterThan(0, Invoice::withoutCompanyScope()->where('company_id', $resetCompany->id)->count());
    }

    public function test_demo_read_only_blocks_destructive_operations(): void
    {
        $this->seed(DemoCompanySeeder::class);

        $demoCompany = Company::query()->where('slug', 'demo')->firstOrFail();
        app()->instance('currentCompany', $demoCompany);

        config()->set('demo.read_only', true);

        $invoice = Invoice::query()->firstOrFail();

        try {
            $invoice->delete();
            $this->fail('Expected invoice deletion to be blocked in demo read-only mode.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('demo', $exception->errors());
        }

        try {
            $demoCompany->delete();
            $this->fail('Expected demo company deletion to be blocked in demo read-only mode.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('company', $exception->errors());
        }

        $demoAdmin = User::query()->where('email', 'admin@demo.erp')->firstOrFail();

        try {
            $demoAdmin->delete();
            $this->fail('Expected demo admin deletion to be blocked in demo read-only mode.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('user', $exception->errors());
        }
    }
}
