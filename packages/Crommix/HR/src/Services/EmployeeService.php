<?php

namespace Crommix\HR\Services;

use Crommix\HR\Models\Employee;
use Crommix\HR\Models\LeaveRequest;
use Illuminate\Support\Collection;

class EmployeeService
{
    /**
     * Create a new employee record.
     *
     * @param array<string, mixed> $data
     */
    public function create(array $data): Employee
    {
        return Employee::create($data);
    }

    /**
     * Update an existing employee record.
     *
     * @param array<string, mixed> $data
     */
    public function update(Employee $employee, array $data): Employee
    {
        $employee->update($data);

        return $employee->refresh();
    }

    /**
     * Terminate an employee.
     */
    public function terminate(Employee $employee, \DateTimeInterface $terminatedAt): Employee
    {
        $employee->update([
            'status'        => 'terminated',
            'terminated_at' => $terminatedAt,
        ]);

        return $employee->refresh();
    }

    /**
     * Approve a leave request.
     */
    public function approveLeave(LeaveRequest $request, int $approverId): LeaveRequest
    {
        $request->update([
            'status'      => 'approved',
            'approved_by' => $approverId,
            'approved_at' => now(),
        ]);

        return $request->refresh();
    }

    /**
     * Reject a leave request.
     */
    public function rejectLeave(LeaveRequest $request, int $approverId, string $reason): LeaveRequest
    {
        $request->update([
            'status'           => 'rejected',
            'approved_by'      => $approverId,
            'approved_at'      => now(),
            'rejection_reason' => $reason,
        ]);

        return $request->refresh();
    }

    /**
     * Return all active employees for the current tenant.
     *
     * @return Collection<int, Employee>
     */
    public function activeEmployees(): Collection
    {
        return Employee::active()->get();
    }
}
