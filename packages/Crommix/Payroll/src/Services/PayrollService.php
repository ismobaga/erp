<?php

namespace Crommix\Payroll\Services;

use Crommix\HR\Models\Employee;
use Crommix\Payroll\Models\PayrollItem;
use Crommix\Payroll\Models\PayrollRun;

class PayrollService
{
    /**
     * Create a new payroll run for the given month.
     *
     * @param array<string, mixed> $data
     */
    public function createRun(array $data): PayrollRun
    {
        return PayrollRun::create($data);
    }

    /**
     * Generate payroll items for all active employees in the current company.
     */
    public function generateItems(PayrollRun $run): PayrollRun
    {
        $employees = Employee::active()->get();

        foreach ($employees as $employee) {
            $gross = (float) ($employee->base_salary ?? 0);

            PayrollItem::create([
                'payroll_run_id' => $run->id,
                'employee_id'    => $employee->id,
                'gross_salary'   => $gross,
                'deductions'     => 0,
                'bonuses'        => 0,
                'net_salary'     => $gross,
                'status'         => 'pending',
            ]);
        }

        $this->recalculate($run);

        return $run->refresh();
    }

    /**
     * Recalculate the totals of a payroll run from its items.
     */
    public function recalculate(PayrollRun $run): void
    {
        $items = $run->items;

        $run->update([
            'total_gross'      => $items->sum('gross_salary'),
            'total_deductions' => $items->sum('deductions'),
            'total_net'        => $items->sum('net_salary'),
        ]);
    }

    /**
     * Mark the run as completed and record the processor.
     */
    public function complete(PayrollRun $run, int $userId): PayrollRun
    {
        $run->update([
            'status'       => 'completed',
            'processed_by' => $userId,
            'processed_at' => now(),
        ]);

        return $run->refresh();
    }
}
