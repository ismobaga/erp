<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Client;
use App\Models\Expense;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OperationalWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_expense_can_be_approved_and_logged(): void
    {
        $manager = User::factory()->create();

        $expense = Expense::create([
            'category' => 'Travel',
            'title' => 'Regional site inspection',
            'amount' => 125000,
            'expense_date' => now()->toDateString(),
            'recorded_by' => $manager->id,
        ]);

        $expense->approve($manager, 'Validated for reimbursement.');
        $expense->refresh();

        $this->assertSame('approved', $expense->approval_status);
        $this->assertSame($manager->id, $expense->approved_by);
        $this->assertNotNull($expense->approved_at);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'expense_approved',
            'subject_type' => Expense::class,
            'subject_id' => $expense->id,
        ]);
    }

    public function test_project_can_move_through_operational_statuses(): void
    {
        $user = User::factory()->create();
        $client = Client::create([
            'type' => 'company',
            'company_name' => 'ACME Build',
            'status' => 'active',
        ]);

        $project = Project::create([
            'client_id' => $client->id,
            'name' => 'Bamako Office Fit-out',
            'status' => 'planned',
            'due_date' => now()->addDays(21)->toDateString(),
            'created_by' => $user->id,
        ]);

        $project->markInProgress();
        $project->refresh();

        $this->assertSame('in_progress', $project->status);

        $project->markCompleted();
        $project->refresh();

        $this->assertSame('completed', $project->status);
    }
}
