<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class OperationalResilienceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_backup_and_restore_commands_preserve_financial_records(): void
    {
        Storage::fake('local');

        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole('Super Admin');

        $client = Client::create([
            'type' => 'company',
            'company_name' => 'Backup Corp',
            'status' => 'active',
        ]);

        $invoice = Invoice::create([
            'client_id' => $client->id,
            'issue_date' => now()->toDateString(),
            'status' => 'sent',
            'created_by' => $user->id,
            'total' => 450,
            'balance_due' => 450,
        ]);

        Artisan::call('erp:backup-run');

        $invoice->delete();
        $this->assertDatabaseMissing('invoices', ['id' => $invoice->id]);

        Artisan::call('erp:restore-backup', ['--force' => true]);

        $this->assertDatabaseHas('invoices', ['id' => $invoice->id]);
        $this->assertDatabaseHas('activity_logs', ['action' => 'system_backup_created']);
        $this->assertDatabaseHas('activity_logs', ['action' => 'system_backup_restored']);
    }

    public function test_health_monitor_logs_alerts_for_failed_jobs(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole('Super Admin');

        config()->set('erp.resilience.monitoring.failed_jobs_alert_threshold', 1);

        DB::table('failed_jobs')->insert([
            'uuid' => (string) Str::uuid(),
            'connection' => 'database',
            'queue' => 'default',
            'payload' => json_encode(['job' => 'DemoJob']),
            'exception' => 'Synthetic failure for monitoring test',
            'failed_at' => now(),
        ]);

        Artisan::call('erp:monitor-health');

        $this->assertDatabaseHas('activity_logs', ['action' => 'system_alert_raised']);
    }

    public function test_finance_user_can_open_the_operational_audit_dashboard(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole('Finance');

        ActivityLog::create([
            'user_id' => $user->id,
            'action' => 'system_backup_created',
            'meta_json' => ['file' => 'backup-demo.json'],
        ]);

        ActivityLog::create([
            'user_id' => $user->id,
            'action' => 'system_alert_raised',
            'meta_json' => ['reason' => 'Failed jobs threshold reached'],
        ]);

        $response = $this->actingAs($user)->get('/admin/operational-resilience');

        $response->assertOk();
        $response->assertSee('Résilience opérationnelle');
        $response->assertSee('Sauvegarde et restauration');
        $response->assertSee('Audit administrateur');
        $response->assertSee('Alertes système');
    }
}
