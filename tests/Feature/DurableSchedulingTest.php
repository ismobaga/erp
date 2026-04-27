<?php

namespace Tests\Feature;

use App\Jobs\GenerateScheduledReportJob;
use App\Models\ReportSchedule;
use App\Models\User;
use App\Services\ReportExportService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class DurableSchedulingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_persist_scheduled_plan_creates_a_db_record(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $service = app(ReportExportService::class);

        $plan = $service->buildScheduledPlan([
            'scheduleFrequency' => 'Hebdomadaire',
            'nextExecutionAt' => now()->addDay()->toDateTimeString(),
            'scheduleEmail' => 'finance@example.com',
            'exportFormat' => 'pdf',
            'startDate' => now()->startOfMonth()->toDateString(),
            'endDate' => now()->endOfMonth()->toDateString(),
            'selectedModules' => ['invoices' => true, 'payments' => true],
            'includeCharts' => true,
        ], $user->id);

        $service->persistScheduledPlan($plan);

        $this->assertDatabaseHas('report_schedules', [
            'owner_id' => $user->id,
            'status' => 'active',
            'export_format' => 'pdf',
            'schedule_email' => 'finance@example.com',
        ]);
    }

    public function test_load_scheduled_plans_filters_by_owner(): void
    {
        $userA = User::factory()->create(['status' => 'active']);
        $userB = User::factory()->create(['status' => 'active']);

        ReportSchedule::create([
            'owner_id' => $userA->id,
            'description' => 'Plan for User A',
            'frequency' => 'Hebdomadaire',
            'export_format' => 'pdf',
            'next_execution_at' => now()->addDay(),
            'status' => 'active',
        ]);

        ReportSchedule::create([
            'owner_id' => $userB->id,
            'description' => 'Plan for User B',
            'frequency' => 'Hebdomadaire',
            'export_format' => 'csv',
            'next_execution_at' => now()->addDay(),
            'status' => 'active',
        ]);

        $service = app(ReportExportService::class);

        $plansA = $service->loadScheduledPlans($userA->id);
        $plansB = $service->loadScheduledPlans($userB->id);

        $this->assertCount(1, $plansA);
        $this->assertSame('Plan for User A', $plansA[0]['description']);

        $this->assertCount(1, $plansB);
        $this->assertSame('Plan for User B', $plansB[0]['description']);
    }

    public function test_run_due_scheduled_exports_dispatches_jobs(): void
    {
        \Illuminate\Support\Facades\Queue::fake();

        ReportSchedule::create([
            'description' => 'Due schedule',
            'frequency' => 'Hebdomadaire',
            'export_format' => 'pdf',
            'next_execution_at' => now()->subMinute(),
            'status' => 'active',
        ]);

        ReportSchedule::create([
            'description' => 'Future schedule',
            'frequency' => 'Hebdomadaire',
            'export_format' => 'pdf',
            'next_execution_at' => now()->addDay(),
            'status' => 'active',
        ]);

        $service = app(ReportExportService::class);
        $count = $service->runDueScheduledExports();

        $this->assertSame(1, $count);
        \Illuminate\Support\Facades\Queue::assertPushed(GenerateScheduledReportJob::class, 1);
    }

    public function test_report_schedule_next_run_advances_by_frequency(): void
    {
        $schedule = new ReportSchedule([
            'frequency' => 'Mensuelle',
            'next_execution_at' => now()->startOfMonth(),
        ]);

        $nextRun = $schedule->nextRun();

        $this->assertTrue($nextRun->greaterThan($schedule->next_execution_at));
        $this->assertEqualsWithDelta(30, $schedule->next_execution_at->diffInDays($nextRun), 3);
    }

    public function test_paused_schedule_is_not_returned_by_due_scope(): void
    {
        ReportSchedule::create([
            'description' => 'Paused schedule',
            'frequency' => 'Hebdomadaire',
            'export_format' => 'pdf',
            'next_execution_at' => now()->subMinute(),
            'status' => 'paused',
        ]);

        $due = ReportSchedule::due()->get();

        $this->assertEmpty($due);
    }

    public function test_due_schedule_is_claimed_only_once_before_dispatch(): void
    {
        \Illuminate\Support\Facades\Queue::fake();

        $schedule = ReportSchedule::create([
            'description' => 'Claim once schedule',
            'frequency' => 'Hebdomadaire',
            'export_format' => 'pdf',
            'next_execution_at' => now()->subMinute(),
            'status' => 'active',
        ]);

        $service = app(ReportExportService::class);

        $this->assertSame(1, $service->runDueScheduledExports());
        $this->assertSame(0, $service->runDueScheduledExports());
        $this->assertSame('processing', $schedule->fresh()->status);
        \Illuminate\Support\Facades\Queue::assertPushed(GenerateScheduledReportJob::class, 1);
    }

    public function test_failed_scheduled_job_releases_schedule_back_to_active(): void
    {
        $schedule = ReportSchedule::create([
            'description' => 'Retry schedule',
            'frequency' => 'Hebdomadaire',
            'export_format' => 'pdf',
            'next_execution_at' => now()->subMinute(),
            'status' => 'processing',
        ]);

        $job = new GenerateScheduledReportJob($schedule->id);
        $job->failed(new RuntimeException('boom'));

        $this->assertSame('active', $schedule->fresh()->status);
    }
}
