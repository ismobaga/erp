<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Project;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Aggregates cross-module KPI metrics for the analytics dashboard and API.
 *
 * All queries are guarded with Schema::hasTable() checks so the service
 * degrades gracefully when optional module tables are absent.
 */
class AnalyticsService
{
    /**
     * Build the full KPI payload for a date range.
     *
     * @return array<string, mixed>
     */
    public function kpis(Carbon $start, Carbon $end): array
    {
        return [
            'finance' => $this->financeKpis($start, $end),
            'clients' => $this->clientKpis($start, $end),
            'projects' => $this->projectKpis($start, $end),
            'crm' => $this->crmKpis($start, $end),
            'hr' => $this->hrKpis($start, $end),
        ];
    }

    // ── Finance ────────────────────────────────────────────────────────────────

    /**
     * @return array<string, mixed>
     */
    public function financeKpis(Carbon $start, Carbon $end): array
    {
        $revenue = Invoice::query()
            ->whereDate('issue_date', '>=', $start->toDateString())
            ->whereDate('issue_date', '<=', $end->toDateString())
            ->sum('total');

        $collected = Payment::query()
            ->whereDate('payment_date', '>=', $start->toDateString())
            ->whereDate('payment_date', '<=', $end->toDateString())
            ->sum('amount');

        $outstanding = Invoice::query()
            ->whereNotIn('status', ['paid', 'cancelled'])
            ->sum('balance_due');

        $expenses = Expense::query()
            ->whereDate('expense_date', '>=', $start->toDateString())
            ->whereDate('expense_date', '<=', $end->toDateString())
            ->sum('amount');

        $invoicesByStatus = Invoice::query()
            ->whereDate('issue_date', '>=', $start->toDateString())
            ->whereDate('issue_date', '<=', $end->toDateString())
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();

        $collectionRate = $revenue > 0
            ? round((float) $collected / (float) $revenue * 100, 1)
            : 0.0;

        return [
            'revenue' => (float) $revenue,
            'collected' => (float) $collected,
            'outstanding' => (float) $outstanding,
            'expenses' => (float) $expenses,
            'collection_rate' => $collectionRate,
            'invoices_by_status' => $invoicesByStatus,
        ];
    }

    // ── Clients ────────────────────────────────────────────────────────────────

    /**
     * @return array<string, mixed>
     */
    public function clientKpis(Carbon $start, Carbon $end): array
    {
        $total = Client::query()->count();
        $active = Client::query()->where('status', 'active')->count();
        $new = Client::query()
            ->whereDate('created_at', '>=', $start->toDateString())
            ->whereDate('created_at', '<=', $end->toDateString())
            ->count();

        return [
            'total' => $total,
            'active' => $active,
            'new' => $new,
        ];
    }

    // ── Projects ───────────────────────────────────────────────────────────────

    /**
     * @return array<string, mixed>
     */
    public function projectKpis(Carbon $start, Carbon $end): array
    {
        $byStatus = Project::query()
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();

        $completed = (int) ($byStatus['completed'] ?? 0);
        $total = array_sum($byStatus);

        $completionRate = $total > 0 ? round($completed / $total * 100, 1) : 0.0;

        $newInPeriod = Project::query()
            ->whereDate('created_at', '>=', $start->toDateString())
            ->whereDate('created_at', '<=', $end->toDateString())
            ->count();

        return [
            'total' => $total,
            'by_status' => $byStatus,
            'completion_rate' => $completionRate,
            'new' => $newInPeriod,
        ];
    }

    // ── CRM ────────────────────────────────────────────────────────────────────

    /**
     * @return array<string, mixed>
     */
    public function crmKpis(Carbon $start, Carbon $end): array
    {
        if (! Schema::hasTable('crm_leads')) {
            return ['available' => false];
        }

        $leadsByStatus = DB::table('crm_leads')
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();

        $newInPeriod = DB::table('crm_leads')
            ->whereDate('created_at', '>=', $start->toDateString())
            ->whereDate('created_at', '<=', $end->toDateString())
            ->count();

        $convertedInPeriod = DB::table('crm_leads')
            ->where('status', 'converted')
            ->whereDate('converted_at', '>=', $start->toDateString())
            ->whereDate('converted_at', '<=', $end->toDateString())
            ->count();

        $total = array_sum($leadsByStatus);
        $conversionRate = $total > 0
            ? round((int) ($leadsByStatus['converted'] ?? 0) / $total * 100, 1)
            : 0.0;

        return [
            'available' => true,
            'total' => $total,
            'by_status' => $leadsByStatus,
            'new' => $newInPeriod,
            'converted' => $convertedInPeriod,
            'conversion_rate' => $conversionRate,
        ];
    }

    // ── HR ─────────────────────────────────────────────────────────────────────

    /**
     * @return array<string, mixed>
     */
    public function hrKpis(Carbon $start, Carbon $end): array
    {
        if (! Schema::hasTable('hr_employees')) {
            return ['available' => false];
        }

        $total = DB::table('hr_employees')->count();
        $active = DB::table('hr_employees')->where('status', 'active')->count();

        $hiredInPeriod = DB::table('hr_employees')
            ->whereDate('hired_at', '>=', $start->toDateString())
            ->whereDate('hired_at', '<=', $end->toDateString())
            ->count();

        $leaveRequests = Schema::hasTable('hr_leave_requests')
            ? DB::table('hr_leave_requests')
                ->whereDate('created_at', '>=', $start->toDateString())
                ->whereDate('created_at', '<=', $end->toDateString())
                ->count()
            : 0;

        return [
            'available' => true,
            'total' => $total,
            'active' => $active,
            'hired' => $hiredInPeriod,
            'leave_requests' => $leaveRequests,
        ];
    }
}
