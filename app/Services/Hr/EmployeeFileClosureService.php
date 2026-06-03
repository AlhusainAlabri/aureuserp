<?php

namespace App\Services\Hr;

use App\Listeners\Hr\NotifyEmployeeFileClosure;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Webkul\Employee\Models\Employee;
use Webkul\Security\Models\User;

class EmployeeFileClosureService
{
    public function isClosed(Employee $employee): bool
    {
        return (bool) $employee->is_closed;
    }

    public function canBeClosedBy(Employee $employee, User $user): bool
    {
        return $user->can('close_employee_file');
    }

    public function close(Employee $employee, User $user, string $reason, ?string $notes = null): void
    {
        if (! $this->canBeClosedBy($employee, $user)) {
            throw new RuntimeException(__('hr-extensions::employee.exceptions.cannot_close'));
        }

        DB::transaction(function () use ($employee, $user, $reason, $notes): void {
            $employee->forceFill([
                'is_closed'       => true,
                'closure_reason'  => $reason,
                'closure_notes'   => $notes,
                'closed_at'       => now(),
                'closed_by'       => $user->id,
                'reopen_reason'   => null,
                'reopened_at'     => null,
                'reopened_by'     => null,
                'is_active'       => false,
            ])->save();

            if ($employee->user) {
                $employee->user->update(['is_active' => false]);
            }
        });

        app(NotifyEmployeeFileClosure::class)->handle($employee->fresh(), $user);
    }

    public function reopen(Employee $employee, User $user, string $reason): void
    {
        if (! $user->can('reopen_employee_file') && ! $user->hasRole('hr_manager')) {
            throw new RuntimeException(__('hr-extensions::employee.exceptions.cannot_reopen'));
        }

        DB::transaction(function () use ($employee, $user, $reason): void {
            $employee->forceFill([
                'is_closed'      => false,
                'closure_reason' => null,
                'closure_notes'  => null,
                'closed_at'      => null,
                'closed_by'      => null,
                'reopen_reason'  => $reason,
                'reopened_at'    => now(),
                'reopened_by'    => $user->id,
                'is_active'      => true,
            ])->save();

            if ($employee->user) {
                $employee->user->update(['is_active' => true]);
            }
        });
    }
}
